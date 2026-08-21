<?php

return [
    'title'                => 'Esporta & Importa Configurazione',
    'subtitle'             => 'Esporta e importa ruoli, permessi, risorse e regole in formato JSON tra ambienti diversi',
    'export_box_title'     => 'Esporta Configurazione ACL',
    'export_box_desc'      => 'Scarica un backup completo della configurazione ACL corrente in formato JSON.',
    'included_data'        => 'Dati Inclusi nell\'Esportazione',
    'download_json_btn'    => 'Scarica File JSON (.json)',
    'import_box_title'     => 'Importa Configurazione ACL',
    'import_box_desc'      => 'Carica un file JSON di configurazione ACL precedentemente esportato per applicarlo a questo ambiente.',
    'choose_file'          => 'Seleziona File JSON',
    'overwrite_mode'       => 'Modalità Sovrascrittura Completa (Overwrite)',
    'overwrite_mode_help'  => 'Se abilitato, sostituisce completamente i permessi esistenti invece di unirli (merge).',
    'import_btn'           => 'Avvia Importazione JSON',
    'confirm_import'       => 'Sei sicuro di voler importare questo file di configurazione?',
    'invalid_json'         => 'Il file fornito non contiene una struttura JSON valida.',
    'imported_success'     => 'Importazione completata con successo (:roles ruoli, :permissions permessi, :resources risorse, :rules regole scanner aggiornate).',
];
