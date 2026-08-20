<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
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
                'public'      => $query->where('is_public', true)->where('is_deprecated', false),
                'protected'   => $query->where('is_public', false)->where('is_super_admin_only', false)->where('is_deprecated', false),
                'super_admin' => $query->where('is_super_admin_only', true)->where('is_deprecated', false),
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
        $isSkipped = false;

        return view('acl::routes.index', compact('routes', 'methods', 'isSkipped'));
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

        return redirect()
            ->route('acl.routes.edit', $id)
            ->with('success', __('acl::routes.updated_success', ['identifier' => $resource->identifier]));
    }

    /**
     * Trigger route synchronization.
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
