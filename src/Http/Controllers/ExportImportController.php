<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Services\AclExporterImporter;
use SalvatoreCervone\RolePermissionManager\Services\AuditLogger;
use Symfony\Component\HttpFoundation\Response;

class ExportImportController extends Controller
{
    /**
     * Display export & import management page.
     */
    public function index()
    {
        return view('acl::export_import.index');
    }

    /**
     * Export ACL configuration as downloadable JSON file.
     */
    public function export()
    {
        $data = AclExporterImporter::exportData();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $filename = 'acl-backup-' . date('Y-m-d_His') . '.json';

        AuditLogger::log('acl_exported', 'ExportImport', $filename, 'Downloaded complete ACL configuration JSON backup');

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import ACL configuration from uploaded JSON file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'      => 'required|file|mimes:json,txt',
            'overwrite' => 'nullable|boolean',
        ]);

        $jsonContent = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($jsonContent, true);

        if (!$data || !is_array($data)) {
            return redirect()->back()->with('error', __('acl::export_import.invalid_json'));
        }

        $overwrite = (bool) $request->get('overwrite', false);
        $stats = AclExporterImporter::importData($data, $overwrite);

        AuditLogger::log(
            'acl_imported',
            'ExportImport',
            'JSON Import',
            "Imported ACL configuration (Roles: {$stats['roles']}, Perms: {$stats['permissions']}, Resources: {$stats['resources']}, Rules: {$stats['rules']})"
        );

        return redirect()->route('acl.export_import.index')->with('success', __('acl::export_import.imported_success', [
            'roles'       => $stats['roles'],
            'permissions' => $stats['permissions'],
            'resources'   => $stats['resources'],
            'rules'       => $stats['rules'],
        ]));
    }
}
