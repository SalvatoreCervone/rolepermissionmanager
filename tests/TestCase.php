<?php

namespace SalvatoreCervone\RolePermissionManager\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Orchestra\Testbench\TestCase as Orchestra;
use SalvatoreCervone\RolePermissionManager\RolePermissionManagerServiceProvider;
use SalvatoreCervone\RolePermissionManager\Traits\HasAcl;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        \SalvatoreCervone\RolePermissionManager\Services\AclRegistry::flushAll();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create a minimal users table for testing.
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->timestamps();
        });
    }

    /**
     * Get the package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            RolePermissionManagerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Set test app key for session/cookie encryption.
        $app['config']->set('app.key', 'base64:6Cu6W+V2EaH+H1YkY+fV3wX8gq1+Tz1y6p7Z4o3xU2A=');

        // Use an in-memory SQLite database for testing.
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Use default cache driver (array for testing).
        $app['config']->set('cache.default', 'array');

        // Configure user model for testing.
        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('rolepermissionmanager.models.user', TestUser::class);

        // Disable global middleware registration in tests to avoid interference.
        $app['config']->set('rolepermissionmanager.middleware.register_globally', false);
    }

    /**
     * Create a test user with the HasAcl trait.
     */
    protected function createUser(array $attributes = []): TestUser
    {
        return TestUser::create(array_merge([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ], $attributes));
    }
}

/**
 * A minimal User model for testing purposes.
 */
class TestUser extends Authenticatable
{
    use HasAcl;

    protected $table = 'users';
    protected $guarded = [];
}
