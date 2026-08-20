<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class BulkResourceUpdateTest extends TestCase
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

    public function test_can_bulk_set_super_admin_only_on_routes(): void
    {
        $r1 = SecuredResource::create([
            'identifier' => 'invoices.export',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'invoices/export',
            'is_public'  => false,
            'operator'   => 'OR',
        ]);

        $r2 = SecuredResource::create([
            'identifier' => 'invoices.delete_all',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'POST',
            'uri'        => 'invoices/delete-all',
            'is_public'  => false,
            'operator'   => 'OR',
        ]);

        $response = $this->actingAs($this->admin)->post('/acl-admin/routes/bulk-update', [
            'ids'    => [$r1->id, $r2->id],
            'action' => 'set_super_admin',
        ]);

        $response->assertRedirect('/acl-admin/routes');
        $r1->refresh();
        $r2->refresh();

        $this->assertTrue($r1->is_super_admin_only);
        $this->assertTrue($r2->is_super_admin_only);
    }

    public function test_can_bulk_add_and_sync_permissions_on_routes(): void
    {
        $r1 = SecuredResource::create([
            'identifier' => 'reports.a',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'reports/a',
            'operator'   => 'OR',
        ]);
        $r2 = SecuredResource::create([
            'identifier' => 'reports.b',
            'type'       => SecuredResource::TYPE_ROUTE,
            'method'     => 'GET',
            'uri'        => 'reports/b',
            'operator'   => 'OR',
        ]);

        $p1 = Permission::create(['name' => 'View Reports', 'slug' => 'reports.view']);
        $p2 = Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export']);

        // 1. Bulk Add permissions
        $response = $this->actingAs($this->admin)->post('/acl-admin/routes/bulk-update', [
            'ids'         => [$r1->id, $r2->id],
            'action'      => 'add_permissions',
            'permissions' => [$p1->id, $p2->id],
        ]);

        $response->assertRedirect('/acl-admin/routes');
        $r1->refresh();
        $r2->refresh();

        $this->assertCount(2, $r1->permissions);
        $this->assertCount(2, $r2->permissions);

        // 2. Bulk remove all permissions
        $this->actingAs($this->admin)->post('/acl-admin/routes/bulk-update', [
            'ids'    => [$r1->id, $r2->id],
            'action' => 'remove_all_permissions',
        ]);

        $r1->refresh();
        $r2->refresh();
        $this->assertCount(0, $r1->permissions);
        $this->assertCount(0, $r2->permissions);
    }

    public function test_can_bulk_update_custom_resources(): void
    {
        $c1 = SecuredResource::create([
            'identifier' => 'PaymentService@process',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'operator'   => 'OR',
        ]);
        $c2 = SecuredResource::create([
            'identifier' => 'PaymentService@refund',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'operator'   => 'OR',
        ]);

        // Bulk set public
        $this->actingAs($this->admin)->post('/acl-admin/resources/bulk-update', [
            'ids'    => [$c1->id, $c2->id],
            'action' => 'make_public',
        ]);

        $c1->refresh();
        $c2->refresh();
        $this->assertTrue($c1->is_public);
        $this->assertTrue($c2->is_public);

        // Bulk delete
        $this->actingAs($this->admin)->post('/acl-admin/resources/bulk-update', [
            'ids'    => [$c1->id, $c2->id],
            'action' => 'delete',
        ]);

        $this->assertDatabaseMissing('acl_secured_resources', ['id' => $c1->id]);
        $this->assertDatabaseMissing('acl_secured_resources', ['id' => $c2->id]);
    }
}
