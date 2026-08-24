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
     * Routes included by prefix (whitelist).
     */
    protected array $includedPrefixes;

    /**
     * Routes included by name (whitelist).
     */
    protected array $includedNames;

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
        $this->includedPrefixes = config('rolepermissionmanager.scanner.included_prefixes', []);
        $this->includedNames = config('rolepermissionmanager.scanner.included_names', []);
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
     * Load custom route files configured in config if they exist.
     */
    protected function loadCustomRouteFiles(): void
    {
        $files = config('rolepermissionmanager.scanner.route_files', []);

        foreach ($files as $file) {
            $path = Str::startsWith($file, '/') ? $file : (function_exists('base_path') ? base_path($file) : $file);
            if (file_exists($path)) {
                require_once $path;
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
        $this->loadCustomRouteFiles();
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
        return $this->getExclusionReason($route) !== null;
    }

    /**
     * Get the exclusion reason for a route, or null if the route should be included.
     */
    public function getExclusionReason(Route $route): ?string
    {
        $uri = $route->uri();
        $name = $route->getName();

        $dynamicRules = AclRegistry::getScannerRules();

        // 1. Dynamic Inclusions (Override exclusions and force inclusion)
        if ($name) {
            foreach ($dynamicRules['includes']['names'] as $pattern) {
                if (Str::is($pattern, $name)) {
                    return null;
                }
            }
        }
        foreach ($dynamicRules['includes']['prefixes'] as $prefix) {
            $cleanPrefix = trim($prefix, '/');
            $cleanUri = trim($uri, '/');
            if ($cleanUri === $cleanPrefix || Str::startsWith($cleanUri, $cleanPrefix . '/') || Str::is($prefix, $uri)) {
                return null;
            }
        }

        // 2. Config Whitelist (if explicitly defined and non-empty)
        if (!empty($this->includedNames) || !empty($this->includedPrefixes)) {
            $matchesWhitelist = false;

            if ($name) {
                foreach ($this->includedNames as $pattern) {
                    if (Str::is($pattern, $name)) {
                        $matchesWhitelist = true;
                        break;
                    }
                }
            }

            if (!$matchesWhitelist) {
                foreach ($this->includedPrefixes as $prefix) {
                    $cleanPrefix = trim($prefix, '/');
                    $cleanUri = trim($uri, '/');
                    if ($cleanUri === $cleanPrefix || Str::startsWith($cleanUri, $cleanPrefix . '/') || Str::is($prefix, $uri)) {
                        $matchesWhitelist = true;
                        break;
                    }
                }
            }

            if (!$matchesWhitelist) {
                return 'Not in config whitelist';
            }
        }

        // 3. Dynamic DB Exclusions + Config Exclusions by URI prefix
        $allPrefixes = array_merge($this->excludedPrefixes, $dynamicRules['excludes']['prefixes']);
        foreach ($allPrefixes as $prefix) {
            $cleanPrefix = trim($prefix, '/');
            $cleanUri = trim($uri, '/');
            if ($cleanUri === $cleanPrefix || Str::startsWith($cleanUri, $cleanPrefix . '/') || Str::is($prefix, $uri)) {
                return "Excluded prefix: {$prefix}";
            }
        }

        // 4. Dynamic DB Exclusions + Config Exclusions by Route name
        $allNames = array_merge($this->excludedNames, $dynamicRules['excludes']['names']);
        if ($name) {
            foreach ($allNames as $pattern) {
                if (Str::is($pattern, $name)) {
                    return "Excluded route name: {$pattern}";
                }
            }
        }

        // 5. Exclude fallback routes (e.g., Route::fallback()).
        if (method_exists($route, 'isFallback') && $route->isFallback()) {
            return 'Fallback route';
        }

        return null;
    }

    /**
     * Get all skipped routes with their details and exclusion reasons.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSkippedRoutes(): \Illuminate\Support\Collection
    {
        $this->loadCustomRouteFiles();
        $routes = $this->router->getRoutes()->getRoutes();
        $skipped = collect();

        foreach ($routes as $route) {
            $reason = $this->getExclusionReason($route);
            if ($reason !== null) {
                $methods = $route->methods();
                $primaryMethod = $methods[0] ?? 'GET';
                if ($primaryMethod === 'HEAD') {
                    continue;
                }

                $skipped->push((object) [
                    'identifier'        => $this->resolveIdentifier($route),
                    'method'            => $primaryMethod,
                    'uri'               => $route->uri(),
                    'controller_action' => $this->resolveControllerAction($route),
                    'source_file'       => $this->resolveSourceFile($route),
                    'reason'            => $reason,
                    'is_skipped'        => true,
                ]);
            }
        }

        return $skipped;
    }

    /**
     * Cache of route file contents for fast lookup.
     */
    protected array $routeFilesCache = [];

    /**
     * Resolve the source file where this route was defined.
     */
    public function resolveSourceFile(Route $route): ?string
    {
        // 1. If it's a Closure, reflection gives the exact file immediately.
        $uses = $route->getAction('uses');
        if ($uses instanceof \Closure) {
            $ref = new \ReflectionFunction($uses);
            $fileName = $ref->getFileName();
            if ($fileName && file_exists($fileName)) {
                return $this->formatRelativePath($fileName);
            }
        }

        // 2. Scan route files in routes/ directory and any custom configured files.
        $this->loadRouteFilesCache();

        $name = $route->getName();
        $uri = $route->uri();
        $action = $route->getActionName();

        // Search by route name first if available
        if ($name) {
            foreach ($this->routeFilesCache as $file => $content) {
                if (str_contains($content, "'{$name}'") || str_contains($content, "\"{$name}\"")) {
                    return $this->formatRelativePath($file);
                }
            }
        }

        // Search by URI pattern
        if ($uri && $uri !== '/') {
            $cleanUri = trim($uri, '/');
            foreach ($this->routeFilesCache as $file => $content) {
                if (str_contains($content, "'{$cleanUri}'") || str_contains($content, "\"{$cleanUri}\"") || str_contains($content, "'/{$cleanUri}'") || str_contains($content, "\"/{$cleanUri}\"")) {
                    return $this->formatRelativePath($file);
                }
            }
        }

        // Search by Controller method
        if ($action && $action !== 'Closure' && str_contains($action, '@')) {
            $methodPart = explode('@', $action)[1] ?? '';
            $classPart = class_basename(explode('@', $action)[0] ?? '');
            if ($methodPart && $classPart) {
                foreach ($this->routeFilesCache as $file => $content) {
                    if (str_contains($content, $classPart) && str_contains($content, $methodPart)) {
                        return $this->formatRelativePath($file);
                    }
                }
            }
        }

        // 3. Fallback based on middleware groups & prefixes
        $middlewares = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : [];
        if (in_array('api', $middlewares) || Str::startsWith($uri, 'api/')) {
            return 'routes/api.php';
        }
        if (in_array('web', $middlewares)) {
            return 'routes/web.php';
        }

        return null;
    }

    /**
     * Populate cache of route files.
     */
    protected function loadRouteFilesCache(): void
    {
        if (!empty($this->routeFilesCache)) {
            return;
        }

        $files = [];

        // Check base_path('routes') if available
        $routesDir = function_exists('base_path') ? base_path('routes') : null;
        if ($routesDir && is_dir($routesDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($routesDir));
            foreach ($iterator as $f) {
                if ($f->isFile() && $f->getExtension() === 'php') {
                    $files[] = $f->getPathname();
                }
            }
        }

        // Add configured custom files
        $customFiles = config('rolepermissionmanager.scanner.route_files', []);
        foreach ($customFiles as $cf) {
            $path = Str::startsWith($cf, '/') ? $cf : (function_exists('base_path') ? base_path($cf) : $cf);
            if (file_exists($path) && !in_array($path, $files)) {
                $files[] = $path;
            }
        }

        foreach ($files as $filePath) {
            if (is_readable($filePath)) {
                $this->routeFilesCache[$filePath] = file_get_contents($filePath);
            }
        }
    }

    /**
     * Format a path relative to the application base path.
     */
    protected function formatRelativePath(string $fullPath): string
    {
        $base = function_exists('base_path') ? base_path() : '';
        if ($base && Str::startsWith($fullPath, $base)) {
            return ltrim(Str::replaceFirst($base, '', $fullPath), '/\\');
        }

        return $fullPath;
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
            'type'              => SecuredResource::TYPE_ROUTE,
            'controller_action' => $this->resolveControllerAction($route),
            'source_file'       => $this->resolveSourceFile($route),
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

        $orphaned = $resourceModel::routes()
            ->whereNotIn('identifier', $scannedIdentifiers)
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
