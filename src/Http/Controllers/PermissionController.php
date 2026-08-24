<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\AuditLogger;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $defaultPerPage = (int) config('rolepermissionmanager.admin_panel.per_page', 25);
        $perPageParam = $request->get('per_page');
        $perPage = $perPageParam === 'all' ? 10000 : (in_array((int)$perPageParam, [25, 50, 100]) ? (int)$perPageParam : $defaultPerPage);
        $moduleFilter = $request->get('module');

        $query = Permission::withCount('roles', 'securedResources')->orderBy('module')->orderBy('name');

        if ($moduleFilter) {
            $query->where('module', $moduleFilter);
        }

        $permissions = $query->paginate($perPage)->appends($request->query());
        $modules = Permission::whereNotNull('module')->distinct()->pluck('module')->sort();

        return view('acl::permissions.index', compact('permissions', 'modules', 'moduleFilter'));
    }

    public function create()
    {
        $modules = Permission::whereNotNull('module')->distinct()->pluck('module')->sort();

        return view('acl::permissions.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:' . config('rolepermissionmanager.tables.permissions', 'acl_permissions') . ',slug',
            'module'      => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::create($validated);
        AclRegistry::flushResourcesCache();

        AuditLogger::log('permission_created', 'Permission', $permission->slug, "Created permission '{$permission->name}' [Module: " . ($permission->module ?: 'General') . "]");

        return redirect()
            ->route('acl.permissions.index')
            ->with('success', __('acl::permissions.created_success', ['name' => $validated['name']]));
    }

    public function edit(int $id)
    {
        $permission = Permission::with('roles', 'securedResources')->findOrFail($id);
        $modules = Permission::whereNotNull('module')->distinct()->pluck('module')->sort();

        return view('acl::permissions.edit', compact('permission', 'modules'));
    }

    public function update(Request $request, int $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:' . config('rolepermissionmanager.tables.permissions', 'acl_permissions') . ',slug,' . $id,
            'module'      => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission->update($validated);
        AclRegistry::flushResourcesCache();

        AuditLogger::log('permission_updated', 'Permission', $permission->slug, "Updated permission '{$permission->name}'");

        return redirect()
            ->route('acl.permissions.edit', $id)
            ->with('success', __('acl::permissions.updated_success', ['name' => $permission->name]));
    }

    public function destroy(int $id)
    {
        $permission = Permission::findOrFail($id);
        $name = $permission->name;
        $slug = $permission->slug;

        $permission->roles()->detach();
        $permission->securedResources()->detach();
        $permission->delete();
        AclRegistry::flushResourcesCache();

        AuditLogger::log('permission_deleted', 'Permission', $slug, "Deleted permission '{$name}' ({$slug})");

        return redirect()
            ->route('acl.permissions.index')
            ->with('success', __('acl::permissions.deleted_success', ['name' => $name]));
    }
}
