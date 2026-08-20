<?php

namespace SalvatoreCervone\RolePermissionManager\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface RoleInterface
{
    /**
     * Get the permissions associated with this role.
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its slug.
     */
    public static function findBySlug(string $slug): ?self;

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(string $permissionSlug): bool;

    /**
     * Grant the given permissions to the role.
     */
    public function givePermissionTo(string|array ...$permissions): self;

    /**
     * Revoke the given permissions from the role.
     */
    public function revokePermissionTo(string|array ...$permissions): self;
}
