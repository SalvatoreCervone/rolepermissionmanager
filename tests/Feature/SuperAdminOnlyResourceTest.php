<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException;
use SalvatoreCervone\RolePermissionManager\Http\Middleware\DynamicAclGuard;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class SuperAdminOnlyResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
        ]);

        $this->superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'super@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $this->regularUser = User::create([
            'name'     => 'Regular User',
            'email'    => 'regular@example.com',
            'password' => bcrypt('password'),
        ]);

        AclRegistry::refreshCache();
    }

    public function test_super_admin_only_route_blocks_regular_user_and_allows_super_admin(): void
    {
        Route::get('/admin/danger-zone', function () {
            return response()->json(['status' => 'welcome']);
        })->middleware(['web', DynamicAclGuard::class])->name('admin.danger_zone');

        SecuredResource::create([
            'identifier'          => 'admin.danger_zone',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'admin/danger-zone',
            'is_public'           => false,
            'is_super_admin_only' => true,
            'operator'            => 'OR',
            'is_deprecated'       => false,
        ]);

        AclRegistry::refreshCache();

        // 1. Regular user gets 403 Forbidden
        $response = $this->actingAs($this->regularUser)->get('/admin/danger-zone');
        $response->assertStatus(403);

        // 2. Super Admin gets 200 OK
        $responseSuper = $this->actingAs($this->superAdmin)->get('/admin/danger-zone');
        $responseSuper->assertStatus(200);
        $responseSuper->assertJson(['status' => 'welcome']);
    }

    public function test_super_admin_only_custom_resource_with_acl_registry(): void
    {
        SecuredResource::create([
            'identifier'          => 'MaintenanceService@purgeLogs',
            'type'                => SecuredResource::TYPE_CUSTOM,
            'is_public'           => false,
            'is_super_admin_only' => true,
            'operator'            => 'OR',
            'is_deprecated'       => false,
        ]);

        AclRegistry::refreshCache();

        // Check hasAccess
        $this->assertFalse(AclRegistry::hasAccess('MaintenanceService@purgeLogs', $this->regularUser));
        $this->assertTrue(AclRegistry::hasAccess('MaintenanceService@purgeLogs', $this->superAdmin));

        // Check authorize throws on regular user
        $this->expectException(UnauthorizedException::class);
        AclRegistry::authorize('MaintenanceService@purgeLogs', $this->regularUser);
    }

    public function test_super_admin_only_custom_resource_authorize_passes_for_super_admin(): void
    {
        SecuredResource::create([
            'identifier'          => 'MaintenanceService@restartServers',
            'type'                => SecuredResource::TYPE_CUSTOM,
            'is_public'           => false,
            'is_super_admin_only' => true,
            'operator'            => 'OR',
            'is_deprecated'       => false,
        ]);

        AclRegistry::refreshCache();

        // Should not throw exception
        AclRegistry::authorize('MaintenanceService@restartServers', $this->superAdmin);
        $this->assertTrue(true);
    }

    public function test_can_toggle_super_admin_only_via_admin_panel_forms(): void
    {
        $route = SecuredResource::create([
            'identifier'          => 'reports.financial',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'reports/financial',
            'is_public'           => false,
            'is_super_admin_only' => false,
            'operator'            => 'OR',
        ]);

        // Update route to super admin only
        $response = $this->actingAs($this->superAdmin)->put("/acl-admin/routes/{$route->id}", [
            'is_public'           => '0',
            'is_super_admin_only' => '1',
            'operator'            => 'OR',
        ]);

        $response->assertRedirect("/acl-admin/routes/{$route->id}/edit");
        $route->refresh();
        $this->assertTrue($route->is_super_admin_only);

        // Create custom resource as super admin only
        $resResponse = $this->actingAs($this->superAdmin)->post('/acl-admin/resources', [
            'identifier'          => 'SystemSettings@wipeDatabase',
            'description'         => 'Wipe DB',
            'is_public'           => '0',
            'is_super_admin_only' => '1',
            'operator'            => 'OR',
        ]);

        $resResponse->assertRedirect('/acl-admin/resources');
        $this->assertDatabaseHas('acl_secured_resources', [
            'identifier'          => 'SystemSettings@wipeDatabase',
            'is_super_admin_only' => true,
        ]);
    }
}
