<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Unit;

use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class HasAclTraitTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Role Assignment Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_assign_role_by_slug(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $user->assignRole('editor');

        $this->assertTrue($user->hasRole('editor'));
        $this->assertCount(1, $user->roles);
    }

    public function test_can_assign_role_by_model_instance(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole($role));
    }

    public function test_can_assign_multiple_roles(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);
        Role::create(['name' => 'Writer', 'slug' => 'writer']);

        $user->assignRole('editor', 'writer');

        $this->assertTrue($user->hasRole('editor'));
        $this->assertTrue($user->hasRole('writer'));
        $this->assertCount(2, $user->fresh()->roles);
    }

    public function test_assigning_same_role_twice_is_idempotent(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $user->assignRole('editor');
        $user->assignRole('editor');

        $this->assertCount(1, $user->fresh()->roles);
    }

    public function test_can_remove_role(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $user->assignRole('editor');
        $this->assertTrue($user->hasRole('editor'));

        $user->removeRole('editor');
        $this->assertFalse($user->fresh()->hasRole('editor'));
    }

    public function test_can_sync_roles(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);
        Role::create(['name' => 'Writer', 'slug' => 'writer']);
        Role::create(['name' => 'Viewer', 'slug' => 'viewer']);

        $user->assignRole('editor', 'writer');
        $user->syncRoles('viewer');

        $this->assertFalse($user->fresh()->hasRole('editor'));
        $this->assertFalse($user->fresh()->hasRole('writer'));
        $this->assertTrue($user->fresh()->hasRole('viewer'));
    }

    public function test_has_any_role(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);
        Role::create(['name' => 'Admin', 'slug' => 'admin']);

        $user->assignRole('editor');

        $this->assertTrue($user->hasAnyRole('editor', 'admin'));
        $this->assertFalse($user->hasAnyRole('admin'));
    }

    public function test_has_all_roles(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);
        Role::create(['name' => 'Writer', 'slug' => 'writer']);

        $user->assignRole('editor', 'writer');

        $this->assertTrue($user->hasAllRoles('editor', 'writer'));
        $this->assertFalse($user->hasAllRoles('editor', 'writer', 'admin'));
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Assignment Tests
    |--------------------------------------------------------------------------
    */

    public function test_can_give_direct_permission(): void
    {
        $user = $this->createUser();
        Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']);

        $user->givePermissionTo('users.delete');

        $this->assertTrue($user->hasPermission('users.delete'));
    }

    public function test_can_revoke_direct_permission(): void
    {
        $user = $this->createUser();
        Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']);

        $user->givePermissionTo('users.delete');
        $user->revokePermissionTo('users.delete');

        // Flush cache to reflect the revocation.
        AclRegistry::flushUserCache($user->getKey());

        $this->assertFalse($user->hasPermission('users.delete'));
    }

    public function test_inherits_permissions_from_role(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $permission = Permission::create(['name' => 'Edit Posts', 'slug' => 'posts.edit']);

        $role->givePermissionTo('posts.edit');
        $user->assignRole('editor');

        $this->assertTrue($user->hasPermission('posts.edit'));
    }

    public function test_has_any_permission(): void
    {
        $user = $this->createUser();
        Permission::create(['name' => 'View Posts', 'slug' => 'posts.view']);
        Permission::create(['name' => 'Edit Posts', 'slug' => 'posts.edit']);

        $user->givePermissionTo('posts.view');

        $this->assertTrue($user->hasAnyPermission('posts.view', 'posts.edit'));
        $this->assertFalse($user->hasAnyPermission('posts.edit', 'posts.delete'));
    }

    public function test_has_all_permissions(): void
    {
        $user = $this->createUser();
        Permission::create(['name' => 'View Posts', 'slug' => 'posts.view']);
        Permission::create(['name' => 'Edit Posts', 'slug' => 'posts.edit']);

        $user->givePermissionTo('posts.view', 'posts.edit');

        $this->assertTrue($user->hasAllPermissions('posts.view', 'posts.edit'));
        $this->assertFalse($user->hasAllPermissions('posts.view', 'posts.delete'));
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin Tests
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_is_detected(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);

        $user->assignRole('super-admin');

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_regular_user_is_not_super_admin(): void
    {
        $user = $this->createUser();
        Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $user->assignRole('editor');

        $this->assertFalse($user->isSuperAdmin());
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Permission Integration Tests
    |--------------------------------------------------------------------------
    */

    public function test_role_can_be_given_and_revoked_permissions(): void
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        Permission::create(['name' => 'View Reports', 'slug' => 'reports.view']);
        Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export']);

        $role->givePermissionTo('reports.view', 'reports.export');
        $this->assertTrue($role->hasPermission('reports.view'));
        $this->assertTrue($role->hasPermission('reports.export'));

        $role->revokePermissionTo('reports.export');
        $role->load('permissions'); // Reload
        $this->assertFalse($role->hasPermission('reports.export'));
    }

    public function test_find_or_create_role(): void
    {
        $role1 = Role::findOrCreate('new-role', 'New Role');
        $role2 = Role::findOrCreate('new-role');

        $this->assertEquals($role1->id, $role2->id);
        $this->assertEquals('New Role', $role2->name);
    }

    public function test_find_or_create_permission(): void
    {
        $perm1 = Permission::findOrCreate('new-perm', 'New Permission', 'Testing');
        $perm2 = Permission::findOrCreate('new-perm');

        $this->assertEquals($perm1->id, $perm2->id);
        $this->assertEquals('Testing', $perm2->module);
    }

    public function test_gate_allows_both_permissions_and_roles(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        Permission::create(['name' => 'Edit Posts', 'slug' => 'posts.edit']);

        $role->givePermissionTo('posts.edit');
        $user->assignRole('editor');

        // Test with permission slug via Gate
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($user)->allows('posts.edit'));

        // Test with role slug via Gate
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($user)->allows('editor'));

        // Test non-assigned ability
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($user)->allows('non_existent'));
    }

    public function test_filter_navigation_menu(): void
    {
        $user = $this->createUser();
        Permission::create(['name' => 'Scrivi RI', 'slug' => 'scrivi_rapportoinformativo']);
        Permission::create(['name' => 'Scrivi RD', 'slug' => 'scrivi_anagraficarelazionedirigenziale']);
        Permission::create(['name' => 'Leggi RD', 'slug' => 'leggi_relazionidirigenziali']);

        // Give user only 'scrivi_rapportoinformativo'
        $user->givePermissionTo('scrivi_rapportoinformativo');

        $menu = [
            [
                'permessi' => ['scrivi_rapportoinformativo', 'scrivi_anagraficarelazionedirigenziale', 'leggi_relazionidirigenziali'],
                'label'    => 'Uff. Valutazioni',
                'icon'     => 'pi pi-fw pi-home',
                'items'    => [
                    [
                        'permessi' => ['scrivi_rapportoinformativo'],
                        'label'    => 'Organico RI',
                        'url'      => '/rapportiinformativi/match',
                    ],
                    [
                        'permessi' => ['scrivi_anagraficarelazionedirigenziale'],
                        'label'    => 'Organico RD',
                        'url'      => '/relazionidirigenziali/match',
                    ],
                ],
            ],
            [
                'permessi' => ['leggi_relazionidirigenziali'],
                'label'    => 'Archivio',
                'url'      => '/archivio',
            ],
        ];

        $filtered = $user->filterNavigation($menu);

        // 'Uff. Valutazioni' is present
        $this->assertCount(1, $filtered);
        $this->assertEquals('Uff. Valutazioni', $filtered[0]['label']);

        // In 'items', only 'Organico RI' is present, 'Organico RD' was stripped
        $this->assertCount(1, $filtered[0]['items']);
        $this->assertEquals('Organico RI', $filtered[0]['items'][0]['label']);

        // Super admin sees all
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->assignRole('super-admin');
        $this->assertCount(2, $user->filterNavigation($menu));
    }
}


