<?php

namespace SalvatoreCervone\RolePermissionManager\Services;

use Illuminate\Support\Facades\Cache;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;

class AclRegistry
{
    /**
     * Cache key for the full resource map.
     */
    protected const RESOURCES_MAP_KEY = 'resources_map';

    /**
     * Cache key prefix for per-user permission sets.
     */
    protected const USER_PERMISSIONS_KEY = 'user_permissions_';

    /**
     * Get the configured cache store.
     */
    protected static function cache(): \Illuminate\Contracts\Cache\Repository
    {
        $store = config('rolepermissionmanager.cache.store');

        return Cache::store($store);
    }

    /**
     * Build the full cache key with configured prefix.
     */
    protected static function cacheKey(string $key): string
    {
        return config('rolepermissionmanager.cache.prefix', 'acl_') . $key;
    }

    /**
     * Get the cache TTL in seconds.
     */
    protected static function ttl(): int
    {
        return (int) config('rolepermissionmanager.cache.ttl', 86400);
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Map (Route Identifier -> Resource Rule)
    |--------------------------------------------------------------------------
    */

    /**
     * Get the resource rule for a given route identifier.
     *
     * Returns an object with: is_public, operator, permission_slugs[]
     * or null if the route is not registered in the ACL system.
     */
    public static function getResourceRule(?string $routeName, ?string $routeSignature = null): ?object
    {
        $map = static::getResourcesMap();

        // Try by route name first (preferred).
        if ($routeName && isset($map[$routeName])) {
            return (object) $map[$routeName];
        }

        // Fall back to method:uri signature.
        if ($routeSignature && isset($map[$routeSignature])) {
            return (object) $map[$routeSignature];
        }

        return null;
    }

    /**
     * Get or build the full resources map from cache.
     *
     * Structure: [identifier => [is_public, operator, permission_slugs]]
     */
    public static function getResourcesMap(): array
    {
        $cacheKey = static::cacheKey(static::RESOURCES_MAP_KEY);
        $ttl = static::ttl();

        $builder = function () {
            return static::buildResourcesMap();
        };

        if ($ttl > 0) {
            return static::cache()->remember($cacheKey, $ttl, $builder);
        }

        return static::cache()->rememberForever($cacheKey, $builder);
    }

    /**
     * Build the resources map from the database.
     *
     * Loads all active secured resources with their permissions in a single query.
     */
    protected static function buildResourcesMap(): array
    {
        $resourceModel = config(
            'rolepermissionmanager.models.secured_resource',
            SecuredResource::class
        );

        $resources = $resourceModel::with('permissions')
            ->where('is_deprecated', false)
            ->get();

        $map = [];

        foreach ($resources as $resource) {
            $entry = [
                'is_public'        => $resource->is_public,
                'operator'         => $resource->operator,
                'permission_slugs' => $resource->permissions->pluck('slug')->all(),
            ];

            $map[$resource->identifier] = $entry;
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | User Permissions Cache
    |--------------------------------------------------------------------------
    */

    /**
     * Get all permission slugs for a given user (from roles + direct permissions).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user  A model that uses HasAcl trait.
     */
    public static function getUserPermissions($user): array
    {
        $cacheKey = static::cacheKey(static::USER_PERMISSIONS_KEY . $user->getKey());
        $ttl = static::ttl();

        $builder = function () use ($user) {
            return static::buildUserPermissions($user);
        };

        if ($ttl > 0) {
            return static::cache()->remember($cacheKey, $ttl, $builder);
        }

        return static::cache()->rememberForever($cacheKey, $builder);
    }

    /**
     * Build the complete permission slug set for a user.
     */
    protected static function buildUserPermissions($user): array
    {
        $permissionsFromRoles = [];

        if (method_exists($user, 'roles')) {
            $permissionsFromRoles = $user->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('slug')
                ->all();
        }

        $directPermissions = [];

        if (method_exists($user, 'permissions')) {
            $directPermissions = $user->permissions()
                ->pluck('slug')
                ->all();
        }

        return array_values(array_unique(
            array_merge($permissionsFromRoles, $directPermissions)
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation
    |--------------------------------------------------------------------------
    */

    /**
     * Flush the entire ACL resources map cache.
     * Call this when roles, permissions, or resources are modified.
     */
    public static function flushResourcesCache(): void
    {
        static::cache()->forget(static::cacheKey(static::RESOURCES_MAP_KEY));
    }

    /**
     * Flush the cached permissions for a specific user.
     * Call this when the user's roles or direct permissions change.
     */
    public static function flushUserCache($userId): void
    {
        static::cache()->forget(static::cacheKey(static::USER_PERMISSIONS_KEY . $userId));
    }

    /**
     * Flush all ACL caches (resources map + all user permissions).
     * Note: This uses cache tags if supported, or flushes known keys.
     */
    public static function flushAll(): void
    {
        static::flushResourcesCache();

        // Flush all user permission caches.
        // Since we can't iterate all user IDs efficiently without tags,
        // we increment a version key to invalidate all user caches.
        $versionKey = static::cacheKey('version');
        $currentVersion = (int) static::cache()->get($versionKey, 0);
        static::cache()->forever($versionKey, $currentVersion + 1);
    }

    /**
     * Refresh the resources map cache (flush + rebuild).
     */
    public static function refreshCache(): void
    {
        static::flushResourcesCache();
        static::getResourcesMap(); // Rebuild immediately.
    }
}
