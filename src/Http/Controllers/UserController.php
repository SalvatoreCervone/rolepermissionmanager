<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class UserController extends Controller
{
    /**
     * Resolve the configured User model class.
     */
    protected function getUserModelClass(): string
    {
        return config('rolepermissionmanager.models.user')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }

    /**
     * Instantiate a new User query.
     */
    protected function newUserQuery()
    {
        $modelClass = $this->getUserModelClass();
        return (new $modelClass)->newQuery();
    }

    /**
     * Display a listing of users with their assigned roles and permissions.
     */
    public function index(Request $request)
    {
        $searchableFields = config('rolepermissionmanager.users.searchable_fields', ['name', 'email']);
        $displayField = config('rolepermissionmanager.users.display_field', 'name');
        $secondaryField = config('rolepermissionmanager.users.secondary_field', 'email');
        $perPage = config('rolepermissionmanager.users.per_page', 25);

        $query = $this->newUserQuery()->with(['roles', 'permissions']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $index => $field) {
                    if ($index === 0) {
                        $q->where($field, 'like', "%{$search}%");
                    } else {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        if ($request->filled('role')) {
            $roleSlug = $request->get('role');
            $query->whereHas('roles', function ($q) use ($roleSlug) {
                $q->where('slug', $roleSlug);
            });
        }

        $users = $query->paginate($perPage)->appends($request->query());
        $roles = Role::orderBy('name')->get();

        return view('acl::users.index', compact(
            'users',
            'roles',
            'displayField',
            'secondaryField',
            'searchableFields'
        ));
    }

    /**
     * JSON Autocomplete endpoint for searching users dynamically.
     */
    public function search(Request $request): JsonResponse
    {
        $queryText = $request->get('q', '');
        if (empty($queryText)) {
            return response()->json([]);
        }

        $searchableFields = config('rolepermissionmanager.users.searchable_fields', ['name', 'email']);
        $displayField = config('rolepermissionmanager.users.display_field', 'name');
        $secondaryField = config('rolepermissionmanager.users.secondary_field', 'email');

        $query = $this->newUserQuery()->with('roles');

        $query->where(function ($q) use ($queryText, $searchableFields) {
            foreach ($searchableFields as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'like', "%{$queryText}%");
                } else {
                    $q->orWhere($field, 'like', "%{$queryText}%");
                }
            }
        });

        $results = $query->limit(10)->get()->map(function ($user) use ($displayField, $secondaryField) {
            return [
                'id'        => $user->getKey(),
                'label'     => $user->{$displayField} ?? "User #{$user->getKey()}",
                'sublabel'  => $secondaryField ? ($user->{$secondaryField} ?? '') : '',
                'roles'     => $user->roles->pluck('name')->all(),
                'edit_url'  => route('acl.users.edit', $user->getKey()),
            ];
        });

        return response()->json($results);
    }

    /**
     * Show the form for editing the specified user's roles and permissions.
     */
    public function edit($id)
    {
        $user = $this->newUserQuery()->with(['roles', 'permissions'])->findOrFail($id);

        $allRoles = Role::orderBy('name')->get();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        $displayField = config('rolepermissionmanager.users.display_field', 'name');
        $secondaryField = config('rolepermissionmanager.users.secondary_field', 'email');

        return view('acl::users.edit', compact(
            'user',
            'allRoles',
            'allPermissions',
            'displayField',
            'secondaryField'
        ));
    }

    /**
     * Update the specified user's roles and direct permissions.
     */
    public function update(Request $request, $id)
    {
        $user = $this->newUserQuery()->findOrFail($id);

        $rolesTable = config('rolepermissionmanager.tables.roles', 'acl_roles');
        $permissionsTable = config('rolepermissionmanager.tables.permissions', 'acl_permissions');

        $validated = $request->validate([
            'roles'         => 'nullable|array',
            'roles.*'       => "integer|exists:{$rolesTable},id",
            'permissions'   => 'nullable|array',
            'permissions.*' => "integer|exists:{$permissionsTable},id",
        ]);

        // Sync roles
        $selectedRoleIds = $validated['roles'] ?? [];
        $user->roles()->sync($selectedRoleIds);
        $user->unsetRelation('roles');

        // Sync direct permissions
        $selectedPermissionIds = $validated['permissions'] ?? [];
        $user->permissions()->sync($selectedPermissionIds);
        $user->unsetRelation('permissions');

        // Invalidate cached permissions for this user
        AclRegistry::flushUserCache($user->getKey());

        $displayField = config('rolepermissionmanager.users.display_field', 'name');
        $userName = $user->{$displayField} ?? "User #{$user->getKey()}";

        return redirect()
            ->route('acl.users.edit', $id)
            ->with('success', "Roles and permissions updated for '{$userName}'.");
    }
}
