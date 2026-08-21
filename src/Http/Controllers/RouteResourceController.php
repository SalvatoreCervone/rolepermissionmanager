<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\AuditLogger;
use SalvatoreCervone\RolePermissionManager\Services\RouteScanner;

class RouteResourceController extends Controller
{
    /**
     * Display a listing of scanned HTTP routes.
     */
    public function index(Request $request)
    {
        $perPage = config('rolepermissionmanager.admin_panel.per_page', 25);
        $status = $request->get('status');

        // Handle Skipped / Excluded routes view
        if ($status === 'skipped') {
            /** @var RouteScanner $scanner */
            $scanner = app(RouteScanner::class);
            $allSkipped = $scanner->getSkippedRoutes();

            if ($request->filled('method')) {
                $method = $request->get('method');
                $allSkipped = $allSkipped->where('method', $method);
            }

            if ($request->filled('search')) {
                $search = strtolower($request->get('search'));
                $allSkipped = $allSkipped->filter(function ($item) use ($search) {
                    return str_contains(strtolower($item->identifier), $search)
                        || str_contains(strtolower($item->uri), $search)
                        || str_contains(strtolower($item->controller_action), $search)
                        || str_contains(strtolower($item->reason), $search);
                });
            }

            $page = (int) $request->get('page', 1);
            $total = $allSkipped->count();
            $items = $allSkipped->slice(($page - 1) * $perPage, $perPage)->values();

            $routes = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $methods = $scanner->getSkippedRoutes()->pluck('method')->unique()->sort();
            $isSkipped = true;

            return view('acl::routes.index', compact('routes', 'methods', 'isSkipped'));
        }

        $query = SecuredResource::routes()->with('permissions')->orderBy('identifier');

        // Filters
        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }
        if ($request->filled('status')) {
            match ($request->get('status')) {
                'public'      => $query->public()->active(),
                'protected'   => $query->protected()->notSuperAdminOnly()->active(),
                'super_admin' => $query->superAdminOnly()->active(),
                'deprecated'  => $query->where('is_deprecated', true),
                default       => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                  ->orWhere('uri', 'like', "%{$search}%")
                  ->orWhere('controller_action', 'like', "%{$search}%");
            });
        }

        $routes = $query->paginate($perPage)->appends($request->query());
        $methods = SecuredResource::routes()->whereNotNull('method')->distinct()->pluck('method')->sort();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $isSkipped = false;

        return view('acl::routes.index', compact('routes', 'methods', 'allPermissions', 'isSkipped'));
    }

    /**
     * Show form for configuring permissions on an HTTP route.
     */
    public function edit(int $id)
    {
        $resource = SecuredResource::routes()->with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::routes.edit', compact('resource', 'allPermissions'));
    }

    /**
     * Update permissions configuration for an HTTP route.
     */
    public function update(Request $request, int $id)
    {
        $resource = SecuredResource::routes()->findOrFail($id);

        $validated = $request->validate([
            'is_public'           => 'boolean',
            'is_super_admin_only' => 'boolean',
            'operator'            => 'required|in:OR,AND',
            'permissions'         => 'nullable|array',
            'permissions.*'       => 'integer|exists:' . config('rolepermissionmanager.tables.permissions', 'acl_permissions') . ',id',
        ]);

        $resource->update([
            'is_public'           => $validated['is_public'] ?? false,
            'is_super_admin_only' => $validated['is_super_admin_only'] ?? false,
            'operator'            => $validated['operator'],
        ]);

        $resource->permissions()->sync($validated['permissions'] ?? []);
        AclRegistry::refreshCache();

        AuditLogger::log('route_updated', 'Route', $resource->identifier, "Updated route '{$resource->identifier}' access configuration");

        return redirect()
            ->route('acl.routes.edit', $id)
            ->with('success', __('acl::routes.updated_success', ['identifier' => $resource->identifier]));
    }

    /**
     * Perform bulk update actions on multiple selected routes.
     */
    public function bulkUpdate(Request $request)
    {
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');
        $resourcesTable = config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');

        $validated = $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => "integer|exists:{$resourcesTable},id",
            'action'        => 'required|string|in:set_super_admin,remove_super_admin,make_public,make_protected,add_permissions,sync_permissions,remove_all_permissions,set_operator_or,set_operator_and',
            'permissions'   => 'nullable|array',
            'permissions.*' => "integer|exists:{$permissionsTable},id",
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $count = count($ids);
        $resources = SecuredResource::routes()->whereIn('id', $ids)->get();

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

        AuditLogger::log('routes_bulk_updated', 'Route', "{$count} routes", "Bulk applied action '{$action}' to {$count} routes");

        return redirect()
            ->route('acl.routes.index')
            ->with('success', __('acl::routes.bulk_updated_success', ['count' => $count]));
    }

    /**
     * Trigger route synchronization.
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
