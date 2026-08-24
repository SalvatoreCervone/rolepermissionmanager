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
     * Check if a user is currently deactivated.
     *
     * @param  mixed  $user
     */
    public static function isUserDeactivated($user): bool
    {
        if (!$user) {
            return false;
        }

        if (!empty($user->deleted_at)) {
            return true;
        }

        $marker = config('rolepermissionmanager.users.deactivation_marker', 'Deactivated');
        if (isset($user->password) && $user->password === $marker) {
            return true;
        }

        return false;
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
        $defaultPerPage = (int) ($modelConfig['per_page'] ?? config('rolepermissionmanager.users.per_page', 25));
        $perPageParam = $request->get('per_page');
        $perPage = $perPageParam === 'all' ? 10000 : (in_array((int)$perPageParam, [25, 50, 100]) ? (int)$perPageParam : $defaultPerPage);

        $modelInstance = new $modelClass;
        $schema = $modelInstance->getConnection()->getSchemaBuilder();
        $hasDeletedAt = $schema->hasColumn($modelInstance->getTable(), 'deleted_at');

        $query = (new $modelClass)->newQuery()->with(['roles', 'permissions']);
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

        if ($request->filled('status')) {
            $status = $request->get('status');
            $marker = config('rolepermissionmanager.users.deactivation_marker', 'Deactivated');
            if ($status === 'active') {
                if ($hasDeletedAt) {
                    $query->whereNull('deleted_at');
                }
                $query->where(function ($q) use ($marker) {
                    $q->where('password', '!=', $marker)->orWhereNull('password');
                });
            } elseif ($status === 'deactivated') {
                $query->where(function ($q) use ($hasDeletedAt, $marker) {
                    if ($hasDeletedAt) {
                        $q->whereNotNull('deleted_at');
                    }
                    $q->orWhere('password', $marker);
                });
            }
        }

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

        if ($request->filled('permission')) {
            $permission = $request->get('permission');
            if ($permission === 'none') {
                $query->whereDoesntHave('permissions')->whereDoesntHave('roles.permissions');
            } elseif ($permission === 'has_any') {
                $query->where(function ($q) {
                    $q->whereHas('permissions')->orWhereHas('roles.permissions');
                });
            } else {
                $query->where(function ($q) use ($permission) {
                    $q->whereHas('permissions', function ($p) use ($permission) {
                        $p->where('id', $permission)->orWhere('slug', $permission);
                    })->orWhereHas('roles.permissions', function ($p) use ($permission) {
                        $p->where('id', $permission)->orWhere('slug', $permission);
                    });
                });
            }
        }

        $users = $query->paginate($perPage)->appends($request->query());
        $roles = Role::orderBy('name')->get();
        $allPermissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('acl::users.index', compact(
            'users',
            'roles',
            'allPermissions',
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
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

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

        $query = (new $modelClass)->newQuery()->with(['roles', 'permissions']);
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }
        $user = $query->findOrFail($id);

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

        $query = (new $modelClass)->newQuery();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }
        $user = $query->findOrFail($id);

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

    /**
     * Reset the password for the specified user.
     */
    public function resetPassword(Request $request, $id)
    {
        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);
        $modelClass = $modelConfig['model'];

        $query = (new $modelClass)->newQuery();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }
        $user = $query->findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $schema = $user->getConnection()->getSchemaBuilder();
        $table = $user->getTable();

        if ($schema->hasColumn($table, 'password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        if ($schema->hasColumn($table, 'remember_token')) {
            $user->remember_token = null;
        }

        $user->save();

        $displayField = $modelConfig['display_field'] ?? 'name';
        $userName = static::formatFieldValue($user, $displayField) ?: "User #{$user->getKey()}";

        AuditLogger::log('user_password_reset', $modelConfig['label'] ?? 'User', $userName, "Password reset for user '{$userName}'");

        return redirect()->back()->with('success', __('acl::users.password_reset_success', ['name' => $userName]));
    }

    /**
     * Deactivate the specified user.
     */
    public function deactivate(Request $request, $id)
    {
        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);
        $modelClass = $modelConfig['model'];

        $query = (new $modelClass)->newQuery();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }
        $user = $query->findOrFail($id);

        $marker = config('rolepermissionmanager.users.deactivation_marker', 'Deactivated');
        $schema = $user->getConnection()->getSchemaBuilder();
        $table = $user->getTable();

        // Substitute plain non-hashed marker in password & remember_token
        if ($schema->hasColumn($table, 'password')) {
            $user->password = $marker;
        }
        if ($schema->hasColumn($table, 'remember_token')) {
            $user->remember_token = $marker;
        }
        if ($schema->hasColumn($table, 'deleted_at')) {
            $user->deleted_at = now();
        }

        // Revoke active API/Sanctum tokens if supported
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $user->save();

        // Flush user cache
        AclRegistry::flushUserCache($user->getKey());

        $displayField = $modelConfig['display_field'] ?? 'name';
        $userName = static::formatFieldValue($user, $displayField) ?: "User #{$user->getKey()}";

        AuditLogger::log('user_deactivated', $modelConfig['label'] ?? 'User', $userName, "Deactivated user '{$userName}' and set deleted_at");

        return redirect()->back()->with('success', __('acl::users.deactivated_success', ['name' => $userName]));
    }

    /**
     * Reactivate the specified user.
     */
    public function activate(Request $request, $id)
    {
        $modelKey = $request->get('model');
        $modelConfig = AclRegistry::getUserModelConfig($modelKey);
        $modelClass = $modelConfig['model'];

        $query = (new $modelClass)->newQuery();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }
        $user = $query->findOrFail($id);

        $validated = $request->validate([
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $schema = $user->getConnection()->getSchemaBuilder();
        $table = $user->getTable();

        if ($schema->hasColumn($table, 'deleted_at')) {
            $user->deleted_at = null;
        }
        if ($schema->hasColumn($table, 'remember_token')) {
            $user->remember_token = null;
        }
        if (!empty($validated['password']) && $schema->hasColumn($table, 'password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->save();

        // Flush user cache
        AclRegistry::flushUserCache($user->getKey());

        $displayField = $modelConfig['display_field'] ?? 'name';
        $userName = static::formatFieldValue($user, $displayField) ?: "User #{$user->getKey()}";

        AuditLogger::log('user_activated', $modelConfig['label'] ?? 'User', $userName, "Reactivated user '{$userName}' and cleared deleted_at");

        return redirect()->back()->with('success', __('acl::users.activated_success', ['name' => $userName]));
    }
}
