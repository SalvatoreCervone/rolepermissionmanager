<?php

namespace SalvatoreCervone\RolePermissionManager\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use SalvatoreCervone\RolePermissionManager\Services\RouteScanner;

class SyncAclRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'acl:sync
        {--clean : Remove deprecated routes from the database instead of soft-flagging them}
        {--auto-permissions : Auto-create permissions for newly discovered routes}
        {--notify : Log detailed information about new, updated, and removed routes}';

    /**
     * The console command description.
     */
    protected $description = 'Scan all registered Laravel routes and sync them with the ACL secured_resources table.';

    /**
     * Execute the console command.
     */
    public function handle(RouteScanner $scanner): int
    {
        $this->info('🔍 Scanning routes...');
        $this->newLine();

        $clean = $this->option('clean');
        $autoPermissions = $this->option('auto-permissions');
        $notify = $this->option('notify');

        $summary = $scanner->scan(
            clean: $clean,
            autoPermissions: $autoPermissions,
        );

        // Refresh the ACL cache after syncing.
        AclRegistry::refreshCache();

        // Display summary.
        $this->displaySummary($summary, $notify);

        $this->newLine();
        $this->info('✅ ACL route sync completed. Cache refreshed.');

        return self::SUCCESS;
    }

    /**
     * Display the scan summary.
     */
    protected function displaySummary(array $summary, bool $verbose): void
    {
        $created    = $summary['created'] ?? [];
        $updated    = $summary['updated'] ?? [];
        $deprecated = $summary['deprecated'] ?? [];
        $removed    = $summary['removed'] ?? [];
        $skipped    = $summary['skipped'] ?? [];

        // Overview table.
        $this->table(
            ['Action', 'Count'],
            [
                ['📗 New routes registered', count($created)],
                ['📘 Existing routes updated', count($updated)],
                ['📙 Routes deprecated (soft)', count($deprecated)],
                ['📕 Routes removed (hard)', count($removed)],
                ['⏭️  Routes skipped (excluded)', count($skipped)],
            ]
        );

        if (!$verbose) {
            return;
        }

        // Detailed listing.
        if (!empty($created)) {
            $this->newLine();
            $this->warn('📗 New routes registered:');
            foreach ($created as $identifier) {
                $this->line("   + {$identifier}");
            }
        }

        if (!empty($deprecated)) {
            $this->newLine();
            $this->warn('📙 Routes deprecated (no longer in code):');
            foreach ($deprecated as $identifier) {
                $this->line("   ~ {$identifier}");
            }
        }

        if (!empty($removed)) {
            $this->newLine();
            $this->error('📕 Routes removed from database:');
            foreach ($removed as $identifier) {
                $this->line("   - {$identifier}");
            }
        }
    }
}
