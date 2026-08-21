<?php

return [
    'title'                 => 'Dashboard',
    'subtitle'              => 'Panoramica e stato di salute del tuo sistema ACL',
    'security_health'       => 'Stato di Copertura della Sicurezza',
    'security_health_desc'  => 'Percentuale di rotte e risorse protette da permessi, solo Super Admin o pubbliche',
    'stat_users'            => 'Utenti',
    'stat_roles'            => 'Ruoli',
    'stat_permissions'      => 'Permessi',
    'stat_routes'           => 'Rotte HTTP',
    'stat_custom_resources' => 'Risorse Custom',
    'stat_protected'        => 'Protette da ACL',
    'stat_with_perms'       => 'Protette con Permessi',
    'stat_public'           => 'Pubbliche',
    'stat_unlinked'         => 'Senza Permessi Assegnati',
    'stat_deprecated'       => 'Deprecate',
    'recent_resources'      => 'Risorse Modificate di Recente',
    'view_all'              => 'Vedi Tutte →',
    'no_resources'          => 'Nessuna risorsa trovata. Esegui php artisan acl:sync o clicca "Sincronizza Rotte" per scansionare le tue rotte.',
    'sync_output'           => 'Output Sincronizzazione',
];
