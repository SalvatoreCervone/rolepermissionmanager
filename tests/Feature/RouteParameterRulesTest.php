<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Http\Middleware\DynamicAclGuard;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\RouteParameterRule;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;
use Workbench\App\Models\User;

class RouteParameterRulesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('rolepermissionmanager.admin_panel.enabled', true);
        $app['config']->set('rolepermissionmanager.admin_panel.middleware', ['web']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(DynamicAclGuard::class)->group(function () {
            Route::get('/serversegnalazioni/{page}/{destinazione}', function ($page, $destinazione) {
                return "Page: {$page}, Dest: {$destinazione}";
            })->name('serversegnalazioni.gotopage');
        });
    }

    public function test_can_create_route_parameter_rule_via_controller(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'serversegnalazioni.gotopage',
            'type'              => SecuredResource::TYPE_ROUTE,
            'controller_action' => 'JwtauthController@gotopage',
            'method'            => 'GET',
            'uri'               => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $permission = Permission::create([
            'name'   => 'Leggi Elenco',
            'slug'   => 'leggi_elenco',
            'module' => 'Segnalazioni',
        ]);

        $response = $this->post("/acl-admin/routes/{$resource->id}/parameter-rules", [
            'parameter_name'      => 'page',
            'parameter_value'     => 'elenco',
            'is_public'           => false,
            'is_super_admin_only' => false,
            'operator'            => 'OR',
            'permissions'         => [$permission->id],
        ]);

        $response->assertRedirect("/acl-admin/routes/{$resource->id}/edit");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('acl_route_parameter_rules', [
            'secured_resource_id' => $resource->id,
            'parameter_name'      => 'page',
            'parameter_value'     => 'elenco',
        ]);

        $rule = RouteParameterRule::where('parameter_value', 'elenco')->first();
        $this->assertNotNull($rule);
        $this->assertTrue($rule->permissions->contains('slug', 'leggi_elenco'));
    }

    public function test_can_delete_route_parameter_rule(): void
    {
        $resource = SecuredResource::create([
            'identifier'        => 'serversegnalazioni.gotopage',
            'type'              => SecuredResource::TYPE_ROUTE,
            'controller_action' => 'JwtauthController@gotopage',
            'method'            => 'GET',
            'uri'               => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'         => false,
            'operator'          => 'OR',
        ]);

        $rule = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);

        $response = $this->delete("/acl-admin/routes/{$resource->id}/parameter-rules/{$rule->id}");

        $response->assertRedirect("/acl-admin/routes/{$resource->id}/edit");
        $this->assertDatabaseMissing('acl_route_parameter_rules', ['id' => $rule->id]);
    }

    public function test_matched_parameter_rule_grants_access_when_user_has_permission(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        $permElenco = Permission::create(['name' => 'Leggi Elenco', 'slug' => 'leggi_elenco', 'module' => 'Segnalazioni']);
        $permStats = Permission::create(['name' => 'Leggi Statistiche', 'slug' => 'leggi_statistiche', 'module' => 'Segnalazioni']);

        $ruleElenco = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);
        $ruleElenco->permissions()->attach($permElenco->id);

        $ruleStats = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'statistiche',
            'operator'        => 'OR',
        ]);
        $ruleStats->permissions()->attach($permStats->id);

        AclRegistry::refreshCache();

        $user = User::create(['name' => 'Operatore Elenco', 'email' => 'elenco@test.com', 'password' => bcrypt('password')]);
        $user->givePermissionTo('leggi_elenco');

        // User has 'leggi_elenco', so accessing /serversegnalazioni/elenco/1 must succeed (200)
        $this->actingAs($user)
            ->get('/serversegnalazioni/elenco/1')
            ->assertStatus(200)
            ->assertSee('Page: elenco, Dest: 1');

        // User does NOT have 'leggi_statistiche', so accessing /serversegnalazioni/statistiche/1 must fail (403)
        $this->actingAs($user)
            ->get('/serversegnalazioni/statistiche/1')
            ->assertStatus(403);
    }

    public function test_unmatched_parameter_returns_404_when_strict_behavior_configured(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Leggi Elenco', 'slug' => 'leggi_elenco', 'module' => 'Segnalazioni']);
        $rule = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);
        $rule->permissions()->attach($perm->id);

        AclRegistry::refreshCache();

        $user = User::create(['name' => 'Utente Test', 'email' => 'user@test.com', 'password' => bcrypt('password')]);
        $user->givePermissionTo('leggi_elenco');

        // Accessing undeclared page 'hacker_page' when unmatched_parameter_behavior = deny_404 must return 404!
        $this->actingAs($user)
            ->get('/serversegnalazioni/hacker_page/1')
            ->assertStatus(404);
    }

    public function test_unmatched_parameter_returns_403_when_deny_403_behavior_configured(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_403',
            'operator'                     => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Leggi Elenco', 'slug' => 'leggi_elenco', 'module' => 'Segnalazioni']);
        $rule = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);
        $rule->permissions()->attach($perm->id);

        AclRegistry::refreshCache();

        $user = User::create(['name' => 'Utente Test', 'email' => 'user2@test.com', 'password' => bcrypt('password')]);
        $user->givePermissionTo('leggi_elenco');

        // Accessing undeclared page when unmatched_parameter_behavior = deny_403 must return 403!
        $this->actingAs($user)
            ->get('/serversegnalazioni/unknown_page/1')
            ->assertStatus(403);
    }

    public function test_unmatched_parameter_allows_access_when_allow_behavior_configured(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'allow',
            'operator'                     => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Leggi Elenco', 'slug' => 'leggi_elenco', 'module' => 'Segnalazioni']);
        $rule = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);
        $rule->permissions()->attach($perm->id);

        AclRegistry::refreshCache();

        $user = User::create(['name' => 'Utente Test', 'email' => 'user3@test.com', 'password' => bcrypt('password')]);

        // Accessing undeclared page when unmatched_parameter_behavior = allow falls through to base route (unassigned = allow for authenticated)
        $this->actingAs($user)
            ->get('/serversegnalazioni/fallback_page/1')
            ->assertStatus(200)
            ->assertSee('Page: fallback_page, Dest: 1');
    }

    public function test_parameter_rule_can_be_public(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        // Public parameter rule for 'help'
        $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'help',
            'is_public'       => true,
            'operator'        => 'OR',
        ]);

        AclRegistry::refreshCache();

        // An unauthenticated guest can access /serversegnalazioni/help/1
        $this->get('/serversegnalazioni/help/1')
            ->assertStatus(200)
            ->assertSee('Page: help, Dest: 1');
    }

    public function test_super_admin_bypasses_parameter_rules(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        $perm = Permission::create(['name' => 'Secret', 'slug' => 'secret_perm', 'module' => 'Secret']);
        $rule = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'secret',
            'operator'        => 'OR',
        ]);
        $rule->permissions()->attach($perm->id);

        AclRegistry::refreshCache();

        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $admin->assignRole('super-admin');

        // Super Admin bypasses parameter permissions
        $this->actingAs($admin)
            ->get('/serversegnalazioni/secret/1')
            ->assertStatus(200)
            ->assertSee('Page: secret, Dest: 1');
    }

    public function test_routes_edit_view_renders_detected_placeholders_and_rules(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'elenco',
            'operator'        => 'OR',
        ]);

        $response = $this->get("/acl-admin/routes/{$resource->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('{page}');
        $response->assertSee('{destinazione}');
        $response->assertSee('elenco');
        $response->assertSee('404 Not Found');
    }

    public function test_can_access_route_and_filter_menu_evaluates_parameter_rules(): void
    {
        $resource = SecuredResource::create([
            'identifier'                   => 'serversegnalazioni.gotopage',
            'type'                         => SecuredResource::TYPE_ROUTE,
            'controller_action'            => 'JwtauthController@gotopage',
            'method'                       => 'GET',
            'uri'                          => 'serversegnalazioni/{page}/{destinazione}',
            'is_public'                    => false,
            'unmatched_parameter_behavior' => 'deny_404',
            'operator'                     => 'OR',
        ]);

        $permLeggi = Permission::create(['name' => 'Leggi', 'slug' => 'segnalazione.leggi', 'module' => 'Segnalazioni']);
        $permScrivi = Permission::create(['name' => 'Scrivi', 'slug' => 'segnalazione.scrivi', 'module' => 'Segnalazioni']);

        $ruleLeggi = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'visualizzasegnalazioni',
            'operator'        => 'OR',
        ]);
        $ruleLeggi->permissions()->attach($permLeggi->id);

        $ruleScrivi = $resource->parameterRules()->create([
            'parameter_name'  => 'page',
            'parameter_value' => 'inseriscisegnalazioni',
            'operator'        => 'OR',
        ]);
        $ruleScrivi->permissions()->attach($permScrivi->id);

        AclRegistry::refreshCache();

        // User with ONLY 'segnalazione.leggi'
        $reader = User::create(['name' => 'Reader', 'email' => 'reader@test.com', 'password' => bcrypt('password')]);
        $reader->givePermissionTo('segnalazione.leggi');

        // User with NO permissions
        $guest = User::create(['name' => 'No Perms', 'email' => 'noperms@test.com', 'password' => bcrypt('password')]);

        // 1. Direct canAccessRoute assertions
        $this->assertTrue($reader->canAccessRoute('/serversegnalazioni/visualizzasegnalazioni/segnalazioni'));
        $this->assertFalse($reader->canAccessRoute('/serversegnalazioni/inseriscisegnalazioni/segnalazioni'));
        $this->assertFalse($reader->canAccessRoute('/serversegnalazioni/inesistente/segnalazioni'));

        $this->assertFalse($guest->canAccessRoute('/serversegnalazioni/visualizzasegnalazioni/segnalazioni'));
        $this->assertFalse($guest->canAccessRoute('/serversegnalazioni/inseriscisegnalazioni/segnalazioni'));

        // 2. Navigation Menu filtering test
        $menu = [
            [
                'label' => 'Segnalazioni',
                'icon'  => 'pi pi-fw pi-admin',
                'items' => [
                    [
                        'label' => 'Visualizza',
                        'url'   => '/serversegnalazioni/visualizzasegnalazioni/segnalazioni',
                    ],
                    [
                        'label' => 'Inserisci',
                        'url'   => '/serversegnalazioni/inseriscisegnalazioni/segnalazioni',
                    ],
                ],
            ],
        ];

        // Reader sees only 'Visualizza'
        $readerMenu = AclRegistry::filterMenu($menu, $reader);
        $this->assertCount(1, $readerMenu);
        $this->assertCount(1, $readerMenu[0]['items']);
        $this->assertEquals('Visualizza', $readerMenu[0]['items'][0]['label']);

        // User with no permissions sees 0 menu items (parent is dropped because all children are inaccessible)
        $guestMenu = AclRegistry::filterMenu($menu, $guest);
        $this->assertCount(0, $guestMenu);
    }
}
