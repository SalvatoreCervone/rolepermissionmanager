<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigazione
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'overview'     => 'Panoramica',
        'dashboard'    => 'Dashboard',
        'manage'       => 'Gestione',
        'users'        => 'Utenti & Accessi',
        'roles'        => 'Ruoli',
        'permissions'  => 'Permessi',
        'resources'    => 'Rotte / Risorse',
        'actions'      => 'Azioni',
        'sync_routes'  => 'Sincronizza Rotte',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'            => 'Dashboard',
        'subtitle'         => 'Panoramica del tuo sistema ACL',
        'stat_users'       => 'Utenti',
        'stat_roles'       => 'Ruoli',
        'stat_permissions' => 'Permessi',
        'stat_protected'   => 'Rotte Protette',
        'stat_public'      => 'Rotte Pubbliche',
        'stat_unlinked'    => 'Non Collegate (Senza Permessi)',
        'stat_deprecated'  => 'Rotte Deprecate',
        'recent_resources' => 'Risorse Modificate di Recente',
        'view_all'         => 'Vedi Tutte →',
        'no_resources'     => 'Nessuna risorsa trovata. Esegui php artisan acl:sync o clicca "Sincronizza Rotte" per scansionare le tue rotte.',
        'sync_output'      => 'Output Sincronizzazione',
    ],

    /*
    |--------------------------------------------------------------------------
    | Utenti
    |--------------------------------------------------------------------------
    */
    'users' => [
        'title'                   => 'Utenti & Controllo Accessi',
        'subtitle'                => 'Gestisci ruoli e permessi diretti per gli utenti dell\'applicazione',
        'search_placeholder'      => 'Cerca utente (:fields)...',
        'all_roles'               => 'Tutti i Ruoli',
        'assigned_roles'          => 'Ruoli Assegnati',
        'direct_permissions'      => 'Permessi Diretti',
        'direct_permissions_help' => 'Assegna permessi individuali direttamente a questo utente (in aggiunta a quelli ereditati dai suoi ruoli).',
        'user_info'               => 'Informazioni Utente',
        'user_id'                 => 'ID Utente',
        'super_admin_status'      => 'Stato Super Admin',
        'super_admin_yes'         => '👑 Sì (Bypassa tutti i controlli)',
        'standard_user'           => 'Utente Standard',
        'no_roles'                => 'Nessun ruolo',
        'no_users_found'          => 'Nessun utente trovato corrispondente ai criteri di ricerca.',
        'manage_access'           => 'Gestisci Accesso',
        'save_access'             => 'Salva Impostazioni Accesso',
        'via_role'                => 'via ruolo',
        'updated_success'         => "Ruoli e permessi aggiornati per ':name'.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Ruoli
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'title'              => 'Ruoli',
        'subtitle'           => 'Gestisci i ruoli utente e i permessi associati',
        'new_role'           => '+ Nuovo Ruolo',
        'create_title'       => 'Crea Ruolo',
        'edit_title'         => 'Modifica Ruolo: :name',
        'role_details'       => 'Dettagli Ruolo',
        'assign_permissions' => 'Assegna Permessi',
        'name'               => 'Nome',
        'name_placeholder'   => 'es. Amministratore',
        'slug'               => 'Slug',
        'slug_placeholder'   => 'es. admin',
        'description'        => 'Descrizione',
        'desc_placeholder'   => 'Breve descrizione di questo ruolo...',
        'permissions_count'  => ':count permessi',
        'no_roles_found'     => 'Nessun ruolo trovato. Crea il tuo primo ruolo per iniziare.',
        'no_permissions_yet' => 'Nessun permesso disponibile. Creane prima uno.',
        'delete_confirm'     => "Sei sicuro di voler eliminare il ruolo ':name'?",
        'created_success'    => "Ruolo ':name' creato con successo.",
        'updated_success'    => "Ruolo ':name' aggiornato con successo.",
        'deleted_success'    => "Ruolo ':name' eliminato con successo.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Permessi
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'title'             => 'Permessi',
        'subtitle'          => 'Gestisci permessi granulari per la tua applicazione',
        'new_permission'    => '+ Nuovo Permesso',
        'create_title'      => 'Crea Permesso',
        'edit_title'        => 'Modifica Permesso: :name',
        'details'           => 'Dettagli Permesso',
        'name'              => 'Nome',
        'name_placeholder'  => 'es. Elimina Utenti',
        'slug'              => 'Slug',
        'slug_placeholder'  => 'es. users.delete',
        'module'            => 'Modulo',
        'module_placeholder'=> 'es. Utenti',
        'all_modules'       => 'Tutti i Moduli',
        'uncategorized'     => 'Non categorizzato',
        'description'       => 'Descrizione',
        'desc_placeholder'  => 'Cosa consente di fare questo permesso?',
        'roles'             => 'Ruoli',
        'resources'         => 'Risorse',
        'linked_roles'      => 'Ruoli Collegati',
        'linked_resources'  => 'Risorse Collegate',
        'no_roles_linked'   => 'Questo permesso non è assegnato ad alcun ruolo.',
        'no_resources_linked'=> 'Questo permesso non è collegato ad alcuna rotta.',
        'no_perms_found'    => 'Nessun permesso trovato. Crea il tuo primo permesso o esegui acl:sync --auto-permissions.',
        'delete_confirm'    => "Sei sicuro di voler eliminare il permesso ':name'?",
        'created_success'   => "Permesso ':name' creato con successo.",
        'updated_success'   => "Permesso ':name' aggiornato con successo.",
        'deleted_success'   => "Permesso ':name' eliminato con successo.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Rotte / Risorse
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'title'               => 'Rotte / Risorse',
        'subtitle'            => 'Gestisci le rotte protette scoperte dall\'applicazione',
        'configure_title'     => 'Configura Risorsa',
        'route_info'          => 'Informazioni Rotta',
        'access_settings'     => 'Impostazioni di Accesso',
        'required_permissions'=> 'Permessi Richiesti',
        'identifier'          => 'Identificativo',
        'method'              => 'Metodo HTTP',
        'all_methods'         => 'Tutti i Metodi',
        'all_status'          => 'Tutti gli Stati',
        'uri'                 => 'URI',
        'controller_action'   => 'Azione Controller',
        'status'              => 'Stato',
        'operator'            => 'Operatore',
        'operator_label'      => 'Operatore Permessi',
        'operator_or'         => 'OR — All\'utente basta ALMENO UNO dei permessi',
        'operator_and'        => 'AND — L\'utente deve possedere TUTTI i permessi',
        'public'              => 'Pubblica',
        'protected'           => 'Protetta',
        'deprecated'          => 'Deprecata',
        'public_access'       => 'Accesso Pubblico',
        'public_help'         => 'Questa rotta è PUBBLICA (nessuna autenticazione richiesta)',
        'protected_help'      => 'Questa rotta è PROTETTA (richiede autenticazione + permessi)',
        'no_permissions'      => 'Nessuno',
        'configure'           => 'Configura',
        'no_resources_found'  => 'Nessuna risorsa trovata. Clicca "Sincronizza Rotte" per scoprire le rotte dell\'applicazione.',
        'updated_success'     => "Risorsa ':identifier' aggiornata con successo.",
        'sync_success'        => 'Sincronizzazione rotte completata con successo.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comune / Azioni
    |--------------------------------------------------------------------------
    */
    'common' => [
        'save'       => 'Salva Modifiche',
        'save_config'=> 'Salva Configurazione',
        'create'     => 'Crea',
        'cancel'     => 'Annulla',
        'delete'     => 'Elimina',
        'edit'       => 'Modifica',
        'filter'     => 'Filtra',
        'clear'      => 'Azzera',
        'search'     => 'Cerca',
        'selected'   => 'selezionati',
        'direct'     => 'diretti',
        'actions'    => 'Azioni',
        'updated'    => 'Aggiornato',
        'clear_filter'=> 'Azzera Filtro',
    ],

];
