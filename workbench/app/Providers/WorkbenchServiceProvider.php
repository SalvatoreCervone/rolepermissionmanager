<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Set up isolated SQLite test database for workbench preview.
        $dbPath = __DIR__ . '/../../database/test.sqlite';
        if (!file_exists($dbPath)) {
            @mkdir(dirname($dbPath), 0755, true);
            touch($dbPath);
        }

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'url'                     => null,
            'database'                => $dbPath,
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);

        // Override the User model for auth.
        $this->app['config']->set('auth.providers.users.model', \Workbench\App\Models\User::class);

        // Disable global ACL middleware in preview to avoid blocking admin panel itself.
        $this->app['config']->set('rolepermissionmanager.middleware.register_globally', false);

        // Enable scheduler for demo.
        $this->app['config']->set('rolepermissionmanager.scheduler.enabled', false);
    }
}
