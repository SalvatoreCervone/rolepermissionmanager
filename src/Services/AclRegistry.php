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
     * Determine if a user has access to a specific resource (route or custom identifier).
     *
     * @param  string  $identifier  The resource identifier (e.g. 'routes.users.index', 'CorsoController@dettagliocorsi', 'export.excel')
     * @param  mixed   $user        The authenticatable user (defaults to auth()->user())
     */
    public static function hasAccess(string $identifier, mixed $user = null): bool
    {
        $rule = static::getResourceRule($identifier);

        // If not registered in ACL system
        if (!$rule) {
            $behavior = config('rolepermissionmanager.middleware.unprotected_behavior', 'allow');
            return $behavior === 'allow';
        }

        // Public resources are accessible by anyone
        if ($rule->is_public) {
            return true;
        }

        $guard = config('rolepermissionmanager.middleware.guard');
        $user = $user ?? auth($guard)->user();

        if (!$user) {
            return false;
        }

        // Super Admin bypass
        $superAdminSlug = config('rolepermissionmanager.super_admin_role');
        if ($superAdminSlug && method_exists($user, 'hasRole') && $user->hasRole($superAdminSlug)) {
            return true;
        }

        $requiredPermissions = $rule->permission_slugs ?? [];
        if (empty($requiredPermissions)) {
            $unassignedBehavior = config('rolepermissionmanager.middleware.unassigned_permissions_behavior', 'allow');
            return $unassignedBehavior === 'allow';
        }

        $userPermissions = static::getUserPermissions($user);
        $operator = $rule->operator ?? 'OR';

        if ($operator === 'AND') {
            return empty(array_diff($requiredPermissions, $userPermissions));
        }

        return !empty(array_intersect($requiredPermissions, $userPermissions));
    }

    /**
     * Authorize access to a resource for the user. Throws UnauthorizedException if denied.
     *
     * @param  string  $identifier  The resource identifier
     * @param  mixed   $user        The authenticatable user
     * @throws \SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException
     */
    public static function authorize(string $identifier, mixed $user = null): void
    {
        $guard = config('rolepermissionmanager.middleware.guard');
        $user = $user ?? auth($guard)->user();

        if (!static::hasAccess($identifier, $user)) {
            if (!$user) {
                throw \SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException::notLoggedIn();
            }

            $rule = static::getResourceRule($identifier);
            if ($rule && !empty($rule->permission_slugs)) {
                throw \SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException::forPermissions($rule->permission_slugs);
            }

            throw \SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException::forResource($identifier);
        }
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
     * Cache key for active scanner rules.
     */
    protected const SCANNER_RULES_KEY = 'scanner_rules';

    /**
     * Get all active scanner rules (cached).
     *
     * @return array{excludes: array{prefixes: array, names: array}, includes: array{prefixes: array, names: array}}
     */
    public static function getScannerRules(): array
    {
        $key = static::cacheKey(static::SCANNER_RULES_KEY);

        return static::cache()->remember($key, static::ttl(), function () {
            $ruleModel = config('rolepermissionmanager.models.scanner_rule', \SalvatoreCervone\RolePermissionManager\Models\ScannerRule::class);

            $rules = [
                'excludes' => ['prefixes' => [], 'names' => []],
                'includes' => ['prefixes' => [], 'names' => []],
            ];

            if (!class_exists($ruleModel)) {
                return $rules;
            }

            try {
                $dbRules = $ruleModel::active()->get();
                foreach ($dbRules as $r) {
                    $bucket = $r->type === 'include' ? 'includes' : 'excludes';
                    $target = $r->target === 'prefix' ? 'prefixes' : 'names';
                    $rules[$bucket][$target][] = $r->pattern;
                }
            } catch (\Throwable $e) {
                // Table might not exist yet during migration
            }

            return $rules;
        });
    }

    /**
     * Flush the scanner rules cache.
     */
    public static function flushScannerRulesCache(): void
    {
        static::cache()->forget(static::cacheKey(static::SCANNER_RULES_KEY));
    }

    /**
     * Flush the entire ACL resources map cache.
     * Call this when roles, permissions, or resources are modified.
     */
    public static function flushResourcesCache(): void
    {
        static::cache()->forget(static::cacheKey(static::RESOURCES_MAP_KEY));
        static::flushScannerRulesCache();
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
        static::flushScannerRulesCache();

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
        static::flushScannerRulesCache();
        static::getResourcesMap(); // Rebuild immediately.
        static::getScannerRules();
    }

    /**
     * Filter a hierarchical navigation menu array based on a user's permissions and roles.
     *
     * Supports nested items ('items', 'children', 'subitems') and permission/role keys
     * ('permessi', 'permissions', 'permission', 'ruoli', 'roles', 'role', 'can', 'route').
     *
     * @param  array  $menu  Hierarchical menu items.
     * @param  mixed  $user  User model or null (falls back to auth()->user()).
     * @return array  Filtered menu array with sequential indexing preserved for frontend frameworks.
     */
    public static function filterMenu(array $menu, mixed $user = null): array
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return [];
        }

        // Super Admin bypass: can see everything.
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return array_values($menu);
        }

        $filtered = [];

        foreach ($menu as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Detect children key ('items', 'children', 'subitems')
            $childrenKey = null;
            foreach (['items', 'children', 'subitems'] as $ck) {
                if (isset($item[$ck]) && is_array($item[$ck])) {
                    $childrenKey = $ck;
                    break;
                }
            }

            // Recursively filter children if present
            $hasFilteredChildren = false;
            if ($childrenKey) {
                $filteredChildren = static::filterMenu($item[$childrenKey], $user);
                $item[$childrenKey] = $filteredChildren;
                $hasFilteredChildren = !empty($filteredChildren);
            }

            // Check access for this item
            $isAuthorized = static::checkMenuItemAccess($item, $user);

            // Item with children: keep only if parent is authorized AND has at least one accessible child
            if ($childrenKey) {
                if ($isAuthorized && $hasFilteredChildren) {
                    $filtered[] = $item;
                }
            } elseif ($isAuthorized) {
                $filtered[] = $item;
            }
        }

        return array_values($filtered);
    }

    /**
     * Check if a user is authorized for a specific menu item.
     */
    protected static function checkMenuItemAccess(array $item, mixed $user): bool
    {
        // 1. Explicit permissions ('permessi', 'permissions', 'permission')
        $perms = $item['permessi'] ?? $item['permissions'] ?? $item['permission'] ?? null;
        if ($perms !== null) {
            $perms = (array) $perms;
            if (!empty($perms)) {
                $operator = strtoupper($item['operator'] ?? 'OR');
                $userPermissions = method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()
                    : (method_exists($user, 'permissions') ? $user->permissions->pluck('slug')->all() : []);

                if ($operator === 'AND') {
                    $hasPerms = empty(array_diff($perms, $userPermissions));
                } else {
                    $hasPerms = !empty(array_intersect($perms, $userPermissions));
                }

                if (!$hasPerms) {
                    return false;
                }
            }
        }

        // 2. Explicit roles ('ruoli', 'roles', 'role')
        $roles = $item['ruoli'] ?? $item['roles'] ?? $item['role'] ?? null;
        if ($roles !== null) {
            $roles = (array) $roles;
            if (!empty($roles)) {
                $roleOperator = strtoupper($item['role_operator'] ?? 'OR');
                if (method_exists($user, 'hasAnyRole')) {
                    if ($roleOperator === 'AND' && method_exists($user, 'hasAllRoles')) {
                        if (!$user->hasAllRoles($roles)) {
                            return false;
                        }
                    } elseif (!$user->hasAnyRole($roles)) {
                        return false;
                    }
                }
            }
        }

        // 3. Check 'can' via Gate
        if (isset($item['can']) && is_string($item['can'])) {
            if (method_exists($user, 'can') && !$user->can($item['can'])) {
                return false;
            }
        }

        // 4. Check route or url access if 'route', 'url', or 'to' is specified
        $target = $item['route'] ?? $item['url'] ?? $item['to'] ?? null;
        if ($target && is_string($target) && method_exists($user, 'canAccessRoute')) {
            // Ignore external URLs or anchors
            if (!\Illuminate\Support\Str::startsWith($target, ['http://', 'https://', '#', 'javascript:', 'mailto:', 'tel:'])) {
                if (!$user->canAccessRoute($target)) {
                    return false;
                }
            }
        }

        return true;
    }
}

