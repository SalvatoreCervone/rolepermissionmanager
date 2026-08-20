<?php

namespace SalvatoreCervone\RolePermissionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SecuredResource extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_public'     => 'boolean',
        'is_deprecated' => 'boolean',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the permissions that protect this resource.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rolepermissionmanager.models.permission', Permission::class),
            config('rolepermissionmanager.tables.permission_has_resources', 'acl_permission_has_resources'),
            'secured_resource_id',
            'permission_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Finders
    |--------------------------------------------------------------------------
    */

    /**
     * Find a secured resource by its identifier.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        return static::where('identifier', $identifier)->first();
    }

    /**
     * Find active (non-deprecated) secured resources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_deprecated', false);
    }

    /**
     * Find public secured resources.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Find protected (non-public) secured resources.
     */
    public function scopeProtected($query)
    {
        return $query->where('is_public', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the permission slugs required for this resource.
     */
    public function getRequiredPermissionSlugs(): array
    {
        return $this->permissions->pluck('slug')->all();
    }

    /**
     * Check if a user satisfies the permission requirements for this resource.
     *
     * @param  array  $userPermissionSlugs  The slugs of permissions the user possesses.
     */
    public function isAccessibleBy(array $userPermissionSlugs): bool
    {
        $requiredSlugs = $this->getRequiredPermissionSlugs();

        // If no permissions are required, only SuperAdmin can access (handled in middleware).
        if (empty($requiredSlugs)) {
            return false;
        }

        if ($this->operator === 'AND') {
            // User must have ALL required permissions.
            return empty(array_diff($requiredSlugs, $userPermissionSlugs));
        }

        // OR: User needs at least ONE of the required permissions.
        return !empty(array_intersect($requiredSlugs, $userPermissionSlugs));
    }
}
