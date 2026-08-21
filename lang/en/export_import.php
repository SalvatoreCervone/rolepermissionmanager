<?php

return [
    'title'                => 'Export & Import Configuration',
    'subtitle'             => 'Export and import roles, permissions, resources, and rules across environments in JSON format',
    'export_box_title'     => 'Export ACL Configuration',
    'export_box_desc'      => 'Download a full snapshot of your current ACL configuration as a JSON file.',
    'included_data'        => 'Data Included in Export',
    'download_json_btn'    => 'Download JSON File (.json)',
    'import_box_title'     => 'Import ACL Configuration',
    'import_box_desc'      => 'Upload an exported ACL configuration JSON file to apply settings to this environment.',
    'choose_file'          => 'Select JSON File',
    'overwrite_mode'       => 'Full Overwrite Mode',
    'overwrite_mode_help'  => 'If enabled, completely replaces existing permission associations instead of merging them.',
    'import_btn'           => 'Run JSON Import',
    'confirm_import'       => 'Are you sure you want to import this configuration file?',
    'invalid_json'         => 'The uploaded file does not contain a valid JSON structure.',
    'imported_success'     => 'Import completed successfully (:roles roles, :permissions permissions, :resources resources, :rules scanner rules updated).',
];
