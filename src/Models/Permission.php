<?php

namespace SalvatoreCervone\RolePermissionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SalvatoreCervone\RolePermissionManager\Contracts\PermissionInterface;

class Permission extends Model implements PermissionInterface
{
    protected $guarded = ['id'];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.permissions', 'acl_permissions');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rolepermissionmanager.models.role', Role::class),
            config('rolepermissionmanager.tables.role_has_permissions', 'acl_role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * Get the secured resources protected by this permission.
     */
    public function securedResources(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rolepermissionmanager.models.secured_resource', SecuredResource::class),
            config('rolepermissionmanager.tables.permission_has_resources', 'acl_permission_has_resources'),
            'permission_id',
            'secured_resource_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finders
    |--------------------------------------------------------------------------
    */

    /**
     * Find a permission by its slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Find a permission by its slug or throw an exception.
     */
    public static function findBySlugOrFail(string $slug): self
    {
        return static::where('slug', $slug)->firstOrFail();
    }

    /**
     * Find or create a permission by its slug.
     */
    public static function findOrCreate(
        string $slug,
        ?string $name = null,
        ?string $module = null,
        ?string $description = null
    ): self {
        $permission = static::findBySlug($slug);

        if ($permission) {
            return $permission;
        }

        return static::create([
            'slug'        => $slug,
            'name'        => $name ?? ucwords(str_replace(['-', '_', '.'], ' ', $slug)),
            'module'      => $module,
            'description' => $description,
        ]);
    }
}
