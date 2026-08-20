<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class CustomResourceAccessTest extends TestCase
{
    public function test_acl_registry_has_access_and_authorize_for_custom_resource(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'CorsoController@dettagliocorsi',
            'type'              => SecuredResource::TYPE_CUSTOM,
            'description'       => 'Dettagli corsi interni',
            'controller_action' => 'App\Http\Controllers\CorsoController@dettagliocorsi',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $perm = Permission::create(['name' => 'View Details', 'slug' => 'corsi.dettaglio']);
        $resource->permissions()->attach($perm->id);
        AclRegistry::refreshCache();

        $user = $this->createUser();

        // 1. User without permission is denied
        $this->assertFalse(AclRegistry::hasAccess('CorsoController@dettagliocorsi', $user));

        $this->expectException(UnauthorizedException::class);
        AclRegistry::authorize('CorsoController@dettagliocorsi', $user);
    }

    public function test_authorized_user_can_access_custom_resource(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'CorsoController@dettagliocorsi',
            'type'              => SecuredResource::TYPE_CUSTOM,
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $perm = Permission::create(['name' => 'View Details', 'slug' => 'corsi.dettaglio']);
        $resource->permissions()->attach($perm->id);
        AclRegistry::refreshCache();

        $user = $this->createUser();
        $user->givePermissionTo($perm);
        AclRegistry::flushUserCache($user->getKey());

        // Authorized user has access
        $this->assertTrue(AclRegistry::hasAccess('CorsoController@dettagliocorsi', $user));

        // Authorize does not throw exception
        AclRegistry::authorize('CorsoController@dettagliocorsi', $user);
        $this->assertTrue(true);
    }

    public function test_public_custom_resource_is_accessible_by_anyone(): void
    {
        SecuredResource::create([
            'identifier' => 'public.widget',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'is_public'  => true,
            'operator'   => 'OR',
        ]);
        AclRegistry::refreshCache();

        $this->assertTrue(AclRegistry::hasAccess('public.widget', null));
    }

    public function test_super_admin_bypasses_custom_resource_checks(): void
    {
        $resource = SecuredResource::create([
            'identifier' => 'admin.secret_tool',
            'type'       => SecuredResource::TYPE_CUSTOM,
            'is_public'  => false,
            'operator'   => 'OR',
        ]);
        $perm = Permission::create(['name' => 'Super Secret', 'slug' => 'secret.perm']);
        $resource->permissions()->attach($perm->id);
        AclRegistry::refreshCache();

        $user = $this->createUser();
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->assignRole('super-admin');
        AclRegistry::flushUserCache($user->getKey());

        $this->assertTrue(AclRegistry::hasAccess('admin.secret_tool', $user));
    }

    public function test_sync_command_preserves_custom_resources(): void
    {
        $custom = SecuredResource::create([
            'identifier'        => 'CorsoController@dettagliocorsi',
            'type'              => SecuredResource::TYPE_CUSTOM,
            'description'       => 'Custom Service Method',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $this->artisan('acl:sync', ['--clean' => true]);

        $this->assertDatabaseHas('acl_secured_resources', [
            'id'            => $custom->id,
            'identifier'    => 'CorsoController@dettagliocorsi',
            'is_deprecated' => false,
        ]);
    }
}
