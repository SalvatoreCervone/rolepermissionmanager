<?php

namespace SalvatoreCervone\RolePermissionManager\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\RolePermissionManager\Services\AclExporterImporter;

class ExportAclCommand extends Command
{
    protected $signature = 'acl:export {--path= : File path to save the export JSON}';
    protected $description = 'Export ACL roles, permissions, resources, and scanner rules to a JSON file';

    public function handle(): int
    {
        $data = AclExporterImporter::exportData();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $path = $this->option('path') ?: storage_path('app/acl-export-' . date('Y-m-d_His') . '.json');

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $json);

        $this->info("✔ ACL configuration exported successfully to:");
        $this->line($path);
        $this->line("Counts: " . count($data['roles']) . " roles, " . count($data['permissions']) . " permissions, " . count($data['resources']) . " resources, " . count($data['rules']) . " scanner rules.");

        return self::SUCCESS;
    }
}
