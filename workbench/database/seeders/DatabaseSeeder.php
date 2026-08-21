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
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name'     => 'Admin Demo',
                'password' => bcrypt('password'),
            ]
        );

        $editor = User::firstOrCreate(
            ['email' => 'editor@demo.test'],
            [
                'name'     => 'Editor Demo',
                'password' => bcrypt('password'),
            ]
        );

        // ──────────────────────────────────────
        // 2. Create Roles
        // ──────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin', 'description' => 'Full access to everything']);
        $adminRole  = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator', 'description' => 'Manages users and settings']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor', 'description' => 'Can edit and publish content']);
        $viewerRole = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer', 'description' => 'Read-only access']);

        // ──────────────────────────────────────
        // 3. Create Permissions (grouped by module)
        // ──────────────────────────────────────
        $perms = [];

        // Users module
        $perms['users.index']   = Permission::firstOrCreate(['slug' => 'users.index'], ['name' => 'List Users', 'module' => 'Users', 'description' => 'View the user list']);
        $perms['users.create']  = Permission::firstOrCreate(['slug' => 'users.create'], ['name' => 'Create Users', 'module' => 'Users', 'description' => 'Create new users']);
        $perms['users.edit']    = Permission::firstOrCreate(['slug' => 'users.edit'], ['name' => 'Edit Users', 'module' => 'Users', 'description' => 'Edit existing users']);
        $perms['users.delete']  = Permission::firstOrCreate(['slug' => 'users.delete'], ['name' => 'Delete Users', 'module' => 'Users', 'description' => 'Delete users from the system']);

        // Posts module
        $perms['posts.index']   = Permission::firstOrCreate(['slug' => 'posts.index'], ['name' => 'List Posts', 'module' => 'Posts', 'description' => 'View the post list']);
        $perms['posts.create']  = Permission::firstOrCreate(['slug' => 'posts.create'], ['name' => 'Create Posts', 'module' => 'Posts', 'description' => 'Create new posts']);
        $perms['posts.edit']    = Permission::firstOrCreate(['slug' => 'posts.edit'], ['name' => 'Edit Posts', 'module' => 'Posts', 'description' => 'Edit existing posts']);
        $perms['posts.delete']  = Permission::firstOrCreate(['slug' => 'posts.delete'], ['name' => 'Delete Posts', 'module' => 'Posts', 'description' => 'Delete posts']);
        $perms['posts.publish'] = Permission::firstOrCreate(['slug' => 'posts.publish'], ['name' => 'Publish Posts', 'module' => 'Posts', 'description' => 'Publish draft posts']);

        // Reports module
        $perms['reports.view']   = Permission::firstOrCreate(['slug' => 'reports.view'], ['name' => 'View Reports', 'module' => 'Reports', 'description' => 'Access analytics reports']);
        $perms['reports.export'] = Permission::firstOrCreate(['slug' => 'reports.export'], ['name' => 'Export Reports', 'module' => 'Reports', 'description' => 'Export reports as CSV/PDF']);

        // Settings module
        $perms['settings.manage'] = Permission::firstOrCreate(['slug' => 'settings.manage'], ['name' => 'Manage Settings', 'module' => 'Settings', 'description' => 'Change application settings']);

        // ──────────────────────────────────────
        // 4. Assign Permissions to Roles
        // ──────────────────────────────────────
        $adminRole->syncPermissions(
            'users.index', 'users.create', 'users.edit', 'users.delete',
            'posts.index', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish',
            'reports.view', 'reports.export',
            'settings.manage'
        );

        $editorRole->syncPermissions(
            'users.index',
            'posts.index', 'posts.create', 'posts.edit', 'posts.publish',
            'reports.view'
        );

        $viewerRole->syncPermissions(
            'users.index', 'posts.index', 'reports.view'
        );

        // ──────────────────────────────────────
        // 5. Assign Roles to Users
        // ──────────────────────────────────────
        $admin->syncRoles($superAdmin);
        $editor->syncRoles($editorRole);

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
            $resource = SecuredResource::firstOrCreate(
                ['identifier' => $routeData['identifier']],
                [
                    'method'            => $routeData['method'],
                    'uri'               => $routeData['uri'],
                    'controller_action' => $routeData['controller_action'],
                    'is_public'         => $routeData['is_public'] ?? false,
                    'operator'          => $routeData['operator'] ?? 'OR',
                ]
            );

            // Link matching permissions to resources by identifier convention.
            $slug = $routeData['identifier'];
            if (isset($perms[$slug])) {
                $resource->permissions()->syncWithoutDetaching([$perms[$slug]->id]);
            }
        }

        if (isset($this->command)) {
            $this->command->info('✅ Demo data seeded: 2 users, 4 roles, 12 permissions, 16 resources.');
            $this->command->info('   Login: admin@demo.test / password');
        }
    }
}
