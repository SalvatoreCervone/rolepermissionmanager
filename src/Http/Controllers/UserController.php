<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\AuditLogger;

class UserController extends Controller
{
    /**
     * Format display values for single or multi-column configurations.
     *
     * @param  mixed  $user
     * @param  string|array|null  $field
     */
    public static function formatFieldValue($user, $field): string
    {
        if (empty($field) || !$user) {
            return '';
        }

        if (is_array($field)) {
            $parts = [];
            foreach ($field as $f) {
                $val = $user->{$f} ?? null;
                if (!is_null($val) && $val !== '') {
                    $parts[] = $val;
                }
            }
            return !empty($parts) ? implode(' ', $parts) : "ID #{$user->getKey()}";
        }

        return (string) ($user->{$field} ?? "ID #{$user->getKey()}");
    }

    /**
     * Format table header label for single or multi-column configurations.
     *
     * @param  string|array|null  $field
     */
    public static function formatFieldHeader($field): string
    {
        if (empty($field)) {
            return '';
        }

        if (is_array($field)) {
            return implode(' / ', array_map(fn($f) => ucfirst(str_replace(['_', '-'], ' ', $f)), $field));
        }

        return ucfirst(str_replace(['_', '-'], ' ', (string) $field));
    }

    /**
     * Display a listing of users with their assigned roles and permissions.
     */
    public function index(Request $request)
    {
        $allModels = AclRegistry::getUserModelsConfig();
        $modelKey = $request->get('model', array_key_first($allModels));
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);

        $modelClass = $modelConfig['model'];
        $searchableFields = (array) ($modelConfig['searchable_fields'] ?? ['name', 'email']);
        $displayField = $modelConfig['display_field'] ?? 'name';
        $secondaryField = $modelConfig['secondary_field'] ?? 'email';
        $perPage = config('rolepermissionmanager.users.per_page', 25);

        $query = (new $modelClass)->newQuery()->with(['roles', 'permissions']);

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
            'searchableFields',
            'allModels',
            'modelKey',
            'modelConfig'
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

        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);

        $modelClass = $modelConfig['model'];
        $searchableFields = (array) ($modelConfig['searchable_fields'] ?? ['name', 'email']);
        $displayField = $modelConfig['display_field'] ?? 'name';
        $secondaryField = $modelConfig['secondary_field'] ?? 'email';

        $query = (new $modelClass)->newQuery()->with('roles');

        $query->where(function ($q) use ($queryText, $searchableFields) {
            foreach ($searchableFields as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'like', "%{$queryText}%");
                } else {
                    $q->orWhere($field, 'like', "%{$queryText}%");
                }
            }
        });

        $results = $query->limit(10)->get()->map(function ($user) use ($displayField, $secondaryField, $modelConfig) {
            return [
                'id'        => $user->getKey(),
                'label'     => static::formatFieldValue($user, $displayField),
                'sublabel'  => static::formatFieldValue($user, $secondaryField),
                'roles'     => $user->roles->pluck('name')->all(),
                'edit_url'  => route('acl.users.edit', ['id' => $user->getKey(), 'model' => $modelConfig['key']]),
            ];
        });

        return response()->json($results);
    }

    /**
     * Show the form for editing the specified user's roles and permissions.
     */
    public function edit(Request $request, $id)
    {
        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);
        $modelClass = $modelConfig['model'];

        $user = (new $modelClass)->newQuery()->with(['roles', 'permissions'])->findOrFail($id);

        $allRoles = Role::orderBy('name')->get();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        $displayField = $modelConfig['display_field'] ?? 'name';
        $secondaryField = $modelConfig['secondary_field'] ?? 'email';

        return view('acl::users.edit', compact(
            'user',
            'allRoles',
            'allPermissions',
            'displayField',
            'secondaryField',
            'modelKey',
            'modelConfig'
        ));
    }

    /**
     * Update the specified user's roles and direct permissions.
     */
    public function update(Request $request, $id)
    {
        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);
        $modelClass = $modelConfig['model'];

        $user = (new $modelClass)->newQuery()->findOrFail($id);

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

        $displayField = $modelConfig['display_field'] ?? 'name';
        $userName = static::formatFieldValue($user, $displayField);

        $rolesCount = count($selectedRoleIds);
        $permsCount = count($selectedPermissionIds);
        AuditLogger::log('user_acl_updated', $modelConfig['label'] ?? 'User', $userName, "Assigned {$rolesCount} roles and {$permsCount} direct permissions to {$userName}");

        $redirectParams = ['id' => $id];
        $allModels = AclRegistry::getUserModelsConfig();
        if (count($allModels) > 1 && $modelKey) {
            $redirectParams['model'] = $modelConfig['key'];
        }

        return redirect()
            ->route('acl.users.edit', $redirectParams)
            ->with('success', __('acl::users.updated_success', ['name' => $userName]));
    }
}
