<?php

namespace SalvatoreCervone\RolePermissionManager;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SalvatoreCervone\RolePermissionManager\Commands\SyncAclRoutesCommand;
use SalvatoreCervone\RolePermissionManager\Http\Middleware\DynamicAclGuard;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\RouteScanner;

class RolePermissionManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with the application's config.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rolepermissionmanager.php',
            'rolepermissionmanager'
        );

        // Register the RouteScanner as a singleton.
        $this->app->singleton(RouteScanner::class, function ($app) {
            return new RouteScanner($app->make(Router::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerViews();
        $this->registerAdminRoutes();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerGateIntegration();
        $this->registerBladeDirectives();
        $this->registerScheduler();
        $this->registerModelObservers();
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/rolepermissionmanager.php' => config_path('rolepermissionmanager.php'),
        ], 'rolepermissionmanager-config');
    }

    /**
     * Publish / load the database migrations.
     */
    protected function publishMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'rolepermissionmanager-migrations');
    }

    /**
     * Register package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'acl');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/acl'),
        ], 'rolepermissionmanager-views');
    }

    /**
     * Register admin panel routes if enabled.
     */
    protected function registerAdminRoutes(): void
    {
        if (!config('rolepermissionmanager.admin_panel.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

    /**
     * Register the Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncAclRoutesCommand::class,
            ]);
        }
    }

    /**
     * Register the DynamicAclGuard middleware.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Always register as a named middleware alias for manual usage.
        $router->aliasMiddleware('acl', DynamicAclGuard::class);

        // Optionally register globally on web and api groups.
        if (config('rolepermissionmanager.middleware.register_globally', true)) {
            // For Laravel 11+ (middleware groups on the Router).
            $router->pushMiddlewareToGroup('web', DynamicAclGuard::class);
            $router->pushMiddlewareToGroup('api', DynamicAclGuard::class);
        }
    }

    /**
     * Integrate with Laravel's Gate for native $user->can() and @can support.
     */
    protected function registerGateIntegration(): void
    {
        Gate::before(function ($user, string $ability) {
            // Super Admin bypass.
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Check if the user has the permission (by slug).
            if (method_exists($user, 'hasPermission')) {
                if ($user->hasPermission($ability)) {
                    return true;
                }
            }

            // Return null to let other gates/policies handle it.
            return null;
        });
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // @role('admin') ... @endrole
        Blade::if('role', function (string $role) {
            $user = auth()->user();
            return $user && method_exists($user, 'hasRole') && $user->hasRole($role);
        });

        // @haspermission('users.export') ... @endhaspermission
        Blade::if('haspermission', function (string $permission) {
            $user = auth()->user();
            return $user && method_exists($user, 'hasPermission') && $user->hasPermission($permission);
        });

        // @canRoute('invoices.destroy') ... @endcanRoute
        Blade::if('canRoute', function (string $routeNameOrSignature) {
            $user = auth()->user();
            return $user && method_exists($user, 'canAccessRoute') && $user->canAccessRoute($routeNameOrSignature);
        });
    }

    /**
     * Register the scheduler for automatic route syncing.
     */
    protected function registerScheduler(): void
    {
        if (!config('rolepermissionmanager.scheduler.enabled', false)) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $time = config('rolepermissionmanager.scheduler.time', '06:00');
            $options = config('rolepermissionmanager.scheduler.options', []);

            $command = 'acl:sync';

            if ($options['clean'] ?? false) {
                $command .= ' --clean';
            }

            if ($options['auto_permissions'] ?? false) {
                $command .= ' --auto-permissions';
            }

            if ($options['notify'] ?? false) {
                $command .= ' --notify';
            }

            $schedule->command($command)
                ->dailyAt($time)
                ->withoutOverlapping()
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/acl-sync.log'));
        });
    }

    /**
     * Register Eloquent model observers for automatic cache invalidation.
     *
     * When a Role, Permission, or SecuredResource is created/updated/deleted,
     * the ACL cache is automatically flushed to reflect the changes.
     */
    protected function registerModelObservers(): void
    {
        $events = ['saved', 'deleted'];

        $roleModel = config('rolepermissionmanager.models.role', \SalvatoreCervone\RolePermissionManager\Models\Role::class);
        $permissionModel = config('rolepermissionmanager.models.permission', \SalvatoreCervone\RolePermissionManager\Models\Permission::class);
        $resourceModel = config('rolepermissionmanager.models.secured_resource', \SalvatoreCervone\RolePermissionManager\Models\SecuredResource::class);

        foreach ($events as $event) {
            // Flush resources cache when roles, permissions, or resources change.
            $roleModel::$event(function () {
                AclRegistry::flushResourcesCache();
            });

            $permissionModel::$event(function () {
                AclRegistry::flushResourcesCache();
            });

            $resourceModel::$event(function () {
                AclRegistry::flushResourcesCache();
            });
        }
    }
}
