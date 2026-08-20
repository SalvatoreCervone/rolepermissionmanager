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
    public function index(Request $request)
    {
        $perPage = config('rolepermissionmanager.admin_panel.per_page', 25);

        $query = SecuredResource::with('permissions')->orderBy('identifier');

        // Filters
        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }
        if ($request->filled('status')) {
            match ($request->get('status')) {
                'public'     => $query->where('is_public', true)->where('is_deprecated', false),
                'protected'  => $query->where('is_public', false)->where('is_deprecated', false),
                'deprecated' => $query->where('is_deprecated', true),
                default      => null,
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

        $resources = $query->paginate($perPage)->appends($request->query());

        $methods = SecuredResource::distinct()->pluck('method')->sort();

        return view('acl::resources.index', compact('resources', 'methods'));
    }

    public function edit(int $id)
    {
        $resource = SecuredResource::with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::resources.edit', compact('resource', 'allPermissions'));
    }

    public function update(Request $request, int $id)
    {
        $resource = SecuredResource::findOrFail($id);

        $validated = $request->validate([
            'is_public'    => 'boolean',
            'operator'     => 'required|in:OR,AND',
            'permissions'  => 'nullable|array',
            'permissions.*' => 'integer|exists:' . config('rolepermissionmanager.tables.permissions', 'acl_permissions') . ',id',
        ]);

        $resource->update([
            'is_public' => $validated['is_public'] ?? false,
            'operator'  => $validated['operator'],
        ]);

        $resource->permissions()->sync($validated['permissions'] ?? []);
        AclRegistry::refreshCache();

        return redirect()
            ->route('acl.resources.edit', $id)
            ->with('success', "Resource '{$resource->identifier}' updated successfully.");
    }

    public function sync()
    {
        Artisan::call('acl:sync', ['--notify' => true]);
        $output = Artisan::output();

        return redirect()
            ->route('acl.dashboard')
            ->with('success', 'Route sync completed successfully.')
            ->with('sync_output', $output);
    }
}
