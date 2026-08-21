<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\AuditLog;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclExporterImporter;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class NewAclFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['rolepermissionmanager.models.user' => User::class]);

        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->admin->roles()->attach($role->id);
    }

    public function test_matrix_index_renders_and_toggle_works(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $perm = Permission::create(['name' => 'Publish Post', 'slug' => 'posts.publish']);

        $response = $this->actingAs($this->admin)->get('/acl-admin/matrix');
        $response->assertStatus(200);
        $response->assertSee('Publish Post');

        // Toggle attach
        $toggleResp = $this->actingAs($this->admin)->postJson('/acl-admin/matrix/toggle', [
            'role_id'       => $role->id,
            'permission_id' => $perm->id,
        ]);
        $toggleResp->assertStatus(200);
        $toggleResp->assertJson(['success' => true, 'action' => 'attached']);
        $this->assertTrue($role->permissions()->where('permission_id', $perm->id)->exists());

        // Toggle detach
        $toggleResp2 = $this->actingAs($this->admin)->postJson('/acl-admin/matrix/toggle', [
            'role_id'       => $role->id,
            'permission_id' => $perm->id,
        ]);
        $toggleResp2->assertStatus(200);
        $toggleResp2->assertJson(['success' => true, 'action' => 'detached']);
        $this->assertFalse($role->permissions()->where('permission_id', $perm->id)->exists());
    }

    public function test_simulator_evaluates_access_accurately(): void
    {
        $user = User::create(['name' => 'Subscriber', 'email' => 'sub@test.com', 'password' => 'secret']);
        $res = SecuredResource::create([
            'identifier' => 'premium.content',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'is_public'  => false,
            'operator'   => 'OR',
        ]);
        $perm = Permission::create(['name' => 'View Premium', 'slug' => 'premium.view']);
        $res->permissions()->attach($perm->id);
        AclRegistry::refreshCache();

        // 1. Without permission -> Denied
        $respDenied = $this->actingAs($this->admin)->get("/acl-admin/simulator?user_id={$user->id}&identifier=premium.content");
        $respDenied->assertStatus(200);
        $respDenied->assertSee('403 Forbidden');

        // 2. Grant permission using givePermissionTo -> Allowed
        $user->givePermissionTo($perm);

        $respAllowed = $this->actingAs($this->admin)->get("/acl-admin/simulator?user_id={$user->id}&identifier=premium.content");
        $respAllowed->assertStatus(200);
        $respAllowed->assertSee('200 OK');
    }

    public function test_cli_acl_check_command(): void
    {
        $user = User::create(['name' => 'Mario', 'email' => 'mario@test.com', 'password' => 'secret']);
        SecuredResource::create([
            'identifier' => 'dashboard.view',
            'type'       => SecuredResource::TYPE_ROUTE,
            'is_public'  => true,
        ]);

        $code = Artisan::call('acl:check', [
            'user'     => $user->id,
            'resource' => 'dashboard.view',
        ]);

        $this->assertEquals(0, $code);
        $output = Artisan::output();
        $this->assertStringContainsString('VERDICT: AUTHORIZED', $output);
    }

    public function test_export_and_import_flow(): void
    {
        Role::create(['name' => 'Manager', 'slug' => 'manager']);
        Permission::create(['name' => 'Manage Users', 'slug' => 'users.manage']);

        // Test export download endpoint
        $exportResp = $this->actingAs($this->admin)->get('/acl-admin/export-import/export');
        $exportResp->assertStatus(200);
        $exportResp->assertHeader('Content-Type', 'application/json');

        // Test import endpoint
        $fakeData = [
            'version'     => '1.1',
            'permissions' => [
                ['name' => 'Imported Perm', 'slug' => 'imported.perm', 'module' => 'Test'],
            ],
            'roles' => [
                ['name' => 'Imported Role', 'slug' => 'imported-role', 'permissions' => ['imported.perm']],
            ],
            'resources' => [],
            'rules'     => [],
        ];

        $tempFile = UploadedFile::fake()->createWithContent('acl-import.json', json_encode($fakeData));

        $importResp = $this->actingAs($this->admin)->post('/acl-admin/export-import/import', [
            'file'      => $tempFile,
            'overwrite' => 1,
        ]);

        $importResp->assertRedirect('/acl-admin/export-import');
        $this->assertDatabaseHas('acl_permissions', ['slug' => 'imported.perm']);
        $this->assertDatabaseHas('acl_roles', ['slug' => 'imported-role']);
    }

    public function test_audit_logs_index_and_clear(): void
    {
        AuditLog::create([
            'user_name'         => 'Admin User',
            'action'            => 'role_created',
            'target_type'       => 'Role',
            'target_identifier' => 'Tester',
            'details'           => 'Created test role',
            'created_at'        => now(),
        ]);

        $resp = $this->actingAs($this->admin)->get('/acl-admin/audit-logs');
        $resp->assertStatus(200);
        $resp->assertSee('Created test role');

        $clearResp = $this->actingAs($this->admin)->post('/acl-admin/audit-logs/clear');
        $clearResp->assertRedirect('/acl-admin/audit-logs');
        $this->assertDatabaseCount('acl_audit_logs', 0);
    }
}
