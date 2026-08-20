<?php

namespace SalvatoreCervone\RolePermissionManager\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

/**
 * Trait HasAcl
 *
 * Apply this trait to your User model (or any Authenticatable model)
 * to integrate with the RolePermissionManager package.
 *
 * Usage: use HasAcl; in your User model class.
 */
trait HasAcl
{
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the roles assigned to this model.
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(
            config('rolepermissionmanager.models.role', Role::class),
            'model',
            config('rolepermissionmanager.tables.model_has_roles', 'acl_model_has_roles'),
            'model_id',
            'role_id'
        );
    }

    /**
     * Get the permissions directly assigned to this model (bypassing roles).
     */
    public function permissions(): BelongsToMany
    {
        return $this->morphToMany(
            config('rolepermissionmanager.models.permission', Permission::class),
            'model',
            config('rolepermissionmanager.tables.model_has_permissions', 'acl_model_has_permissions'),
            'model_id',
            'permission_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Role Management
    |--------------------------------------------------------------------------
    */

    /**
     * Assign one or more roles to this model.
     *
     * @param  string|array|Role  ...$roles  Role slugs or Role model instances.
     * @return $this
     */
    public function assignRole(string|array|Role ...$roles): static
    {
        $roleIds = $this->resolveRoleIds($roles);

        $this->roles()->syncWithoutDetaching($roleIds);
        $this->unsetRelation('roles');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Remove one or more roles from this model.
     *
     * @param  string|array|Role  ...$roles  Role slugs or Role model instances.
     * @return $this
     */
    public function removeRole(string|array|Role ...$roles): static
    {
        $roleIds = $this->resolveRoleIds($roles);

        $this->roles()->detach($roleIds);
        $this->unsetRelation('roles');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Sync the model's roles to exactly the given set.
     *
     * @param  string|array|Role  ...$roles  Role slugs or Role model instances.
     * @return $this
     */
    public function syncRoles(string|array|Role ...$roles): static
    {
        $roleIds = $this->resolveRoleIds($roles);

        $this->roles()->sync($roleIds);
        $this->unsetRelation('roles');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Determine if this model has the given role.
     *
     * @param  string|Role  $role  Role slug or Role model instance.
     */
    public function hasRole(string|Role $role): bool
    {
        $slug = $role instanceof Role ? $role->slug : $role;

        return $this->roles->contains('slug', $slug);
    }

    /**
     * Determine if this model has any of the given roles.
     *
     * @param  string|array  ...$roles  Role slugs.
     */
    public function hasAnyRole(string|array ...$roles): bool
    {
        $slugs = collect($roles)->flatten()->all();

        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Determine if this model has all of the given roles.
     *
     * @param  string|array  ...$roles  Role slugs.
     */
    public function hasAllRoles(string|array ...$roles): bool
    {
        $slugs = collect($roles)->flatten()->all();

        return $this->roles->whereIn('slug', $slugs)->count() === count($slugs);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Management
    |--------------------------------------------------------------------------
    */

    /**
     * Grant one or more direct permissions to this model.
     *
     * @param  string|array|Permission  ...$permissions  Permission slugs or Permission model instances.
     * @return $this
     */
    public function givePermissionTo(string|array|Permission ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->syncWithoutDetaching($permissionIds);
        $this->unsetRelation('permissions');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Revoke one or more direct permissions from this model.
     *
     * @param  string|array|Permission  ...$permissions  Permission slugs or Permission model instances.
     * @return $this
     */
    public function revokePermissionTo(string|array|Permission ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->detach($permissionIds);
        $this->unsetRelation('permissions');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Sync the model's direct permissions to exactly the given set.
     *
     * @param  string|array|Permission  ...$permissions  Permission slugs or Permission model instances.
     * @return $this
     */
    public function syncPermissions(string|array|Permission ...$permissions): static
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        $this->permissions()->sync($permissionIds);
        $this->unsetRelation('permissions');
        AclRegistry::flushUserCache($this->getKey());

        return $this;
    }

    /**
     * Determine if this model has the given permission (directly or via roles).
     *
     * @param  string|Permission  $permission  Permission slug or model instance.
     */
    public function hasPermission(string|Permission $permission): bool
    {
        $slug = $permission instanceof Permission ? $permission->slug : $permission;

        $allPermissions = AclRegistry::getUserPermissions($this);

        return in_array($slug, $allPermissions, true);
    }

    /**
     * Determine if this model has any of the given permissions.
     *
     * @param  string|array  ...$permissions  Permission slugs.
     */
    public function hasAnyPermission(string|array ...$permissions): bool
    {
        $slugs = collect($permissions)->flatten()->all();
        $allPermissions = AclRegistry::getUserPermissions($this);

        return !empty(array_intersect($slugs, $allPermissions));
    }

    /**
     * Determine if this model has all of the given permissions.
     *
     * @param  string|array  ...$permissions  Permission slugs.
     */
    public function hasAllPermissions(string|array ...$permissions): bool
    {
        $slugs = collect($permissions)->flatten()->all();
        $allPermissions = AclRegistry::getUserPermissions($this);

        return empty(array_diff($slugs, $allPermissions));
    }

    /*
    |--------------------------------------------------------------------------
    | Route Access Check
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if this model can access a specific route by name or signature.
     *
     * @param  string  $routeNameOrSignature  E.g., 'invoices.destroy' or 'DELETE:invoices/{id}'
     */
    public function canAccessRoute(string $routeNameOrSignature): bool
    {
        $rule = AclRegistry::getResourceRule($routeNameOrSignature, $routeNameOrSignature);

        if (!$rule) {
            return true; // Route is not ACL-managed.
        }

        if ($rule->is_public) {
            return true;
        }

        // Super Admin bypass.
        if ($this->isSuperAdmin()) {
            return true;
        }

        $requiredPermissions = $rule->permission_slugs ?? [];

        if (empty($requiredPermissions)) {
            return false; // No permissions configured = locked to SuperAdmin.
        }

        $userPermissions = AclRegistry::getUserPermissions($this);

        if (($rule->operator ?? 'OR') === 'AND') {
            return empty(array_diff($requiredPermissions, $userPermissions));
        }

        return !empty(array_intersect($requiredPermissions, $userPermissions));
    }

    /**
     * Determine if this model is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        $superAdminSlug = config('rolepermissionmanager.super_admin_role');

        if (!$superAdminSlug) {
            return false;
        }

        return $this->hasRole($superAdminSlug);
    }

    /**
     * Get all permission slugs this model possesses (from roles + direct).
     *
     * @return string[]
     */
    public function getAllPermissions(): array
    {
        return AclRegistry::getUserPermissions($this);
    }

    /*
    |--------------------------------------------------------------------------
    | Resolvers (Internal)
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve role arguments (slugs or model instances) into an array of IDs.
     */
    protected function resolveRoleIds(array $roles): array
    {
        $items = collect($roles)->flatten();

        $ids = [];
        $slugsToResolve = [];

        foreach ($items as $item) {
            if ($item instanceof Role) {
                $ids[] = $item->id;
            } elseif (is_numeric($item)) {
                $ids[] = (int) $item;
            } else {
                $slugsToResolve[] = $item;
            }
        }

        if (!empty($slugsToResolve)) {
            $roleModel = config('rolepermissionmanager.models.role', Role::class);
            $resolvedIds = $roleModel::whereIn('slug', $slugsToResolve)->pluck('id')->all();
            $ids = array_merge($ids, $resolvedIds);
        }

        return $ids;
    }

    /**
     * Resolve permission arguments (slugs or model instances) into an array of IDs.
     */
    protected function resolvePermissionIds(array $permissions): array
    {
        $items = collect($permissions)->flatten();

        $ids = [];
        $slugsToResolve = [];

        foreach ($items as $item) {
            if ($item instanceof Permission) {
                $ids[] = $item->id;
            } elseif (is_numeric($item)) {
                $ids[] = (int) $item;
            } else {
                $slugsToResolve[] = $item;
            }
        }

        if (!empty($slugsToResolve)) {
            $permissionModel = config('rolepermissionmanager.models.permission', Permission::class);
            $resolvedIds = $permissionModel::whereIn('slug', $slugsToResolve)->pluck('id')->all();
            $ids = array_merge($ids, $resolvedIds);
        }

        return $ids;
    }
}
