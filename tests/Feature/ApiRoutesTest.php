<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    public function test_api_can_list_roles(): void
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager']);

        $response = $this->getJson('/acl-api/roles');

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => 'manager']);
    }

    public function test_api_can_list_permissions(): void
    {
        Permission::create(['name' => 'Edit Articles', 'slug' => 'articles.edit']);

        $response = $this->getJson('/acl-api/permissions');

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => 'articles.edit']);
    }

    public function test_api_can_list_resources(): void
    {
        SecuredResource::create([
            'identifier'        => 'articles.index',
            'method'            => 'GET',
            'uri'               => 'articles',
            'controller_action' => 'ArticleController@index',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $response = $this->getJson('/acl-api/resources');

        $response->assertStatus(200);
        $response->assertJsonFragment(['identifier' => 'articles.index']);
    }

    public function test_api_can_trigger_sync(): void
    {
        $response = $this->postJson('/acl-api/sync');

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }
}
