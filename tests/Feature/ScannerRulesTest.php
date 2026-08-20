<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SalvatoreCervone\RolePermissionManager\Models\ScannerRule;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class ScannerRulesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Enable admin panel for tests without auth redirection.
        $app['config']->set('rolepermissionmanager.admin_panel.enabled', true);
        $app['config']->set('rolepermissionmanager.admin_panel.middleware', ['web']);

        // Define sample routes for scanning tests.
        $app['router']->get('/test/reports/export', fn() => 'export')->name('reports.export');
        $app['router']->get('/test/posts/publish', fn() => 'publish')->name('posts.publish');
        $app['router']->get('/test/sanctum/csrf-cookie', fn() => 'cookie')->name('sanctum.csrf-cookie');
    }

    public function test_can_view_scanner_rules_page(): void
    {
        $response = $this->get('/acl-admin/scanner-rules');

        $response->assertStatus(200);
        $response->assertSee('Scanner Rules');
    }

    public function test_can_create_scanner_exclude_rule(): void
    {
        $response = $this->post('/acl-admin/scanner-rules', [
            'type'        => 'exclude',
            'target'      => 'name',
            'pattern'     => 'reports.export',
            'description' => 'Ignore export route',
        ]);

        $response->assertRedirect('/acl-admin/scanner-rules');
        $this->assertDatabaseHas('acl_scanner_rules', [
            'type'    => 'exclude',
            'target'  => 'name',
            'pattern' => 'reports.export',
        ]);
    }

    public function test_can_toggle_scanner_rule(): void
    {
        $rule = ScannerRule::create([
            'type'      => 'exclude',
            'target'    => 'prefix',
            'pattern'   => 'api/v1/internal',
            'is_active' => true,
        ]);

        $response = $this->patch("/acl-admin/scanner-rules/{$rule->id}/toggle");

        $response->assertRedirect('/acl-admin/scanner-rules');
        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_can_delete_scanner_rule(): void
    {
        $rule = ScannerRule::create([
            'type'      => 'include',
            'target'    => 'name',
            'pattern'   => 'custom.route',
            'is_active' => true,
        ]);

        $response = $this->delete("/acl-admin/scanner-rules/{$rule->id}");

        $response->assertRedirect('/acl-admin/scanner-rules');
        $this->assertDatabaseMissing('acl_scanner_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_dynamic_exclude_rule_is_honored_by_scanner(): void
    {
        ScannerRule::create([
            'type'      => 'exclude',
            'target'    => 'name',
            'pattern'   => 'posts.publish',
            'is_active' => true,
        ]);

        AclRegistry::refreshCache();

        Artisan::call('acl:sync', ['--clean' => true]);

        $this->assertDatabaseMissing('acl_secured_resources', [
            'identifier' => 'posts.publish',
        ]);
    }

    public function test_dynamic_include_rule_overrides_config_exclusion(): void
    {
        // 'sanctum.*' is excluded by default in config
        ScannerRule::create([
            'type'      => 'include',
            'target'    => 'name',
            'pattern'   => 'sanctum.csrf-cookie',
            'is_active' => true,
        ]);

        AclRegistry::refreshCache();

        Artisan::call('acl:sync');

        $this->assertDatabaseHas('acl_secured_resources', [
            'identifier' => 'sanctum.csrf-cookie',
        ]);
    }

    public function test_api_can_list_scanner_rules(): void
    {
        ScannerRule::create([
            'type'      => 'exclude',
            'target'    => 'name',
            'pattern'   => 'api.test',
            'is_active' => true,
        ]);

        $response = $this->getJson('/acl-api/scanner-rules');

        $response->assertStatus(200);
        $response->assertJsonFragment(['pattern' => 'api.test']);
    }
}
