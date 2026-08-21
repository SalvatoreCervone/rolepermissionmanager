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
        'is_public'           => 'boolean',
        'is_super_admin_only' => 'boolean',
        'is_deprecated'       => 'boolean',
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

    public const TYPE_ROUTE = 'route';
    public const TYPE_CUSTOM = 'custom';

    /*
    |--------------------------------------------------------------------------
    | Finders & Scopes
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
        return $query->where(function ($q) {
            $q->where('is_deprecated', false)->orWhereNull('is_deprecated');
        });
    }

    /**
     * Scope to only HTTP routes.
     */
    public function scopeRoutes($query)
    {
        return $query->where(function ($q) {
            $q->where('type', self::TYPE_ROUTE)->orWhereNull('type');
        });
    }

    /**
     * Scope to only custom resources (methods, services, UI elements).
     */
    public function scopeCustom($query)
    {
        return $query->where('type', self::TYPE_CUSTOM);
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
        return $query->where(function ($q) {
            $q->where('is_public', false)->orWhereNull('is_public');
        });
    }

    /**
     * Scope to resources accessible exclusively by Super Admin.
     */
    public function scopeSuperAdminOnly($query)
    {
        return $query->where('is_super_admin_only', true);
    }

    /**
     * Scope to resources not exclusively reserved for Super Admin.
     */
    public function scopeNotSuperAdminOnly($query)
    {
        return $query->where(function ($q) {
            $q->where('is_super_admin_only', false)->orWhereNull('is_super_admin_only');
        });
    }

    /**
     * Check if this resource is an HTTP route.
     */
    public function isRoute(): bool
    {
        return ($this->type ?? self::TYPE_ROUTE) === self::TYPE_ROUTE;
    }

    /**
     * Check if this resource is a custom resource.
     */
    public function isCustom(): bool
    {
        return ($this->type ?? self::TYPE_ROUTE) === self::TYPE_CUSTOM;
    }

    /**
     * Check if this resource is accessible exclusively by Super Admin.
     */
    public function isSuperAdminOnly(): bool
    {
        return (bool) ($this->is_super_admin_only ?? false);
    }

    /**
     * Check if this resource is public (no authentication required).
     */
    public function isPublic(): bool
    {
        return (bool) ($this->is_public ?? false);
    }

    /**
     * Check if this resource is protected (authentication + permissions required).
     */
    public function isProtected(): bool
    {
        return !$this->isPublic();
    }

    /**
     * Check if this resource has any permissions attached.
     */
    public function hasPermissions(): bool
    {
        return $this->permissions()->exists();
    }

    /**
     * Check if this resource is deprecated.
     */
    public function isDeprecated(): bool
    {
        return (bool) ($this->is_deprecated ?? false);
    }
}
