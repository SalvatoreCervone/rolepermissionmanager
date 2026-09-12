<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Config;
use SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class DummyTargetService
{
    public function internalExport()
    {
        AclRegistry::protect();
        return 'exported_data';
    }

    public function explicitProtectedAction()
    {
        AclRegistry::protect('accounting.run_payroll');
        return 'payroll_done';
    }
}

class AutoProtectedResourceTest extends TestCase
{
    public function test_protect_auto_discovers_caller_creates_record_and_denies_access(): void
    {
        $dummy = new DummyTargetService();

        // Calling DummyTargetService@internalExport without prior configuration must fail-closed (403)
        $exceptionThrown = false;
        try {
            $dummy->internalExport();
        } catch (UnauthorizedException $e) {
            $exceptionThrown = true;
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertStringContainsString('DummyTargetService@internalExport', $e->getMessage());
            $this->assertStringContainsString('unconfigured', strtolower($e->getMessage()));
        }

        $this->assertTrue($exceptionThrown, 'Expected UnauthorizedException was not thrown.');

        // Verify the resource was created in the database with is_unconfigured = true
        $resource = SecuredResource::where('identifier', 'DummyTargetService@internalExport')->first();
        $this->assertNotNull($resource);
        $this->assertEquals(SecuredResource::TYPE_CUSTOM, $resource->type);
        $this->assertTrue($resource->is_unconfigured);
        $this->assertFalse($resource->is_public);
        $this->assertEquals(DummyTargetService::class . '@internalExport', $resource->controller_action);
    }

    public function test_protect_with_explicit_identifier_creates_record_and_denies_access(): void
    {
        $dummy = new DummyTargetService();

        $exceptionThrown = false;
        try {
            $dummy->explicitProtectedAction();
        } catch (UnauthorizedException $e) {
            $exceptionThrown = true;
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertStringContainsString('accounting.run_payroll', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown);

        $resource = SecuredResource::where('identifier', 'accounting.run_payroll')->first();
        $this->assertNotNull($resource);
        $this->assertTrue($resource->is_unconfigured);
        $this->assertEquals(DummyTargetService::class . '@explicitProtectedAction', $resource->controller_action);
    }

    public function test_unconfigured_resource_blocks_super_admin_even_with_all_access(): void
    {
        // Setup Super Admin user
        $superAdminUser = $this->createUser();
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $superAdminUser->assignRole('super-admin');

        Config::set('rolepermissionmanager.super_admin.role_slug', 'super-admin');
        Config::set('rolepermissionmanager.super_admin.all_access', true);

        // Auto-discover / create unconfigured resource
        $dummy = new DummyTargetService();
        try {
            $dummy->internalExport();
        } catch (\Throwable $e) {
            // Expected initial deny
        }

        $identifier = 'DummyTargetService@internalExport';

        // 1. hasAccess() MUST be false for Super Admin
        $this->assertFalse(
            AclRegistry::hasAccess($identifier, $superAdminUser),
            'hasAccess must return FALSE for Super Admin when resource is unconfigured'
        );

        // 2. authorize() MUST throw 403 for Super Admin
        $this->expectException(UnauthorizedException::class);
        AclRegistry::authorize($identifier, $superAdminUser);
    }

    public function test_unconfigured_resource_blocks_normal_user_with_direct_permissions(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'service.sensitive_action',
            'type'              => SecuredResource::TYPE_CUSTOM,
            'is_unconfigured'   => true,
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Do Sensitive', 'slug' => 'sensitive.do']);
        $resource->permissions()->attach($perm->id);
        AclRegistry::refreshCache();

        $user = $this->createUser();
        $user->givePermissionTo($perm);
        AclRegistry::flushUserCache($user->getKey());

        // Even though user has the permission, is_unconfigured = true strictly denies
        $this->assertFalse(AclRegistry::hasAccess('service.sensitive_action', $user));

        $this->expectException(UnauthorizedException::class);
        AclRegistry::authorize('service.sensitive_action', $user);
    }

    public function test_updating_resource_clears_unconfigured_flag_and_allows_authorized_access(): void
    {
        // 1. Auto-discover
        $dummy = new DummyTargetService();
        try {
            $dummy->internalExport();
        } catch (\Throwable $e) {
        }

        $identifier = 'DummyTargetService@internalExport';
        $resource = SecuredResource::where('identifier', $identifier)->firstOrFail();
        $this->assertTrue($resource->is_unconfigured);

        // 2. Setup admin user for controller request
        $adminUser = $this->createUser(['email' => 'admin@example.com']);
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $adminUser->assignRole('super-admin');

        $perm = Permission::create(['name' => 'Export Data', 'slug' => 'data.export']);

        // 3. Admin updates the resource through SecuredResourceController
        $response = $this->actingAs($adminUser)->put(route('acl.resources.update', $resource->id), [
            'identifier'        => $identifier,
            'description'       => 'Configured export function',
            'is_public'         => '0',
            'operator'          => 'OR',
            'permissions'       => [$perm->id],
        ]);

        $response->assertRedirect(route('acl.resources.edit', $resource->id));

        // Verify database updated
        $resource->refresh();
        $this->assertFalse($resource->is_unconfigured);
        $this->assertEquals('Configured export function', $resource->description);

        // 4. Normal user without permission is denied with regular permission check
        $regularUser = $this->createUser(['email' => 'regular@example.com']);
        $this->assertFalse(AclRegistry::hasAccess($identifier, $regularUser));

        // 5. Authorized user now has access!
        $authorizedUser = $this->createUser(['email' => 'authorized@example.com']);
        $authorizedUser->givePermissionTo($perm);
        $this->assertTrue(AclRegistry::hasAccess($identifier, $authorizedUser));

        // 6. Super Admin now has full access!
        $this->assertTrue(AclRegistry::hasAccess($identifier, $adminUser));
    }

    public function test_admin_panel_displays_unconfigured_filter_and_count(): void
    {
        $adminUser = $this->createUser();
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $adminUser->assignRole('super-admin');

        // Create 2 unconfigured and 1 configured resources
        SecuredResource::create([
            'identifier'      => 'unconfigured.one',
            'type'            => SecuredResource::TYPE_CUSTOM,
            'is_unconfigured' => true,
        ]);
        SecuredResource::create([
            'identifier'      => 'unconfigured.two',
            'type'            => SecuredResource::TYPE_CUSTOM,
            'is_unconfigured' => true,
        ]);
        SecuredResource::create([
            'identifier'      => 'configured.regular',
            'type'            => SecuredResource::TYPE_CUSTOM,
            'is_unconfigured' => false,
        ]);

        // Access index page
        $response = $this->actingAs($adminUser)->get(route('acl.resources.index'));
        $response->assertOk();
        $response->assertViewHas('unconfiguredCount', 2);
        $response->assertSee('unconfigured.one');
        $response->assertSee('unconfigured.two');
        $response->assertSee('configured.regular');

        // Filter by unconfigured only
        $filterResponse = $this->actingAs($adminUser)->get(route('acl.resources.index', ['status' => 'unconfigured']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('unconfigured.one');
        $filterResponse->assertSee('unconfigured.two');
        $filterResponse->assertDontSee('configured.regular');
    }
}
