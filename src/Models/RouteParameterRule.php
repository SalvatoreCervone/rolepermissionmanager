<?php

namespace SalvatoreCervone\RolePermissionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RouteParameterRule extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_public'           => 'boolean',
        'is_super_admin_only' => 'boolean',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('rolepermissionmanager.tables.route_parameter_rules', 'acl_route_parameter_rules');
    }

    /**
     * The secured resource this parameter rule belongs to.
     */
    public function securedResource(): BelongsTo
    {
        return $this->belongsTo(
            config('rolepermissionmanager.models.secured_resource', SecuredResource::class),
            'secured_resource_id'
        );
    }

    /**
     * Get the permissions required for this specific parameter value.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('rolepermissionmanager.models.permission', Permission::class),
            config('rolepermissionmanager.tables.parameter_rule_has_permissions', 'acl_parameter_rule_has_permissions'),
            'parameter_rule_id',
            'permission_id'
        );
    }

    /**
     * Check if this parameter rule is public.
     */
    public function isPublic(): bool
    {
        return (bool) ($this->is_public ?? false);
    }

    /**
     * Check if this parameter rule is restricted exclusively to Super Admin.
     */
    public function isSuperAdminOnly(): bool
    {
        return (bool) ($this->is_super_admin_only ?? false);
    }
}
