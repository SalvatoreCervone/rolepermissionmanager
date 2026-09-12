<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class AccessPolicyConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->admin->roles()->attach($role->id);
    }

    public function test_model_helper_methods_and_scopes_for_access_policies(): void
    {
        $public = SecuredResource::create([
            'identifier' => 'page.home',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'home',
            'is_public'  => true,
            'operator'   => 'OR',
        ]);

        $superAdmin = SecuredResource::create([
            'identifier'          => 'system.audit',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'audit',
            'is_super_admin_only' => true,
            'is_public'           => false,
            'operator'            => 'OR',
        ]);

        $unconfigured = SecuredResource::create([
            'identifier'      => 'api.checkout',
            'type'            => SecuredResource::TYPE_ROUTE,
            'method'          => 'POST',
            'uri'             => 'checkout',
            'is_unconfigured' => true,
            'is_public'       => false,
            'operator'        => 'OR',
        ]);

        $authOnly = SecuredResource::create([
            'identifier'      => 'profile.show',
            'type'            => SecuredResource::TYPE_ROUTE,
            'method'          => 'GET',
            'uri'             => 'profile',
            'is_public'       => false,
            'is_unconfigured' => false,
            'operator'        => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Manage Orders', 'slug' => 'orders.manage']);
        $protected = SecuredResource::create([
            'identifier'      => 'orders.manage',
            'type'            => SecuredResource::TYPE_ROUTE,
            'method'          => 'GET',
            'uri'             => 'orders',
            'is_public'       => false,
            'is_unconfigured' => false,
            'operator'        => 'OR',
        ]);
        $protected->permissions()->attach($perm->id);

        // Check helper methods
        $this->assertFalse($public->isAuthenticatedOnly());
        $this->assertFalse($superAdmin->isAuthenticatedOnly());
        $this->assertFalse($unconfigured->isAuthenticatedOnly());
        $this->assertTrue($authOnly->isAuthenticatedOnly());
        $this->assertFalse($protected->isAuthenticatedOnly());

        $this->assertFalse($authOnly->isProtectedWithPermissions());
        $this->assertTrue($protected->isProtectedWithPermissions());

        // Check scopes
        $authOnlyResults = SecuredResource::authenticatedOnly()->pluck('identifier')->all();
        $this->assertContains('profile.show', $authOnlyResults);
        $this->assertNotContains('orders.manage', $authOnlyResults);
        $this->assertNotContains('page.home', $authOnlyResults);

        $protectedResults = SecuredResource::protectedWithPermissions()->pluck('identifier')->all();
        $this->assertContains('orders.manage', $protectedResults);
        $this->assertNotContains('profile.show', $protectedResults);

        $unconfiguredResults = SecuredResource::unconfigured()->pluck('identifier')->all();
        $this->assertContains('api.checkout', $unconfiguredResults);
        $this->assertNotContains('profile.show', $unconfiguredResults);
    }

    public function test_updating_route_with_access_policy(): void
    {
        $route = SecuredResource::create([
            'identifier' => 'test.route',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'test-route',
            'operator'   => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Test Perm', 'slug' => 'test.perm']);
        $route->permissions()->attach($perm->id);

        // 1. Set to 'authenticated' (should clear permissions and flags)
        $response = $this->actingAs($this->admin)->put("/acl-admin/routes/{$route->id}", [
            'access_policy' => 'authenticated',
            'operator'      => 'OR',
        ]);
        $response->assertRedirect("/acl-admin/routes/{$route->id}/edit");
        $route->refresh();
        $this->assertFalse((bool)$route->is_public);
        $this->assertFalse((bool)$route->is_super_admin_only);
        $this->assertFalse((bool)$route->is_unconfigured);
        $this->assertCount(0, $route->permissions);
        $this->assertTrue($route->isAuthenticatedOnly());

        // 2. Set to 'unconfigured' (lock down route)
        $response = $this->actingAs($this->admin)->put("/acl-admin/routes/{$route->id}", [
            'access_policy' => 'unconfigured',
            'operator'      => 'OR',
        ]);
        $route->refresh();
        $this->assertTrue((bool)$route->is_unconfigured);
        $this->assertFalse((bool)$route->is_public);
        $this->assertFalse((bool)$route->is_super_admin_only);

        // 3. Set to 'protected' with permission
        $response = $this->actingAs($this->admin)->put("/acl-admin/routes/{$route->id}", [
            'access_policy' => 'protected',
            'operator'      => 'AND',
            'permissions'   => [$perm->id],
        ]);
        $route->refresh();
        $this->assertFalse((bool)$route->is_unconfigured);
        $this->assertCount(1, $route->permissions);
        $this->assertTrue($route->isProtectedWithPermissions());
        $this->assertSame('AND', $route->operator);
    }

    public function test_updating_custom_resource_with_access_policy(): void
    {
        $res = SecuredResource::create([
            'identifier' => 'BillingService@charge',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'operator'   => 'OR',
        ]);

        // Lock it
        $this->actingAs($this->admin)->put("/acl-admin/resources/{$res->id}", [
            'identifier'    => 'BillingService@charge',
            'access_policy' => 'unconfigured',
            'operator'      => 'OR',
        ]);
        $res->refresh();
        $this->assertTrue((bool)$res->is_unconfigured);

        // Set to solo autenticati
        $this->actingAs($this->admin)->put("/acl-admin/resources/{$res->id}", [
            'identifier'    => 'BillingService@charge',
            'access_policy' => 'authenticated',
            'operator'      => 'OR',
        ]);
        $res->refresh();
        $this->assertFalse((bool)$res->is_unconfigured);
        $this->assertTrue($res->isAuthenticatedOnly());
    }

    public function test_filtering_routes_by_status(): void
    {
        $rAuth = SecuredResource::create([
            'identifier' => 'route.auth_only',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'auth-only',
            'operator'   => 'OR',
        ]);

        $rUnc = SecuredResource::create([
            'identifier'      => 'route.unconfigured',
            'type'            => SecuredResource::TYPE_ROUTE,
            'method'          => 'GET',
            'uri'             => 'unconfigured',
            'is_unconfigured' => true,
            'operator'        => 'OR',
        ]);

        $responseAuth = $this->actingAs($this->admin)->get('/acl-admin/routes?status=authenticated');
        $responseAuth->assertOk();
        $responseAuth->assertSee('route.auth_only');
        $responseAuth->assertDontSee('route.unconfigured');

        $responseUnc = $this->actingAs($this->admin)->get('/acl-admin/routes?status=unconfigured');
        $responseUnc->assertOk();
        $responseUnc->assertSee('route.unconfigured');
        $responseUnc->assertDontSee('route.auth_only');
    }

    public function test_bulk_set_unconfigured_and_authenticated_only(): void
    {
        $r1 = SecuredResource::create([
            'identifier' => 'bulk.route.1',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'bulk/1',
            'operator'   => 'OR',
        ]);
        $r2 = SecuredResource::create([
            'identifier' => 'bulk.route.2',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'bulk/2',
            'operator'   => 'OR',
        ]);

        // Bulk lock
        $this->actingAs($this->admin)->post('/acl-admin/routes/bulk-update', [
            'ids'    => [$r1->id, $r2->id],
            'action' => 'set_unconfigured',
        ]);
        $r1->refresh();
        $r2->refresh();
        $this->assertTrue((bool)$r1->is_unconfigured);
        $this->assertTrue((bool)$r2->is_unconfigured);

        // Bulk set authenticated only
        $this->actingAs($this->admin)->post('/acl-admin/routes/bulk-update', [
            'ids'    => [$r1->id, $r2->id],
            'action' => 'set_authenticated_only',
        ]);
        $r1->refresh();
        $r2->refresh();
        $this->assertFalse((bool)$r1->is_unconfigured);
        $this->assertTrue($r1->isAuthenticatedOnly());
        $this->assertTrue($r2->isAuthenticatedOnly());
    }
}
