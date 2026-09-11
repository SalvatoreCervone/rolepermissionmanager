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
        $defaultPerPage = (int) config('rolepermissionmanager.admin_panel.per_page', 25);
        $perPageParam = $request->get('per_page');
        $perPage = $perPageParam === 'all' ? 10000 : (in_array((int)$perPageParam, [25, 50, 100]) ? (int)$perPageParam : $defaultPerPage);
        $status = $request->get('status');

        /** @var RouteScanner $scanner */
        $scanner = app(RouteScanner::class);
        $allSkipped = $scanner->getSkippedRoutes();
        $managedCount = SecuredResource::routes()->count();
        $skippedCount = $allSkipped->count();
        $totalCount = $managedCount + $skippedCount;

        $query = SecuredResource::routes()->with('permissions')->orderBy('identifier');

        // Filters on DB query
        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }
        if ($request->filled('file')) {
            $query->where('source_file', $request->get('file'));
        }
        if ($request->filled('status') && in_array($status, ['public', 'protected', 'super_admin', 'deprecated'])) {
            match ($status) {
                'public'      => $query->public()->active(),
                'protected'   => $query->protected()->notSuperAdminOnly()->active(),
                'super_admin' => $query->superAdminOnly()->active(),
                'deprecated'  => $query->where('is_deprecated', true),
                default       => null,
            };
        }
        if ($request->filled('permission')) {
            $permission = $request->get('permission');
            if ($permission === 'none') {
                $query->whereDoesntHave('permissions');
            } elseif ($permission === 'has_any') {
                $query->whereHas('permissions');
            } else {
                $query->whereHas('permissions', function ($q) use ($permission) {
                    $q->where('id', $permission)->orWhere('slug', $permission);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                  ->orWhere('uri', 'like', "%{$search}%")
                  ->orWhere('controller_action', 'like', "%{$search}%")
                  ->orWhere('source_file', 'like', "%{$search}%");
            });
        }

        // Filter skipped routes collection
        $filteredSkipped = $allSkipped;
        if ($request->filled('method')) {
            $filteredSkipped = $filteredSkipped->where('method', $request->get('method'));
        }
        if ($request->filled('file')) {
            $filteredSkipped = $filteredSkipped->where('source_file', $request->get('file'));
        }
        if ($request->filled('search')) {
            $search = strtolower($request->get('search'));
            $filteredSkipped = $filteredSkipped->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->identifier), $search)
                    || str_contains(strtolower($item->uri), $search)
                    || str_contains(strtolower($item->controller_action), $search)
                    || str_contains(strtolower($item->source_file ?? ''), $search)
                    || str_contains(strtolower($item->reason), $search);
            });
        }
        if ($request->filled('permission') && $request->get('permission') !== 'none') {
            // Skipped routes don't have assigned permissions
            $filteredSkipped = collect();
        }

        $page = (int) $request->get('page', 1);

        if ($status === 'skipped') {
            $isSkipped = true;
            $total = $filteredSkipped->count();
            $items = $filteredSkipped->slice(($page - 1) * $perPage, $perPage)->values();

            $routes = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } elseif ($status === 'managed' || in_array($status, ['public', 'protected', 'super_admin', 'deprecated'])) {
            $isSkipped = false;
            $routes = $query->paginate($perPage)->appends($request->query());
        } else {
            // Unified 'all' (default): merge DB routes and filtered skipped routes
            $isSkipped = false;
            $dbRoutes = $query->get();
            $combined = $dbRoutes->concat($filteredSkipped)->sortBy('identifier')->values();

            $total = $combined->count();
            $items = $combined->slice(($page - 1) * $perPage, $perPage)->values();

            $routes = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $methods = SecuredResource::routes()->whereNotNull('method')->pluck('method')->merge($allSkipped->pluck('method'))->unique()->sort()->values();
        $routeFiles = SecuredResource::routes()->whereNotNull('source_file')->pluck('source_file')->merge($allSkipped->pluck('source_file')->filter())->unique()->sort()->values();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::routes.index', compact(
            'routes',
            'methods',
            'routeFiles',
            'allPermissions',
            'isSkipped',
            'managedCount',
            'skippedCount',
            'totalCount',
            'status'
        ));
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
