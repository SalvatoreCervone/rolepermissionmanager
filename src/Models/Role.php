<?php

namespace SalvatoreCervone\RolePermissionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SalvatoreCervone\RolePermissionManager\Contracts\RoleInterface;

class Role extends Model implements RoleInterface
{
    protected $guarded = ['id'];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.roles', 'acl_roles');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the permissions associated with this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rolepermissionmanager.models.permission', Permission::class),
            config('rolepermissionmanager.tables.role_has_permissions', 'acl_role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * Get the users (models) that have this role.
     * This is a polymorphic many-to-many relationship.
     */
    public function users(): BelongsToMany
    {
        // Default to App\Models\User; consumers can override.
        return $this->morphedByMany(
            'App\Models\User',
            'model',
            config('rolepermissionmanager.tables.model_has_roles', 'acl_model_has_roles'),
            'role_id',
            'model_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finders
    |--------------------------------------------------------------------------
    */

    /**
     * Find a role by its slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Find a role by its slug or throw an exception.
     */
    public static function findBySlugOrFail(string $slug): self
    {
        return static::where('slug', $slug)->firstOrFail();
    }

    /**
     * Find or create a role by its slug.
     */
    public static function findOrCreate(string $slug, ?string $name = null, ?string $description = null): self
    {
        $role = static::findBySlug($slug);

        if ($role) {
            return $role;
        }

        return static::create([
            'slug'        => $slug,
            'name'        => $name ?? ucwords(str_replace(['-', '_'], ' ', $slug)),
            'description' => $description,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if the role has the given permission (by slug).
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }

    /**
     * Grant the given permissions to the role.
     *
     * @param  string|array  ...$permissions  Permission slugs.
     */
    public function givePermissionTo(string|array ...$permissions): self
    {
        $slugs = collect($permissions)->flatten()->all();

        $permissionModel = config('rolepermissionmanager.models.permission', Permission::class);
        $permissionIds = $permissionModel::whereIn('slug', $slugs)->pluck('id');

        $this->permissions()->syncWithoutDetaching($permissionIds);
        $this->unsetRelation('permissions');

        return $this;
    }

    /**
     * Revoke the given permissions from the role.
     *
     * @param  string|array  ...$permissions  Permission slugs.
     */
    public function revokePermissionTo(string|array ...$permissions): self
    {
        $slugs = collect($permissions)->flatten()->all();

        $permissionModel = config('rolepermissionmanager.models.permission', Permission::class);
        $permissionIds = $permissionModel::whereIn('slug', $slugs)->pluck('id');

        $this->permissions()->detach($permissionIds);
        $this->unsetRelation('permissions');

        return $this;
    }

    /**
     * Sync the role's permissions to exactly the given set.
     *
     * @param  string|array  ...$permissions  Permission slugs.
     */
    public function syncPermissions(string|array ...$permissions): self
    {
        $slugs = collect($permissions)->flatten()->all();

        $permissionModel = config('rolepermissionmanager.models.permission', Permission::class);
        $permissionIds = $permissionModel::whereIn('slug', $slugs)->pluck('id');

        $this->permissions()->sync($permissionIds);
        $this->unsetRelation('permissions');

        return $this;
    }
}
