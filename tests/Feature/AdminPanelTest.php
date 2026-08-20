<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class AdminPanelTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Enable admin panel for tests.
        $app['config']->set('rolepermissionmanager.admin_panel.enabled', true);
        $app['config']->set('rolepermissionmanager.admin_panel.middleware', ['web']);
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/acl-admin');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Roles');
        $response->assertSee('Permissions');
    }

    public function test_roles_index_renders(): void
    {
        Role::create(['name' => 'Manager', 'slug' => 'manager']);

        $response = $this->get('/acl-admin/roles');

        $response->assertStatus(200);
        $response->assertSee('Manager');
    }

    public function test_can_create_role_via_admin(): void
    {
        $response = $this->post('/acl-admin/roles', [
            'name'        => 'Finance Officer',
            'slug'        => 'finance-officer',
            'description' => 'Handles invoices',
        ]);

        $response->assertRedirect('/acl-admin/roles');
        $this->assertDatabaseHas('acl_roles', ['slug' => 'finance-officer']);
    }

    public function test_can_update_role_and_assign_permissions(): void
    {
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $permission = Permission::create(['name' => 'View Users', 'slug' => 'users.view']);

        $response = $this->put("/acl-admin/roles/{$role->id}", [
            'name'        => 'Staff Updated',
            'slug'        => 'staff',
            'description' => 'Updated description',
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect("/acl-admin/roles/{$role->id}/edit");
        $this->assertTrue($role->fresh()->permissions->contains('id', $permission->id));
    }

    public function test_permissions_index_renders(): void
    {
        Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'Reports']);

        $response = $this->get('/acl-admin/permissions');

        $response->assertStatus(200);
        $response->assertSee('Export Reports');
        $response->assertSee('Reports');
    }

    public function test_can_create_permission_via_admin(): void
    {
        $response = $this->post('/acl-admin/permissions', [
            'name'        => 'Audit Logs',
            'slug'        => 'logs.audit',
            'module'      => 'Security',
            'description' => 'View security logs',
        ]);

        $response->assertRedirect('/acl-admin/permissions');
        $this->assertDatabaseHas('acl_permissions', ['slug' => 'logs.audit', 'module' => 'Security']);
    }

    public function test_resources_index_renders(): void
    {
        SecuredResource::create([
            'identifier'        => 'orders.export',
            'controller_action' => 'OrderController@export',
            'method'            => 'POST',
            'uri'               => 'api/orders/export',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $response = $this->get('/acl-admin/resources');

        $response->assertStatus(200);
        $response->assertSee('orders.export');
        $response->assertSee('POST');
    }

    public function test_can_update_resource_configuration(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'orders.export',
            'controller_action' => 'OrderController@export',
            'method'            => 'POST',
            'uri'               => 'api/orders/export',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Export Orders', 'slug' => 'orders.export']);

        $response = $this->put("/acl-admin/resources/{$resource->id}", [
            'is_public'   => '1',
            'operator'    => 'AND',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect("/acl-admin/resources/{$resource->id}/edit");
        $resource->refresh();

        $this->assertTrue($resource->is_public);
        $this->assertEquals('AND', $resource->operator);
        $this->assertTrue($resource->permissions->contains('id', $perm->id));
    }

    public function test_users_index_renders(): void
    {
        $this->createUser(['name' => 'Mario Rossi', 'email' => 'mario@example.com']);

        $response = $this->get('/acl-admin/users');

        $response->assertStatus(200);
        $response->assertSee('Mario Rossi');
        $response->assertSee('mario@example.com');
    }

    public function test_users_search_autocomplete_returns_json(): void
    {
        $user = $this->createUser(['name' => 'Giuseppe Verdi', 'email' => 'giuseppe@music.com']);
        $role = Role::create(['name' => 'Composer', 'slug' => 'composer']);
        $user->assignRole($role);

        $response = $this->get('/acl-admin/users/search?q=Verdi');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'label'    => 'Giuseppe Verdi',
            'sublabel' => 'giuseppe@music.com',
        ]);
    }

    public function test_can_update_user_roles_and_direct_permissions(): void
    {
        $user = $this->createUser(['name' => 'Luigi Nono', 'email' => 'luigi@test.com']);
        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor']);
        $perm = Permission::create(['name' => 'Audit Reports', 'slug' => 'reports.audit']);

        $response = $this->put("/acl-admin/users/{$user->id}", [
            'roles'       => [$role->id],
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect("/acl-admin/users/{$user->id}/edit");
        $user->refresh();

        $this->assertTrue($user->hasRole('auditor'));
        $this->assertTrue($user->hasPermission('reports.audit'));
    }
}
