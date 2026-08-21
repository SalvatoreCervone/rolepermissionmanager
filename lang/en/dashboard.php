<?php

return [
    'title'                 => 'Dashboard',
    'subtitle'              => 'Overview and health status of your ACL system',
    'security_health'       => 'Security Coverage Health',
    'security_health_desc'  => 'Percentage of routes and resources protected by permissions, super admin only, or explicitly public',
    'stat_users'            => 'Users',
    'stat_roles'            => 'Roles',
    'stat_permissions'      => 'Permissions',
    'stat_routes'           => 'HTTP Routes',
    'stat_custom_resources' => 'Custom Resources',
    'stat_protected'        => 'Protected',
    'stat_public'           => 'Public',
    'stat_unlinked'         => 'Without Assigned Permissions',
    'stat_deprecated'       => 'Deprecated',
    'recent_resources'      => 'Recently Modified Resources',
    'view_all'              => 'View All →',
    'no_resources'          => 'No resources found. Run php artisan acl:sync or click "Sync Routes" to scan your routes.',
    'sync_output'           => 'Sync Output',
];
