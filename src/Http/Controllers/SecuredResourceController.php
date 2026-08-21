<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\AuditLogger;

class SecuredResourceController extends Controller
{
    /**
     * Display a listing of custom resources (classes, methods, UI elements).
     */
    public function index(Request $request)
    {
        $perPage = config('rolepermissionmanager.admin_panel.per_page', 25);

        $query = SecuredResource::custom()->with('permissions')->orderBy('identifier');

        // Filters
        if ($request->filled('status')) {
            match ($request->get('status')) {
                'public'      => $query->where('is_public', true),
                'protected'   => $query->where('is_public', false)->where('is_super_admin_only', false),
                'super_admin' => $query->where('is_super_admin_only', true),
                default       => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('controller_action', 'like', "%{$search}%");
            });
        }

        $resources = $query->paginate($perPage)->appends($request->query());
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::resources.index', compact('resources', 'allPermissions'));
    }

    /**
     * Show the form for creating a new custom resource.
     */
    public function create()
    {
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::resources.create', compact('allPermissions'));
    }

    /**
     * Store a newly created custom resource in storage.
     */
    public function store(Request $request)
    {
        $resourcesTable = config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');

        $validated = $request->validate([
            'identifier'          => "required|string|max:255|unique:{$resourcesTable},identifier",
            'description'         => 'nullable|string|max:255',
            'controller_action'   => 'nullable|string|max:255',
            'is_public'           => 'boolean',
            'is_super_admin_only' => 'boolean',
            'operator'            => 'required|in:OR,AND',
            'permissions'         => 'nullable|array',
            'permissions.*'       => "integer|exists:{$permissionsTable},id",
        ]);

        $resource = SecuredResource::create([
            'identifier'          => trim($validated['identifier']),
            'type'                => SecuredResource::TYPE_CUSTOM,
            'description'         => $validated['description'] ?? null,
            'controller_action'   => $validated['controller_action'] ?? null,
            'is_public'           => $validated['is_public'] ?? false,
            'is_super_admin_only' => $validated['is_super_admin_only'] ?? false,
            'operator'            => $validated['operator'],
            'is_deprecated'       => false,
        ]);

        if (!empty($validated['permissions'])) {
            $resource->permissions()->sync($validated['permissions']);
        }

        AclRegistry::refreshCache();

        AuditLogger::log('resource_created', 'Resource', $resource->identifier, "Created custom resource '{$resource->identifier}'");

        return redirect()
            ->route('acl.resources.index')
            ->with('success', __('acl::resources.created_success', ['identifier' => $resource->identifier]));
    }

    /**
     * Show the form for editing the specified custom resource.
     */
    public function edit(int $id)
    {
        $resource = SecuredResource::custom()->with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::resources.edit', compact('resource', 'allPermissions'));
    }

    /**
     * Update the specified custom resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $resource = SecuredResource::custom()->findOrFail($id);
        $resourcesTable = config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');

        $validated = $request->validate([
            'identifier'          => "required|string|max:255|unique:{$resourcesTable},identifier,{$id}",
            'description'         => 'nullable|string|max:255',
            'controller_action'   => 'nullable|string|max:255',
            'is_public'           => 'boolean',
            'is_super_admin_only' => 'boolean',
            'operator'            => 'required|in:OR,AND',
            'permissions'         => 'nullable|array',
            'permissions.*'       => "integer|exists:{$permissionsTable},id",
        ]);

        $resource->update([
            'identifier'          => trim($validated['identifier']),
            'description'         => $validated['description'] ?? null,
            'controller_action'   => $validated['controller_action'] ?? null,
            'is_public'           => $validated['is_public'] ?? false,
            'is_super_admin_only' => $validated['is_super_admin_only'] ?? false,
            'operator'            => $validated['operator'],
        ]);

        $resource->permissions()->sync($validated['permissions'] ?? []);
        AclRegistry::refreshCache();

        AuditLogger::log('resource_updated', 'Resource', $resource->identifier, "Updated custom resource '{$resource->identifier}'");

        return redirect()
            ->route('acl.resources.edit', $id)
            ->with('success', __('acl::resources.updated_success', ['identifier' => $resource->identifier]));
    }

    /**
     * Remove the specified custom resource from storage.
     */
    public function destroy(int $id)
    {
        $resource = SecuredResource::custom()->findOrFail($id);
        $identifier = $resource->identifier;

        $resource->permissions()->detach();
        $resource->delete();

        AclRegistry::refreshCache();

        AuditLogger::log('resource_deleted', 'Resource', $identifier, "Deleted custom resource '{$identifier}'");

        return redirect()
            ->route('acl.resources.index')
            ->with('success', __('acl::resources.deleted_success', ['identifier' => $identifier]));
    }

    /**
     * Perform bulk update actions on multiple selected custom resources.
     */
    public function bulkUpdate(Request $request)
    {
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');
        $resourcesTable = config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');

        $validated = $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => "integer|exists:{$resourcesTable},id",
            'action'        => 'required|string|in:set_super_admin,remove_super_admin,make_public,make_protected,add_permissions,sync_permissions,remove_all_permissions,set_operator_or,set_operator_and,delete',
            'permissions'   => 'nullable|array',
            'permissions.*' => "integer|exists:{$permissionsTable},id",
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $count = count($ids);
        $resources = SecuredResource::custom()->whereIn('id', $ids)->get();

        match ($action) {
            'set_super_admin' => SecuredResource::whereIn('id', $ids)->update([
                'is_super_admin_only' => true,
                'is_public'           => false,
            ]),
            'remove_super_admin' => SecuredResource::whereIn('id', $ids)->update([
                'is_super_admin_only' => false,
            ]),
            'make_public' => SecuredResource::whereIn('id', $ids)->update([
                'is_public'           => true,
                'is_super_admin_only' => false,
            ]),
            'make_protected' => SecuredResource::whereIn('id', $ids)->update([
                'is_public' => false,
            ]),
            'set_operator_or' => SecuredResource::whereIn('id', $ids)->update([
                'operator' => 'OR',
            ]),
            'set_operator_and' => SecuredResource::whereIn('id', $ids)->update([
                'operator' => 'AND',
            ]),
            'delete' => (function () use ($resources) {
                foreach ($resources as $res) {
                    $res->permissions()->detach();
                    $res->delete();
                }
            })(),
            'add_permissions' => (function () use ($resources, $validated) {
                $perms = $validated['permissions'] ?? [];
                if (!empty($perms)) {
                    foreach ($resources as $res) {
                        $res->permissions()->syncWithoutDetaching($perms);
                    }
                }
            })(),
            'sync_permissions' => (function () use ($resources, $validated) {
                $perms = $validated['permissions'] ?? [];
                foreach ($resources as $res) {
                    $res->permissions()->sync($perms);
                }
            })(),
            'remove_all_permissions' => (function () use ($resources) {
                foreach ($resources as $res) {
                    $res->permissions()->detach();
                }
            })(),
        };

        AclRegistry::refreshCache();

        AuditLogger::log('resources_bulk_updated', 'Resource', "{$count} resources", "Bulk applied action '{$action}' to {$count} custom resources");

        return redirect()
            ->route('acl.resources.index')
            ->with('success', __('acl::routes.bulk_updated_success', ['count' => $count]));
    }

    /**
     * Trigger route sync (kept for backwards compatibility).
     */
    public function sync()
    {
        Artisan::call('acl:sync', ['--notify' => true]);
        $output = Artisan::output();

        AuditLogger::log('routes_synced', 'RouteScanner', 'Sync Routes', 'Executed route synchronization from Web UI');

        return redirect()
            ->route('acl.routes.index')
            ->with('success', __('acl::routes.sync_success'))
            ->with('sync_output', $output);
    }
}
