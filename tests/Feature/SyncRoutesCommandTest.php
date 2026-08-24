<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\RouteScanner;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class SyncRoutesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register some test routes to be discovered by the scanner.
        Route::get('/users', function () {
            return 'Users list';
        })->name('users.index');

        Route::post('/users', function () {
            return 'User created';
        })->name('users.store');

        Route::delete('/users/{id}', function ($id) {
            return "User {$id} deleted";
        })->name('users.destroy');

        Route::get('/about', function () {
            return 'About page';
        })->name('about');
    }

    /*
    |--------------------------------------------------------------------------
    | Scanner Tests
    |--------------------------------------------------------------------------
    */

    public function test_scanner_discovers_named_routes(): void
    {
        $scanner = app(RouteScanner::class);
        $summary = $scanner->scan();

        $this->assertContains('users.index', $summary['created']);
        $this->assertContains('users.store', $summary['created']);
        $this->assertContains('users.destroy', $summary['created']);
    }

    public function test_scanner_creates_secured_resources_in_database(): void
    {
        $scanner = app(RouteScanner::class);
        $scanner->scan();

        $resource = SecuredResource::findByIdentifier('users.destroy');

        $this->assertNotNull($resource);
        $this->assertEquals('DELETE', $resource->method);
        $this->assertEquals('users/{id}', $resource->uri);
        $this->assertFalse($resource->is_public);
        $this->assertEquals('OR', $resource->operator);
    }

    public function test_scanner_does_not_duplicate_on_rescan(): void
    {
        $scanner = app(RouteScanner::class);

        $scanner->scan();
        $firstCount = SecuredResource::count();

        // Re-scan.
        $scanner2 = app(RouteScanner::class);
        $summary2 = $scanner2->scan();

        $secondCount = SecuredResource::count();

        $this->assertEquals($firstCount, $secondCount);
        $this->assertContains('users.index', $summary2['updated']);
    }

    public function test_scanner_deprecates_removed_routes(): void
    {
        $scanner = app(RouteScanner::class);
        $scanner->scan();

        // Simulate a route that was removed from code.
        SecuredResource::create([
            'identifier'        => 'legacy.route',
            'controller_action' => 'LegacyController@index',
            'method'            => 'GET',
            'uri'               => 'legacy',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        // Re-scan (legacy.route is not in the router).
        $scanner2 = app(RouteScanner::class);
        $summary = $scanner2->scan(clean: false);

        $this->assertContains('legacy.route', $summary['deprecated']);

        $resource = SecuredResource::findByIdentifier('legacy.route');
        $this->assertTrue($resource->is_deprecated);
    }

    public function test_scanner_removes_routes_with_clean_option(): void
    {
        $scanner = app(RouteScanner::class);
        $scanner->scan();

        // Manually insert a route that no longer exists.
        SecuredResource::create([
            'identifier'        => 'removed.route',
            'controller_action' => 'RemovedController@index',
            'method'            => 'GET',
            'uri'               => 'removed',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $scanner2 = app(RouteScanner::class);
        $summary = $scanner2->scan(clean: true);

        $this->assertContains('removed.route', $summary['removed']);
        $this->assertNull(SecuredResource::findByIdentifier('removed.route'));
    }

    public function test_scanner_auto_creates_permissions(): void
    {
        $scanner = app(RouteScanner::class);
        $scanner->scan(autoPermissions: true);

        // Check that a permission was created for 'users.index'.
        $permission = Permission::findBySlug('users.index');
        $this->assertNotNull($permission);
        $this->assertEquals('Users', $permission->module);

        // Check that the permission is linked to the resource.
        $resource = SecuredResource::findByIdentifier('users.index');
        $this->assertTrue($resource->permissions->contains('slug', 'users.index'));
    }

    public function test_scanner_excludes_configured_routes(): void
    {
        // Add a route with an excluded prefix.
        Route::get('/_ignition/health', function () {
            return 'ok';
        });

        // Add a route with an excluded name.
        Route::get('/auth/login', function () {
            return 'login';
        })->name('login');

        $scanner = app(RouteScanner::class);
        $summary = $scanner->scan();

        // These should be in skipped, not created.
        $this->assertNotContains('login', $summary['created']);
    }

    /*
    |--------------------------------------------------------------------------
    | Artisan Command Tests
    |--------------------------------------------------------------------------
    */

    public function test_acl_sync_command_runs_successfully(): void
    {
        $this->artisan('acl:sync', ['--notify' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('ACL route sync completed');
    }

    public function test_acl_sync_command_with_clean_option(): void
    {
        // Pre-populate an orphaned route.
        SecuredResource::create([
            'identifier'        => 'orphan.route',
            'controller_action' => 'OrphanController@index',
            'method'            => 'GET',
            'uri'               => 'orphan',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $this->artisan('acl:sync', ['--clean' => true, '--notify' => true])
            ->assertExitCode(0);

        $this->assertNull(SecuredResource::findByIdentifier('orphan.route'));
    }

    public function test_acl_sync_command_with_auto_permissions(): void
    {
        $this->artisan('acl:sync', ['--auto-permissions' => true])
            ->assertExitCode(0);

        $this->assertNotNull(Permission::findBySlug('users.index'));
    }

    public function test_scanner_with_included_prefixes_whitelist(): void
    {
        config()->set('rolepermissionmanager.scanner.included_prefixes', ['users']);

        $scanner = new RouteScanner(app('router'));
        $summary = $scanner->scan();

        $this->assertContains('users.index', $summary['created']);
        $this->assertNotContains('about', $summary['created']);
    }

    public function test_scanner_discovers_unnamed_closure_routes(): void
    {
        \Illuminate\Support\Facades\Route::get('/ricercacorsi', fn() => 'courses');

        $scanner = new RouteScanner(app('router'));
        $summary = $scanner->scan();

        $this->assertContains('GET:ricercacorsi', $summary['created']);
    }

    public function test_scanner_resolves_route_source_file(): void
    {
        \Illuminate\Support\Facades\Route::get('/closure-route', fn() => 'closure')->name('closure.route');

        $scanner = new RouteScanner(app('router'));
        $scanner->scan();

        $resource = SecuredResource::findByIdentifier('closure.route');
        $this->assertNotNull($resource);
        $this->assertNotNull($resource->source_file);
        $this->assertStringContainsString('SyncRoutesCommandTest.php', $resource->source_file);
    }
}


