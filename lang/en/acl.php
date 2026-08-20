<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'overview'     => 'Overview',
        'dashboard'    => 'Dashboard',
        'manage'       => 'Manage',
        'users'        => 'Users & Access',
        'roles'        => 'Roles',
        'permissions'  => 'Permissions',
        'resources'    => 'Routes / Resources',
        'actions'      => 'Actions',
        'sync_routes'  => 'Sync Routes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'            => 'Dashboard',
        'subtitle'         => 'Overview of your ACL system',
        'stat_users'       => 'Users',
        'stat_roles'       => 'Roles',
        'stat_permissions' => 'Permissions',
        'stat_protected'   => 'Protected Routes',
        'stat_public'      => 'Public Routes',
        'stat_unlinked'    => 'Unlinked (No Permissions)',
        'stat_deprecated'  => 'Deprecated Routes',
        'recent_resources' => 'Recently Updated Resources',
        'view_all'         => 'View All →',
        'no_resources'     => 'No resources found. Run php artisan acl:sync or click "Sync Routes" to discover your routes.',
        'sync_output'      => 'Sync Output',
    ],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    'users' => [
        'title'                   => 'Users & Access Control',
        'subtitle'                => 'Manage roles and direct permissions for application users',
        'search_placeholder'      => 'Search user (:fields)...',
        'all_roles'               => 'All Roles',
        'assigned_roles'          => 'Assigned Roles',
        'direct_permissions'      => 'Direct Permissions',
        'direct_permissions_help' => 'Grant individual permissions directly to this user (in addition to permissions inherited from their roles).',
        'user_info'               => 'User Information',
        'user_id'                 => 'User ID',
        'super_admin_status'      => 'Super Admin Status',
        'super_admin_yes'         => '👑 Yes (Bypasses all checks)',
        'standard_user'           => 'Standard User',
        'no_roles'                => 'No roles',
        'no_users_found'          => 'No users found matching the search criteria.',
        'manage_access'           => 'Manage Access',
        'save_access'             => 'Save Access Settings',
        'via_role'                => 'via role',
        'updated_success'         => "Roles and permissions updated for ':name'.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'title'              => 'Roles',
        'subtitle'           => 'Manage user roles and their associated permissions',
        'new_role'           => '+ New Role',
        'create_title'       => 'Create Role',
        'edit_title'         => 'Edit Role: :name',
        'role_details'       => 'Role Details',
        'assign_permissions' => 'Assign Permissions',
        'name'               => 'Name',
        'name_placeholder'   => 'e.g. Administrator',
        'slug'               => 'Slug',
        'slug_placeholder'   => 'e.g. admin',
        'description'        => 'Description',
        'desc_placeholder'   => 'Brief description of this role...',
        'permissions_count'  => ':count permissions',
        'no_roles_found'     => 'No roles found. Create your first role to get started.',
        'no_permissions_yet' => 'No permissions available. Create one first.',
        'delete_confirm'     => "Are you sure you want to delete the role ':name'?",
        'created_success'    => "Role ':name' created successfully.",
        'updated_success'    => "Role ':name' updated successfully.",
        'deleted_success'    => "Role ':name' deleted successfully.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'title'             => 'Permissions',
        'subtitle'          => 'Manage granular permissions for your application',
        'new_permission'    => '+ New Permission',
        'create_title'      => 'Create Permission',
        'edit_title'        => 'Edit Permission: :name',
        'details'           => 'Permission Details',
        'name'              => 'Name',
        'name_placeholder'  => 'e.g. Delete Users',
        'slug'              => 'Slug',
        'slug_placeholder'  => 'e.g. users.delete',
        'module'            => 'Module',
        'module_placeholder'=> 'e.g. Users',
        'all_modules'       => 'All Modules',
        'uncategorized'     => 'Uncategorized',
        'description'       => 'Description',
        'desc_placeholder'  => 'What does this permission allow?',
        'roles'             => 'Roles',
        'resources'         => 'Resources',
        'linked_roles'      => 'Linked Roles',
        'linked_resources'  => 'Linked Resources',
        'no_roles_linked'   => 'This permission is not assigned to any role.',
        'no_resources_linked'=> 'This permission is not linked to any route.',
        'no_perms_found'    => 'No permissions found. Create your first permission or run acl:sync --auto-permissions.',
        'delete_confirm'    => "Are you sure you want to delete the permission ':name'?",
        'created_success'   => "Permission ':name' created successfully.",
        'updated_success'   => "Permission ':name' updated successfully.",
        'deleted_success'   => "Permission ':name' deleted successfully.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes / Resources
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'title'               => 'Routes / Resources',
        'subtitle'            => 'Manage protected routes discovered from your application',
        'configure_title'     => 'Configure Resource',
        'route_info'          => 'Route Information',
        'access_settings'     => 'Access Settings',
        'required_permissions'=> 'Required Permissions',
        'identifier'          => 'Identifier',
        'method'              => 'HTTP Method',
        'all_methods'         => 'All Methods',
        'all_status'          => 'All Status',
        'uri'                 => 'URI',
        'controller_action'   => 'Controller Action',
        'status'              => 'Status',
        'operator'            => 'Operator',
        'operator_label'      => 'Permission Operator',
        'operator_or'         => 'OR — User needs at least ONE of the permissions',
        'operator_and'        => 'AND — User needs ALL of the permissions',
        'public'              => 'Public',
        'protected'           => 'Protected',
        'deprecated'          => 'Deprecated',
        'public_access'       => 'Public Access',
        'public_help'         => 'This route is PUBLIC (no authentication required)',
        'protected_help'      => 'This route is PROTECTED (requires authentication + permissions)',
        'no_permissions'      => 'None',
        'configure'           => 'Configure',
        'no_resources_found'  => 'No resources found. Click "Sync Routes" to discover your application routes.',
        'updated_success'     => "Resource ':identifier' updated successfully.",
        'sync_success'        => 'Route sync completed successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common / Actions
    |--------------------------------------------------------------------------
    */
    'common' => [
        'save'       => 'Save Changes',
        'save_config'=> 'Save Configuration',
        'create'     => 'Create',
        'cancel'     => 'Cancel',
        'delete'     => 'Delete',
        'edit'       => 'Edit',
        'filter'     => 'Filter',
        'clear'      => 'Clear',
        'search'     => 'Search',
        'selected'   => 'selected',
        'direct'     => 'direct',
        'actions'    => 'Actions',
        'updated'    => 'Updated',
        'clear_filter'=> 'Clear Filter',
    ],

];
