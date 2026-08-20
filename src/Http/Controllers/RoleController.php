<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = config('rolepermissionmanager.admin_panel.per_page', 25);

        $roles = Role::withCount('permissions')
            ->orderBy('name')
            ->paginate($perPage);

        return view('acl::roles.index', compact('roles'));
    }

    public function create()
    {
        return view('acl::roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:' . config('rolepermissionmanager.tables.roles', 'acl_roles') . ',slug',
            'description' => 'nullable|string|max:500',
        ]);

        Role::create($validated);
        AclRegistry::flushResourcesCache();

        return redirect()
            ->route('acl.roles.index')
            ->with('success', "Role '{$validated['name']}' created successfully.");
    }

    public function edit(int $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::roles.edit', compact('role', 'allPermissions'));
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:' . config('rolepermissionmanager.tables.roles', 'acl_roles') . ',slug,' . $id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:' . config('rolepermissionmanager.tables.permissions', 'acl_permissions') . ',id',
        ]);

        $role->update([
            'name'        => $validated['name'],
            'slug'        => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);
        AclRegistry::flushResourcesCache();

        return redirect()
            ->route('acl.roles.edit', $id)
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        $name = $role->name;

        $role->permissions()->detach();
        $role->delete();
        AclRegistry::flushResourcesCache();

        return redirect()
            ->route('acl.roles.index')
            ->with('success', "Role '{$name}' deleted successfully.");
    }
}
