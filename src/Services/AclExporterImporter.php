<?php

namespace SalvatoreCervone\RolePermissionManager\Services;

use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\ScannerRule;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;

class AclExporterImporter
{
    /**
     * Export the full ACL configuration as an associative array.
     */
    public static function exportData(): array
    {
        // 1. Roles & their permissions
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'name'        => $role->name,
                'slug'        => $role->slug,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('slug')->all(),
            ];
        })->all();

        // 2. Permissions
        $permissions = Permission::all()->map(function ($perm) {
            return [
                'name'        => $perm->name,
                'slug'        => $perm->slug,
                'module'      => $perm->module,
                'description' => $perm->description,
            ];
        })->all();

        // 3. Secured Resources (Routes & Custom)
        $resources = SecuredResource::with('permissions')->get()->map(function ($res) {
            return [
                'identifier'          => $res->identifier,
                'type'                => $res->type,
                'method'              => $res->method,
                'uri'                 => $res->uri,
                'controller_action'   => $res->controller_action,
                'source_file'         => $res->source_file,
                'description'         => $res->description,
                'is_public'           => (bool) $res->is_public,
                'is_super_admin_only' => (bool) $res->is_super_admin_only,
                'operator'            => $res->operator,
                'permissions'         => $res->permissions->pluck('slug')->all(),
            ];
        })->all();

        // 4. Scanner Rules
        $rules = ScannerRule::all()->map(function ($rule) {
            return [
                'type'        => $rule->type,
                'target'      => $rule->target,
                'pattern'     => $rule->pattern,
                'description' => $rule->description,
                'is_active'   => (bool) $rule->is_active,
            ];
        })->all();

        return [
            'version'     => '1.1',
            'exported_at' => now()->toIso8601String(),
            'permissions' => $permissions,
            'roles'       => $roles,
            'resources'   => $resources,
            'rules'       => $rules,
        ];
    }

    /**
     * Import full ACL configuration from an array.
     */
    public static function importData(array $data, bool $overwrite = false): array
    {
        $stats = [
            'permissions' => 0,
            'roles'       => 0,
            'resources'   => 0,
            'rules'       => 0,
        ];

        // 1. Import Permissions
        $permMap = [];
        foreach ($data['permissions'] ?? [] as $pData) {
            $perm = Permission::updateOrCreate(
                ['slug' => $pData['slug']],
                [
                    'name'        => $pData['name'],
                    'module'      => $pData['module'] ?? null,
                    'description' => $pData['description'] ?? null,
                ]
            );
            $permMap[$perm->slug] = $perm->id;
            $stats['permissions']++;
        }

        // 2. Import Roles
        foreach ($data['roles'] ?? [] as $rData) {
            $role = Role::updateOrCreate(
                ['slug' => $rData['slug']],
                [
                    'name'        => $rData['name'],
                    'description' => $rData['description'] ?? null,
                ]
            );

            if (isset($rData['permissions'])) {
                $permIds = [];
                foreach ($rData['permissions'] as $pSlug) {
                    if (isset($permMap[$pSlug])) {
                        $permIds[] = $permMap[$pSlug];
                    }
                }
                if ($overwrite) {
                    $role->permissions()->sync($permIds);
                } else {
                    $role->permissions()->syncWithoutDetaching($permIds);
                }
            }
            $stats['roles']++;
        }

        // 3. Import Resources
        foreach ($data['resources'] ?? [] as $resData) {
            $res = SecuredResource::updateOrCreate(
                ['identifier' => $resData['identifier']],
                [
                    'type'                => $resData['type'] ?? SecuredResource::TYPE_ROUTE,
                    'method'              => $resData['method'] ?? null,
                    'uri'                 => $resData['uri'] ?? null,
                    'controller_action'   => $resData['controller_action'] ?? null,
                    'source_file'         => $resData['source_file'] ?? null,
                    'description'         => $resData['description'] ?? null,
                    'is_public'           => $resData['is_public'] ?? false,
                    'is_super_admin_only' => $resData['is_super_admin_only'] ?? false,
                    'operator'            => $resData['operator'] ?? 'OR',
                ]
            );

            if (isset($resData['permissions'])) {
                $permIds = [];
                foreach ($resData['permissions'] as $pSlug) {
                    if (isset($permMap[$pSlug])) {
                        $permIds[] = $permMap[$pSlug];
                    }
                }
                if ($overwrite) {
                    $res->permissions()->sync($permIds);
                } else {
                    $res->permissions()->syncWithoutDetaching($permIds);
                }
            }
            $stats['resources']++;
        }

        // 4. Import Rules
        foreach ($data['rules'] ?? [] as $ruleData) {
            ScannerRule::updateOrCreate(
                [
                    'type'    => $ruleData['type'],
                    'target'  => $ruleData['target'],
                    'pattern' => $ruleData['pattern'],
                ],
                [
                    'description' => $ruleData['description'] ?? null,
                    'is_active'   => $ruleData['is_active'] ?? true,
                ]
            );
            $stats['rules']++;
        }

        AclRegistry::refreshCache();

        return $stats;
    }
}
