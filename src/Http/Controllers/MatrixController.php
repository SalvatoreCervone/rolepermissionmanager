<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class MatrixController extends Controller
{
    /**
     * Display the Role-Permission Matrix.
     */
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        // Build quick lookup matrix: [role_id][permission_id] => bool
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->id] = $role->permissions->pluck('id')->flip()->toArray();
        }

        return view('acl::matrix.index', compact('roles', 'allPermissions', 'matrix'));
    }

    /**
     * AJAX Toggle permission for a role.
     */
    public function toggle(Request $request): JsonResponse
    {
        $rolesTable = config('rolepermissionmanager.tables.roles', 'acl_roles');
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');

        $validated = $request->validate([
            'role_id'       => "required|integer|exists:{$rolesTable},id",
            'permission_id' => "required|integer|exists:{$permissionsTable},id",
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permissionId = $validated['permission_id'];

        $isAttached = $role->permissions()->where('permission_id', $permissionId)->exists();

        if ($isAttached) {
            $role->permissions()->detach($permissionId);
            $action = 'detached';
        } else {
            $role->permissions()->attach($permissionId);
            $action = 'attached';
        }

        AclRegistry::refreshCache();

        return response()->json([
            'success' => true,
            'action'  => $action,
            'role_id' => $role->id,
            'perm_id' => $permissionId,
        ]);
    }
}
