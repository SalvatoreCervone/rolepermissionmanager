<?php

namespace SalvatoreCervone\RolePermissionManager\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface PermissionInterface
{
    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany;

    /**
     * Get the secured resources protected by this permission.
     */
    public function securedResources(): BelongsToMany;

    /**
     * Find a permission by its slug.
     */
    public static function findBySlug(string $slug): ?self;
}
