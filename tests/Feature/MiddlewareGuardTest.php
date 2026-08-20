<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Http\Middleware\DynamicAclGuard;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use SalvatoreCervone\RolePermissionManager\Tests\TestUser;

class MiddlewareGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define test routes with the middleware applied.
        Route::middleware(DynamicAclGuard::class)->group(function () {
            Route::get('/public-page', function () {
                return 'Public Content';
            })->name('public.page');

            Route::get('/protected-page', function () {
                return 'Protected Content';
            })->name('protected.page');

            Route::delete('/admin/users/{id}', function ($id) {
                return "Deleted user {$id}";
            })->name('admin.users.destroy');

            Route::post('/critical-action', function () {
                return 'Critical action performed';
            })->name('critical.action');

            Route::get('/unmanaged-page', function () {
                return 'Unmanaged Content';
            })->name('unmanaged.page');
        });
    }

    /**
     * Register a secured resource in the DB and flush cache.
     */
    protected function registerResource(
        string $identifier,
        string $method,
        string $uri,
        bool $isPublic = false,
        string $operator = 'OR',
        array $permissionSlugs = []
    ): SecuredResource {
        $resource = SecuredResource::create([
            'identifier'        => $identifier,
            'controller_action' => 'Closure',
            'method'            => $method,
            'uri'               => $uri,
            'is_public'         => $isPublic,
            'operator'          => $operator,
        ]);

        foreach ($permissionSlugs as $slug) {
            $permission = Permission::findOrCreate($slug);
            $resource->permissions()->attach($permission->id);
        }

        AclRegistry::refreshCache();

        return $resource;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Resource Tests
    |--------------------------------------------------------------------------
    */

    public function test_public_resource_is_accessible_without_auth(): void
    {
        $this->registerResource('public.page', 'GET', 'public-page', isPublic: true);

        $response = $this->get('/public-page');

        $response->assertStatus(200);
        $response->assertSee('Public Content');
    }

    /*
    |--------------------------------------------------------------------------
    | Unmanaged Resource Tests
    |--------------------------------------------------------------------------
    */

    public function test_unmanaged_route_is_allowed_by_default(): void
    {
        // 'unmanaged.page' is NOT in the secured_resources table.
        $response = $this->get('/unmanaged-page');

        $response->assertStatus(200);
        $response->assertSee('Unmanaged Content');
    }

    public function test_unmanaged_route_is_denied_when_behavior_is_deny(): void
    {
        config()->set('rolepermissionmanager.middleware.unprotected_behavior', 'deny');

        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/unmanaged-page');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication Tests
    |--------------------------------------------------------------------------
    */

    public function test_protected_resource_returns_401_for_guests(): void
    {
        $this->registerResource('protected.page', 'GET', 'protected-page', permissionSlugs: ['page.view']);

        $response = $this->get('/protected-page');

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Check Tests (OR)
    |--------------------------------------------------------------------------
    */

    public function test_authorized_user_can_access_protected_resource_or(): void
    {
        $this->registerResource('protected.page', 'GET', 'protected-page', operator: 'OR', permissionSlugs: ['page.view', 'page.admin']);

        $user = $this->createUser();
        $user->givePermissionTo('page.view');
        AclRegistry::flushUserCache($user->getKey());

        $this->actingAs($user);

        $response = $this->get('/protected-page');

        $response->assertStatus(200);
        $response->assertSee('Protected Content');
    }

    public function test_unauthorized_user_gets_403_or(): void
    {
        $this->registerResource('protected.page', 'GET', 'protected-page', operator: 'OR', permissionSlugs: ['page.view']);

        $user = $this->createUser();
        // User has no permissions.

        $this->actingAs($user);

        $response = $this->get('/protected-page');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Check Tests (AND)
    |--------------------------------------------------------------------------
    */

    public function test_user_with_all_permissions_can_access_and_resource(): void
    {
        $this->registerResource('critical.action', 'POST', 'critical-action', operator: 'AND', permissionSlugs: ['security.verify', 'action.execute']);

        $user = $this->createUser();
        $user->givePermissionTo('security.verify', 'action.execute');
        AclRegistry::flushUserCache($user->getKey());

        $this->actingAs($user);

        $response = $this->post('/critical-action');

        $response->assertStatus(200);
    }

    public function test_user_missing_one_permission_gets_403_and_resource(): void
    {
        $this->registerResource('critical.action', 'POST', 'critical-action', operator: 'AND', permissionSlugs: ['security.verify', 'action.execute']);

        $user = $this->createUser();
        $user->givePermissionTo('security.verify');
        // Missing: 'action.execute'
        AclRegistry::flushUserCache($user->getKey());

        $this->actingAs($user);

        $response = $this->post('/critical-action');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin Bypass Tests
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $this->registerResource('admin.users.destroy', 'DELETE', 'admin/users/{id}', permissionSlugs: ['admin.delete-users']);

        $user = $this->createUser();
        $superAdminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->assignRole($superAdminRole);
        AclRegistry::flushUserCache($user->getKey());

        $this->actingAs($user);

        $response = $this->delete('/admin/users/1');

        $response->assertStatus(200);
        $response->assertSee('Deleted user 1');
    }

    /*
    |--------------------------------------------------------------------------
    | No Permissions Configured Tests
    |--------------------------------------------------------------------------
    */

    public function test_resource_with_no_permissions_blocks_non_super_admin(): void
    {
        config()->set('rolepermissionmanager.middleware.unassigned_permissions_behavior', 'deny');

        // Resource registered but no permissions attached = only SuperAdmin when behavior is 'deny'.
        $this->registerResource('protected.page', 'GET', 'protected-page');

        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/protected-page');

        $response->assertStatus(403);
    }

    public function test_resource_with_no_permissions_allows_super_admin(): void
    {
        $this->registerResource('protected.page', 'GET', 'protected-page');

        $user = $this->createUser();
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->assignRole('super-admin');
        AclRegistry::flushUserCache($user->getKey());

        $this->actingAs($user);

        $response = $this->get('/protected-page');

        $response->assertStatus(200);
    }

    public function test_unnamed_route_lookup_by_signature_works(): void
    {
        Route::middleware(DynamicAclGuard::class)->get('/unnamed-endpoint', function () {
            return 'Unnamed OK';
        }); // no ->name()

        $this->registerResource('GET:unnamed-endpoint', 'GET', 'unnamed-endpoint', isPublic: true);

        $response = $this->get('/unnamed-endpoint');

        $response->assertStatus(200);
        $response->assertSee('Unnamed OK');
    }

    public function test_protected_route_with_no_permissions_allows_any_authenticated_user(): void
    {
        // Protected route (is_public: false) with 0 permissions
        $this->registerResource('protected.page', 'GET', 'protected-page', isPublic: false, permissionSlugs: []);

        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/protected-page');

        $response->assertStatus(200);
        $response->assertSee('Protected Content');
    }

    public function test_protected_route_with_no_permissions_denies_unauthenticated_guests(): void
    {
        // Protected route (is_public: false) with 0 permissions
        $this->registerResource('protected.page', 'GET', 'protected-page', isPublic: false, permissionSlugs: []);

        // Guest (not logged in)
        $response = $this->get('/protected-page');

        $response->assertStatus(401);
    }
}

