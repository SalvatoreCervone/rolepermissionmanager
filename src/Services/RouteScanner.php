<?php

namespace SalvatoreCervone\RolePermissionManager\Services;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;

class RouteScanner
{
    protected Router $router;

    /**
     * Routes excluded by prefix.
     */
    protected array $excludedPrefixes;

    /**
     * Routes excluded by name.
     */
    protected array $excludedNames;

    /**
     * Summary of results after scanning.
     */
    protected array $summary = [
        'created'    => [],
        'updated'    => [],
        'deprecated' => [],
        'removed'    => [],
        'skipped'    => [],
    ];

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->excludedPrefixes = config('rolepermissionmanager.scanner.excluded_prefixes', []);
        $this->excludedNames = config('rolepermissionmanager.scanner.excluded_names', []);

        // Automatically exclude admin panel and API routes from scanning.
        $adminPrefix = config('rolepermissionmanager.admin_panel.prefix', 'acl-admin');
        if ($adminPrefix && !in_array($adminPrefix, $this->excludedPrefixes)) {
            $this->excludedPrefixes[] = $adminPrefix;
        }

        $apiPrefix = config('rolepermissionmanager.api.prefix', 'acl-api');
        if ($apiPrefix && !in_array($apiPrefix, $this->excludedPrefixes)) {
            $this->excludedPrefixes[] = $apiPrefix;
        }

        // Auto exclude workbench, storage, and framework internals
        $internalPrefixes = ['workbench', 'storage', 'sanctum', '_ignition', '_debugbar', 'telescope', 'horizon', 'livewire'];
        foreach ($internalPrefixes as $ip) {
            if (!in_array($ip, $this->excludedPrefixes)) {
                $this->excludedPrefixes[] = $ip;
            }
        }
    }

    /**
     * Scan all registered routes and sync them with the secured_resources table.
     *
     * @param  bool  $clean  Remove deprecated routes from the DB.
     * @param  bool  $autoPermissions  Auto-create permissions for new routes.
     * @return array Summary of the scan operation.
     */
    public function scan(bool $clean = false, bool $autoPermissions = false): array
    {
        $routes = $this->getFilteredRoutes();
        $scannedIdentifiers = [];

        foreach ($routes as $route) {
            $identifier = $this->resolveIdentifier($route);
            $scannedIdentifiers[] = $identifier;

            $this->syncResource($route, $identifier, $autoPermissions);
        }

        // Handle routes that exist in DB but no longer in code.
        $this->handleOrphanedResources($scannedIdentifiers, $clean);

        return $this->summary;
    }

    /**
     * Get all Laravel routes, filtered by exclusion rules.
     *
     * @return Route[]
     */
    protected function getFilteredRoutes(): array
    {
        $routes = $this->router->getRoutes()->getRoutes();
        $filtered = [];

        foreach ($routes as $route) {
            if ($this->shouldExclude($route)) {
                $this->summary['skipped'][] = $this->resolveIdentifier($route);
                continue;
            }

            $filtered[] = $route;
        }

        return $filtered;
    }

    /**
     * Determine if a route should be excluded from scanning.
     */
    protected function shouldExclude(Route $route): bool
    {
        $uri = $route->uri();
        $name = $route->getName();

        // Exclude by URI prefix or wildcard pattern
        foreach ($this->excludedPrefixes as $prefix) {
            if (Str::is($prefix, $uri) || Str::is($prefix . '/*', $uri) || Str::startsWith($uri, $prefix)) {
                return true;
            }
        }

        // Exclude by route name or wildcard pattern (e.g., "password.*", "workbench.*")
        if ($name) {
            foreach ($this->excludedNames as $pattern) {
                if (Str::is($pattern, $name)) {
                    return true;
                }
            }
        }

        // Exclude routes without a controller action (e.g., fallback/redirect routes).
        $action = $route->getActionName();
        if ($action === 'Closure') {
            // Allow closures only if they have a name.
            if (!$name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the unique identifier for a route.
     * Prefers the route name; falls back to METHOD:uri signature.
     */
    protected function resolveIdentifier(Route $route): string
    {
        $name = $route->getName();

        if ($name) {
            return $name;
        }

        // Fall back to signature: first HTTP method + uri.
        $methods = $route->methods();
        $method = $methods[0] ?? 'GET'; // Take the primary method.

        return $method . ':' . $route->uri();
    }

    /**
     * Resolve the controller action string for a route.
     */
    protected function resolveControllerAction(Route $route): string
    {
        $action = $route->getActionName();

        // For closure-based routes.
        if ($action === 'Closure') {
            return 'Closure';
        }

        return $action;
    }

    /**
     * Create or update a secured resource record for the given route.
     */
    protected function syncResource(Route $route, string $identifier, bool $autoPermissions): void
    {
        $methods = $route->methods();
        $primaryMethod = $methods[0] ?? 'GET';

        // Skip HEAD-only routes (they mirror GET).
        if ($primaryMethod === 'HEAD') {
            return;
        }

        $resourceModel = config(
            'rolepermissionmanager.models.secured_resource',
            SecuredResource::class
        );

        $existing = $resourceModel::where('identifier', $identifier)->first();

        $attributes = [
            'controller_action' => $this->resolveControllerAction($route),
            'method'            => $primaryMethod,
            'uri'               => $route->uri(),
            'is_deprecated'     => false,
        ];

        if ($existing) {
            // Update only traceable fields (don't override is_public or operator, they're user-managed).
            $existing->update($attributes);
            $this->summary['updated'][] = $identifier;
        } else {
            // Create new resource with default settings.
            $resource = $resourceModel::create(array_merge($attributes, [
                'identifier' => $identifier,
                'is_public'  => config('rolepermissionmanager.scanner.default_is_public', false),
                'operator'   => config('rolepermissionmanager.scanner.default_operator', 'OR'),
            ]));

            $this->summary['created'][] = $identifier;

            // Auto-create a permission with the same slug as the route name.
            if ($autoPermissions && $route->getName()) {
                $this->autoCreatePermission($resource, $route->getName());
            }
        }
    }

    /**
     * Auto-create a permission for a newly discovered route.
     */
    protected function autoCreatePermission(SecuredResource $resource, string $routeName): void
    {
        $permissionModel = config(
            'rolepermissionmanager.models.permission',
            Permission::class
        );

        // Derive module from route name prefix (e.g., "invoices.destroy" -> "invoices").
        $parts = explode('.', $routeName);
        $module = count($parts) > 1 ? $parts[0] : null;

        $permission = $permissionModel::findOrCreate(
            slug: $routeName,
            name: ucwords(str_replace(['.', '-', '_'], ' ', $routeName)),
            module: $module ? ucfirst($module) : null,
            description: "Auto-generated permission for route: {$routeName}"
        );

        // Attach the permission to the resource.
        $resource->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Handle routes that exist in the DB but are no longer present in the code.
     */
    protected function handleOrphanedResources(array $scannedIdentifiers, bool $clean): void
    {
        $resourceModel = config(
            'rolepermissionmanager.models.secured_resource',
            SecuredResource::class
        );

        $orphaned = $resourceModel::whereNotIn('identifier', $scannedIdentifiers)
            ->where('is_deprecated', false)
            ->get();

        foreach ($orphaned as $resource) {
            if ($clean) {
                $resource->delete();
                $this->summary['removed'][] = $resource->identifier;
            } else {
                $resource->update(['is_deprecated' => true]);
                $this->summary['deprecated'][] = $resource->identifier;
            }
        }
    }

    /**
     * Get the scan summary.
     */
    public function getSummary(): array
    {
        return $this->summary;
    }
}
