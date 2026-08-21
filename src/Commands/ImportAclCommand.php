<?php

namespace SalvatoreCervone\RolePermissionManager\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\RolePermissionManager\Services\AclExporterImporter;

class ImportAclCommand extends Command
{
    protected $signature = 'acl:import
                            {file : Path to the JSON configuration file}
                            {--overwrite : Overwrite existing permission assignments instead of merging}';
    protected $description = 'Import ACL configuration from a JSON file';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File '{$filePath}' does not exist.");
            return self::FAILURE;
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data || !is_array($data)) {
            $this->error("Invalid JSON format in file '{$filePath}'.");
            return self::FAILURE;
        }

        $overwrite = (bool) $this->option('overwrite');
        $this->info("Importing ACL data (mode: " . ($overwrite ? 'overwrite' : 'merge') . ")...");

        $stats = AclExporterImporter::importData($data, $overwrite);

        $this->info("✔ ACL import completed successfully!");
        $this->table(['Item', 'Count Processed'], [
            ['Permissions', $stats['permissions']],
            ['Roles', $stats['roles']],
            ['Secured Resources', $stats['resources']],
            ['Scanner Rules', $stats['rules']],
        ]);

        return self::SUCCESS;
    }
}
