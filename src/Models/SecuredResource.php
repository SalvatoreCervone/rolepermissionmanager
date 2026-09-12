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
        'is_unconfigured'     => 'boolean',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.secured_resources', 'acl_secured_resources');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (SecuredResource $resource) {
            if ($resource->type === self::TYPE_CUSTOM) {
                if (empty($resource->method)) {
                    $resource->method = 'CUSTOM';
                }
                if (empty($resource->uri)) {
                    $resource->uri = $resource->identifier ?? 'custom';
                }
            }
        });
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

    /**
     * Get the parameter rules defined for this route.
     */
    public function parameterRules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            config('rolepermissionmanager.models.route_parameter_rule', RouteParameterRule::class),
            'secured_resource_id'
        );
    }

    /**
     * Extract placeholder variable names from the route URI (e.g. ['page', 'destinazione']).
     */
    public function getPlaceholders(): array
    {
        if (empty($this->uri)) {
            return [];
        }

        preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', $this->uri, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Check if this route has any dynamic placeholders.
     */
    public function hasPlaceholders(): bool
    {
        return !empty($this->getPlaceholders());
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
     * Scope to unconfigured (pending/locked) resources.
     */
    public function scopeUnconfigured($query)
    {
        return $query->where('is_unconfigured', true);
    }

    /**
     * Scope to configured resources.
     */
    public function scopeConfigured($query)
    {
        return $query->where(function ($q) {
            $q->where('is_unconfigured', false)->orWhereNull('is_unconfigured');
        });
    }

    /**
     * Check if this resource is unconfigured and locked.
     */
    public function isUnconfigured(): bool
    {
        return (bool) $this->is_unconfigured;
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
     * Scope to resources accessible by any authenticated user without special permissions.
     */
    public function scopeAuthenticatedOnly($query)
    {
        return $query->where('is_public', false)
            ->where(function ($q) {
                $q->where('is_super_admin_only', false)->orWhereNull('is_super_admin_only');
            })
            ->where(function ($q) {
                $q->where('is_unconfigured', false)->orWhereNull('is_unconfigured');
            })
            ->doesntHave('permissions');
    }

    /**
     * Scope to resources protected by specific permissions.
     */
    public function scopeProtectedWithPermissions($query)
    {
        return $query->where('is_public', false)
            ->where(function ($q) {
                $q->where('is_super_admin_only', false)->orWhereNull('is_super_admin_only');
            })
            ->where(function ($q) {
                $q->where('is_unconfigured', false)->orWhereNull('is_unconfigured');
            })
            ->has('permissions');
    }

    /**
     * Check if this resource requires authentication only (no special permissions, not public, not super admin, not unconfigured).
     */
    public function isAuthenticatedOnly(): bool
    {
        if ($this->isPublic() || $this->isSuperAdminOnly() || $this->isUnconfigured()) {
            return false;
        }

        return $this->relationLoaded('permissions')
            ? $this->permissions->isEmpty()
            : !$this->hasPermissions();
    }

    /**
     * Check if this resource is protected with specific permissions.
     */
    public function isProtectedWithPermissions(): bool
    {
        if ($this->isPublic() || $this->isSuperAdminOnly() || $this->isUnconfigured()) {
            return false;
        }

        return $this->relationLoaded('permissions')
            ? $this->permissions->isNotEmpty()
            : $this->hasPermissions();
    }

    /**
     * Check if this resource is deprecated.
     */
    public function isDeprecated(): bool
    {
        return (bool) ($this->is_deprecated ?? false);
    }
}
