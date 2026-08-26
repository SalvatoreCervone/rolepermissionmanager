<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Localization / Language
    |--------------------------------------------------------------------------
    |
    | Default language for the ACL Admin Panel and package messages.
    | Supported out of the box: 'en' (English - default), 'it' (Italian).
    | Set to null to inherit the application's locale from config('app.locale').
    |
    */
    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customizable table names for all ACL-related database tables.
    | Change these if you have naming conflicts with existing tables.
    |
    */
    'tables' => [
        'roles' => 'acl_roles',
        'permissions' => 'acl_permissions',
        'secured_resources' => 'acl_secured_resources',
        'model_has_roles' => 'acl_model_has_roles',
        'model_has_permissions' => 'acl_model_has_permissions',
        'role_has_permissions' => 'acl_role_has_permissions',
        'permission_has_resources' => 'acl_permission_has_resources',
        'scanner_rules' => 'acl_scanner_rules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Override the default Eloquent models used by the package.
    | Useful if you need to extend the base models with custom logic.
    |
    */
    'models' => [
        'user' => 'App\\Models\\User',
        'role' => SalvatoreCervone\RolePermissionManager\Models\Role::class,
        'permission' => SalvatoreCervone\RolePermissionManager\Models\Permission::class,
        'secured_resource' => SalvatoreCervone\RolePermissionManager\Models\SecuredResource::class,
        'scanner_rule' => SalvatoreCervone\RolePermissionManager\Models\ScannerRule::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticatable User Models (Polymorphic Multi-Model Support)
    |--------------------------------------------------------------------------
    |
    | Define one or more authenticatable models to manage in the admin panel.
    | When multiple models are configured, the admin panel automatically
    | renders model tabs allowing you to assign roles and permissions to each.
    |
    | Example multi-model setup:
    |
    | 'user_models' => [
    |     'users' => [
    |         'label'             => 'Users',
    |         'model'             => App\Models\User::class,
    |         'searchable_fields' => ['name', 'email'],
    |         'display_field'     => ['name', 'cognome'],
    |         'secondary_field'   => 'email',
    |     ],
    |     'admins' => [
    |         'label'             => 'Administrators',
    |         'model'             => App\Models\Admin::class,
    |         'searchable_fields' => ['nome', 'cognome', 'email'],
    |         'display_field'     => ['nome', 'cognome'],
    |         'secondary_field'   => 'email',
    |     ],
    | ],
    |
    | If left empty, the package uses the default single-model 'users' settings below.
    |
    */
    'user_models' => [],

    /*
    |--------------------------------------------------------------------------
    | User Model & Autocomplete Search Configuration (Default / Fallback)
    |--------------------------------------------------------------------------
    |
    | Default configuration for managing users when 'user_models' is empty.
    |
    | - table: The database table name for users (default: 'users').
    | - searchable_fields: Array of column names to search when using autocomplete/filters.
    | - display_field: Primary column(s) to display (string 'name' or array ['name', 'cognome']).
    | - secondary_field: Sub-label column(s) to display (string 'email' or array ['matricola', 'email']).
    | - per_page: Pagination count for the users list in the admin panel.
    |
    */
    'users' => [
        'table' => 'users',
        'searchable_fields' => ['name', 'email'],
        'display_field' => 'name',
        'secondary_field' => 'email',
        'per_page' => 25,
        'deactivation_marker' => 'Deactivated', // Plain string substituted into password & remember_token upon deactivation
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Configuration for the Super Admin role and bypass behavior.
    |
    | - 'role': The role slug that identifies Super Admin users (default: 'super-admin').
    |           Set to null to disable Super Admin handling.
    | - 'all_access': If true (or 'all'), users with the Super Admin role bypass all
    |                 permission checks automatically (everything is open).
    |                 If false, null, or key is omitted, Super Admin can ONLY access
    |                 routes/resources that are explicitly assigned to their roles/permissions,
    |                 or routes marked exclusively as 'super_admin_only'.
    |
    */
    'super_admin' => [
        'role' => 'super-admin',
        'all_access' => true,
    ],
    'super_admin_role' => 'super-admin', // Backwards compatibility fallback

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache configuration for the ACL registry. Caching dramatically
    | improves performance by avoiding database queries on every request.
    |
    | - store: The cache store to use (null = default store).
    | - ttl: Time-to-live in seconds (0 = forever until manually flushed).
    | - prefix: Cache key prefix to avoid collisions.
    |
    */
    'cache' => [
        'store' => null,
        'ttl' => 86400, // 24 hours
        'prefix' => 'acl_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Scanner
    |--------------------------------------------------------------------------
    |
    | Configuration for the automatic route discovery system.
    |
    | - route_files: Optional list of additional route files to ensure are loaded
    |   during scanning (e.g. ['routes/maschere.php', 'routes/custom.php']).
    | - included_prefixes: If not empty, ONLY scan routes matching these URI prefixes.
    | - included_names: If not empty, ONLY scan routes matching these name patterns.
    | - excluded_prefixes: Route URI prefixes to ignore during scanning.
    | - excluded_names: Specific route names to ignore during scanning.
    | - default_is_public: Whether newly discovered routes should default
    |   to public (false = "Secure by Default", recommended).
    | - default_operator: Default logical operator for new resources ('OR' or 'AND').
    | - auto_create_permissions: If true, the scanner will auto-create a
    |   permission with the same slug as the route name for each new route.
    |
    */
    'scanner' => [
        'route_files' => [
            // 'routes/maschere.php',
        ],
        'included_prefixes' => [
            // 'api',
            // 'valutazioni',
            // 'decreti',
        ],
        'included_names' => [
            // 'api.*',
            // 'valutazioni.*',
        ],
        'excluded_prefixes' => ['_ignition', '_debugbar', 'sanctum', 'telescope', 'horizon', 'livewire', 'workbench', 'storage', 'up'],
        'excluded_names' => ['login', '*.login', 'logout', '*.logout', 'register', '*.register', 'password.*', 'verification.*', 'sanctum.*', 'ignition.*', 'livewire.*', 'workbench.*', 'storage.*'],
        'default_is_public' => false,
        'default_operator' => 'OR',
        'auto_create_permissions' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configuration for the DynamicAclGuard middleware.
    |
    | - register_globally: If true, the middleware is automatically
    |   appended to the 'web' and 'api' middleware groups.
    | - guard: The authentication guard to use for retrieving the user.
    | - unprotected_behavior: What to do when a route is NOT found
    |   in the secured_resources table:
    |   - 'allow': Let the request through (the route is not managed by ACL).
    |   - 'deny': Block the request (every route must be explicitly registered).
    |
    */
    'middleware' => [
        'register_globally' => true,
        'guard' => null, // null = default guard
        'unprotected_behavior' => 'allow',
        'unassigned_permissions_behavior' => 'allow', // 'allow' = any authenticated user can access; 'deny' = locked to SuperAdmin
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | Configure automatic route synchronization via Laravel's scheduler.
    |
    | - enabled: If true, the acl:sync command is registered in the scheduler.
    | - time: The time of day to run the sync (HH:MM format).
    | - options: Additional flags passed to the acl:sync command.
    |   - clean: Remove deprecated/orphaned routes from the DB.
    |   - auto_permissions: Auto-create permissions for new routes.
    |   - notify: Log details about new/removed routes.
    |
    */
    'scheduler' => [
        'enabled' => false,
        'time' => '06:00',
        'options' => [
            'clean' => false,
            'auto_permissions' => false,
            'notify' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Configuration for the package's JSON API endpoints.
    |
    | - enabled: If true, API routes are registered.
    | - prefix: The URL prefix for API endpoints (default: 'acl-api').
    | - middleware: The middleware stack applied to API endpoints.
    |
    */
    'api' => [
        'enabled' => true,
        'prefix' => 'acl-api',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in web administration panel.
    |
    | - enabled: If true, the admin panel routes and views are registered.
    | - prefix: URL prefix for the admin panel (e.g., '/acl-admin').
    | - middleware: Middleware applied to all admin panel routes.
    | - page_title: Title displayed in the admin panel header.
    | - per_page: Number of items per page in paginated lists.
    |
    */
    'admin_panel' => [
        'enabled' => true,
        'prefix' => 'acl-admin',
        'middleware' => ['web', 'auth'],
        'page_title' => 'ACL Manager',
        'per_page' => 25,
    ],
];
