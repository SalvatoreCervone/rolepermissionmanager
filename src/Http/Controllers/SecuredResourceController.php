<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

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

        return view('acl::resources.index', compact('resources'));
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

        return redirect()
            ->route('acl.resources.index')
            ->with('success', __('acl::resources.deleted_success', ['identifier' => $identifier]));
    }

    /**
     * Trigger route sync (kept for backwards compatibility).
     */
    public function sync()
    {
        Artisan::call('acl:sync', ['--notify' => true]);
        $output = Artisan::output();

        return redirect()
            ->route('acl.routes.index')
            ->with('success', __('acl::routes.sync_success'))
            ->with('sync_output', $output);
    }
}
