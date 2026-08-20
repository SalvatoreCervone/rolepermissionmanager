<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────
        // 1. Create Demo User
        // ──────────────────────────────────────
        $admin = User::create([
            'name'     => 'Admin Demo',
            'email'    => 'admin@demo.test',
            'password' => bcrypt('password'),
        ]);

        $editor = User::create([
            'name'     => 'Editor Demo',
            'email'    => 'editor@demo.test',
            'password' => bcrypt('password'),
        ]);

        // ──────────────────────────────────────
        // 2. Create Roles
        // ──────────────────────────────────────
        $superAdmin = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full access to everything']);
        $adminRole  = Role::create(['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Manages users and settings']);
        $editorRole = Role::create(['name' => 'Editor', 'slug' => 'editor', 'description' => 'Can edit and publish content']);
        $viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only access']);

        // ──────────────────────────────────────
        // 3. Create Permissions (grouped by module)
        // ──────────────────────────────────────
        $perms = [];

        // Users module
        $perms['users.index']   = Permission::create(['name' => 'List Users', 'slug' => 'users.index', 'module' => 'Users', 'description' => 'View the user list']);
        $perms['users.create']  = Permission::create(['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'Users', 'description' => 'Create new users']);
        $perms['users.edit']    = Permission::create(['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'Users', 'description' => 'Edit existing users']);
        $perms['users.delete']  = Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'Users', 'description' => 'Delete users from the system']);

        // Posts module
        $perms['posts.index']   = Permission::create(['name' => 'List Posts', 'slug' => 'posts.index', 'module' => 'Posts', 'description' => 'View the post list']);
        $perms['posts.create']  = Permission::create(['name' => 'Create Posts', 'slug' => 'posts.create', 'module' => 'Posts', 'description' => 'Create new posts']);
        $perms['posts.edit']    = Permission::create(['name' => 'Edit Posts', 'slug' => 'posts.edit', 'module' => 'Posts', 'description' => 'Edit existing posts']);
        $perms['posts.delete']  = Permission::create(['name' => 'Delete Posts', 'slug' => 'posts.delete', 'module' => 'Posts', 'description' => 'Delete posts']);
        $perms['posts.publish'] = Permission::create(['name' => 'Publish Posts', 'slug' => 'posts.publish', 'module' => 'Posts', 'description' => 'Publish draft posts']);

        // Reports module
        $perms['reports.view']   = Permission::create(['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'Reports', 'description' => 'Access analytics reports']);
        $perms['reports.export'] = Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'Reports', 'description' => 'Export reports as CSV/PDF']);

        // Settings module
        $perms['settings.manage'] = Permission::create(['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'Settings', 'description' => 'Change application settings']);

        // ──────────────────────────────────────
        // 4. Assign Permissions to Roles
        // ──────────────────────────────────────
        $adminRole->givePermissionTo(
            'users.index', 'users.create', 'users.edit', 'users.delete',
            'posts.index', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish',
            'reports.view', 'reports.export',
            'settings.manage'
        );

        $editorRole->givePermissionTo(
            'users.index',
            'posts.index', 'posts.create', 'posts.edit', 'posts.publish',
            'reports.view'
        );

        $viewerRole->givePermissionTo(
            'users.index', 'posts.index', 'reports.view'
        );

        // ──────────────────────────────────────
        // 5. Assign Roles to Users
        // ──────────────────────────────────────
        $admin->assignRole($superAdmin);
        $editor->assignRole($editorRole);

        // ──────────────────────────────────────
        // 6. Create Simulated Secured Resources (routes)
        // ──────────────────────────────────────
        $routes = [
            ['identifier' => 'users.index',   'method' => 'GET',    'uri' => 'api/users',           'controller_action' => 'App\\Http\\Controllers\\UserController@index'],
            ['identifier' => 'users.store',   'method' => 'POST',   'uri' => 'api/users',           'controller_action' => 'App\\Http\\Controllers\\UserController@store'],
            ['identifier' => 'users.show',    'method' => 'GET',    'uri' => 'api/users/{id}',      'controller_action' => 'App\\Http\\Controllers\\UserController@show'],
            ['identifier' => 'users.update',  'method' => 'PUT',    'uri' => 'api/users/{id}',      'controller_action' => 'App\\Http\\Controllers\\UserController@update'],
            ['identifier' => 'users.destroy', 'method' => 'DELETE', 'uri' => 'api/users/{id}',      'controller_action' => 'App\\Http\\Controllers\\UserController@destroy'],
            ['identifier' => 'posts.index',   'method' => 'GET',    'uri' => 'api/posts',           'controller_action' => 'App\\Http\\Controllers\\PostController@index'],
            ['identifier' => 'posts.store',   'method' => 'POST',   'uri' => 'api/posts',           'controller_action' => 'App\\Http\\Controllers\\PostController@store'],
            ['identifier' => 'posts.show',    'method' => 'GET',    'uri' => 'api/posts/{id}',      'controller_action' => 'App\\Http\\Controllers\\PostController@show', 'is_public' => true],
            ['identifier' => 'posts.update',  'method' => 'PUT',    'uri' => 'api/posts/{id}',      'controller_action' => 'App\\Http\\Controllers\\PostController@update'],
            ['identifier' => 'posts.destroy', 'method' => 'DELETE', 'uri' => 'api/posts/{id}',      'controller_action' => 'App\\Http\\Controllers\\PostController@destroy'],
            ['identifier' => 'posts.publish', 'method' => 'POST',   'uri' => 'api/posts/{id}/publish', 'controller_action' => 'App\\Http\\Controllers\\PostController@publish'],
            ['identifier' => 'reports.index', 'method' => 'GET',    'uri' => 'api/reports',         'controller_action' => 'App\\Http\\Controllers\\ReportController@index'],
            ['identifier' => 'reports.export','method' => 'POST',   'uri' => 'api/reports/export',  'controller_action' => 'App\\Http\\Controllers\\ReportController@export'],
            ['identifier' => 'settings.index','method' => 'GET',    'uri' => 'admin/settings',      'controller_action' => 'App\\Http\\Controllers\\SettingsController@index'],
            ['identifier' => 'settings.update','method'=> 'PUT',    'uri' => 'admin/settings',      'controller_action' => 'App\\Http\\Controllers\\SettingsController@update', 'operator' => 'AND'],
            ['identifier' => 'home',          'method' => 'GET',    'uri' => '/',                   'controller_action' => 'App\\Http\\Controllers\\HomeController@index', 'is_public' => true],
        ];

        foreach ($routes as $routeData) {
            $resource = SecuredResource::create([
                'identifier'        => $routeData['identifier'],
                'method'            => $routeData['method'],
                'uri'               => $routeData['uri'],
                'controller_action' => $routeData['controller_action'],
                'is_public'         => $routeData['is_public'] ?? false,
                'operator'          => $routeData['operator'] ?? 'OR',
            ]);

            // Link matching permissions to resources by identifier convention.
            $slug = $routeData['identifier'];
            if (isset($perms[$slug])) {
                $resource->permissions()->attach($perms[$slug]->id);
            }
        }

        $this->command->info('✅ Demo data seeded: 2 users, 4 roles, 12 permissions, 16 resources.');
        $this->command->info('   Login: admin@demo.test / password');
    }
}
