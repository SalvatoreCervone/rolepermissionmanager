<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Http\Middleware\DynamicAclGuard;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class SuperAdminBypassConfigTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Permission $permCalendar;
    protected Permission $permBilling;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::create([
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
        ]);

        $this->permCalendar = Permission::create([
            'name'   => 'Calendario',
            'slug'   => 'calendario',
            'module' => 'calendar',
        ]);

        $this->permBilling = Permission::create([
            'name'   => 'Fatturazione',
            'slug'   => 'billing',
            'module' => 'finance',
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

    public function test_when_all_access_is_true_super_admin_bypasses_all_permission_checks(): void
    {
        config(['rolepermissionmanager.super_admin.all_access' => true]);

        Route::get('/test/calendar', fn() => response()->json(['status' => 'ok']))
            ->middleware(['web', DynamicAclGuard::class])
            ->name('calendar.index');

        $resource = SecuredResource::create([
            'identifier'          => 'calendar.index',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'test/calendar',
            'is_public'           => false,
            'is_super_admin_only' => false,
            'operator'            => 'OR',
        ]);
        $resource->permissions()->attach($this->permCalendar->id);
        AclRegistry::refreshCache();

        // Super Admin has NO calendar permission assigned, but all_access is true => allowed
        $response = $this->actingAs($this->superAdmin)->get('/test/calendar');
        $response->assertStatus(200);

        // Regular user without permission => 403
        $response = $this->actingAs($this->regularUser)->get('/test/calendar');
        $response->assertStatus(403);
    }

    public function test_when_all_access_is_false_or_null_super_admin_only_accesses_assigned_permissions(): void
    {
        // Disable global bypass
        config(['rolepermissionmanager.super_admin.all_access' => false]);

        Route::get('/test/calendar', fn() => response()->json(['status' => 'calendar_ok']))
            ->middleware(['web', DynamicAclGuard::class])
            ->name('calendar.index');

        Route::get('/test/billing', fn() => response()->json(['status' => 'billing_ok']))
            ->middleware(['web', DynamicAclGuard::class])
            ->name('billing.index');

        $calendarResource = SecuredResource::create([
            'identifier'          => 'calendar.index',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'test/calendar',
            'is_public'           => false,
            'is_super_admin_only' => false,
            'operator'            => 'OR',
        ]);
        $calendarResource->permissions()->attach($this->permCalendar->id);

        $billingResource = SecuredResource::create([
            'identifier'          => 'billing.index',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'test/billing',
            'is_public'           => false,
            'is_super_admin_only' => false,
            'operator'            => 'OR',
        ]);
        $billingResource->permissions()->attach($this->permBilling->id);
        AclRegistry::refreshCache();

        // Assign ONLY calendar permission to Super Admin
        $this->superAdmin->givePermissionTo($this->permCalendar);

        // Super Admin accessing calendar => 200 (has permission)
        $response = $this->actingAs($this->superAdmin)->get('/test/calendar');
        $response->assertStatus(200);

        // Super Admin accessing billing => 403 (does NOT have billing permission and all_access is false)
        $response = $this->actingAs($this->superAdmin)->get('/test/billing');
        $response->assertStatus(403);
    }

    public function test_when_all_access_is_false_super_admin_can_still_access_super_admin_only_resource(): void
    {
        config(['rolepermissionmanager.super_admin.all_access' => false]);

        Route::get('/test/danger-zone', fn() => response()->json(['status' => 'danger_ok']))
            ->middleware(['web', DynamicAclGuard::class])
            ->name('danger.index');

        SecuredResource::create([
            'identifier'          => 'danger.index',
            'type'                => SecuredResource::TYPE_ROUTE,
            'method'              => 'GET',
            'uri'                 => 'test/danger-zone',
            'is_public'           => false,
            'is_super_admin_only' => true,
            'operator'            => 'OR',
        ]);
        AclRegistry::refreshCache();

        // Super admin CAN access super_admin_only route even when all_access is false
        $response = $this->actingAs($this->superAdmin)->get('/test/danger-zone');
        $response->assertStatus(200);

        // Regular user is blocked (403)
        $response = $this->actingAs($this->regularUser)->get('/test/danger-zone');
        $response->assertStatus(403);
    }

    public function test_menu_filtering_respects_all_access_setting(): void
    {
        $menu = [
            [
                'label'      => 'Calendar',
                'permission' => 'calendario',
            ],
            [
                'label'      => 'Billing',
                'permission' => 'billing',
            ],
        ];

        // 1. When all_access is true => Super Admin sees all menu items
        config(['rolepermissionmanager.super_admin.all_access' => true]);
        $filtered = AclRegistry::filterMenu($menu, $this->superAdmin);
        $this->assertCount(2, $filtered);

        // 2. When all_access is false and no permissions assigned => Super Admin sees 0 items
        config(['rolepermissionmanager.super_admin.all_access' => false]);
        $filtered = AclRegistry::filterMenu($menu, $this->superAdmin);
        $this->assertCount(0, $filtered);

        // 3. When all_access is false and calendar is assigned => Super Admin sees only calendar
        $this->superAdmin->givePermissionTo($this->permCalendar);
        $filtered = AclRegistry::filterMenu($menu, $this->superAdmin);
        $this->assertCount(1, $filtered);
        $this->assertEquals('Calendar', $filtered[0]['label']);
    }

    public function test_gate_integration_respects_all_access_setting(): void
    {
        // When all_access is true => Gate returns true for any ability
        config(['rolepermissionmanager.super_admin.all_access' => true]);
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('any_random_ability'));

        // When all_access is false => Gate returns false for unassigned ability
        config(['rolepermissionmanager.super_admin.all_access' => false]);
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('billing'));

        // But returns true for assigned ability
        $this->superAdmin->givePermissionTo($this->permCalendar);
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('calendario'));
    }
}
