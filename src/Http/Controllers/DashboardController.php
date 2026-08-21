<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;

class DashboardController extends Controller
{
    public function index()
    {
        $userModelClass = \SalvatoreCervone\RolePermissionManager\Services\AclRegistry::getUserModelClass();

        $totalUsers = 0;
        if ($userModelClass && class_exists($userModelClass)) {
            try {
                $totalUsers = (new $userModelClass)->count();
            } catch (\Throwable $e) {
                $totalUsers = 0;
            }
        }

        $totalRoutes = SecuredResource::routes()->active()->count();
        $totalCustom = SecuredResource::custom()->active()->count();
        $totalActive = SecuredResource::active()->count();

        $publicCount = SecuredResource::active()->public()->count();
        $superAdminCount = SecuredResource::active()->superAdminOnly()->count();
        $protectedCount = SecuredResource::active()->protected()->count();

        // Protected with permissions assigned (excluding super admin only which is inherently secured):
        $withPermsCount = SecuredResource::active()->protected()->notSuperAdminOnly()->has('permissions')->count();

        // Protected with 0 permissions assigned (unassigned / needs configuration):
        $unlinkedCount = SecuredResource::active()->protected()->notSuperAdminOnly()->doesntHave('permissions')->count();

        // Properly secured = Public (by intention) + Super Admin only (by rule) + Protected with assigned permissions:
        $securedProperly = $publicCount + $superAdminCount + $withPermsCount;
        $coveragePct = $totalActive > 0 ? round(($securedProperly / $totalActive) * 100) : 100;

        $stats = [
            'total_users'            => $totalUsers,
            'total_roles'            => Role::count(),
            'total_permissions'      => Permission::count(),
            'total_routes'           => $totalRoutes,
            'total_custom_resources' => $totalCustom,
            'total_resources'        => $totalActive,
            'public_resources'       => $publicCount,
            'protected_resources'    => $protectedCount,
            'super_admin_resources'  => $superAdminCount,
            'with_perms_resources'   => $withPermsCount,
            'unlinked_resources'     => $unlinkedCount,
            'coverage_percentage'    => $coveragePct,
        ];

        $recentResources = SecuredResource::active()
            ->with('permissions')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('acl::dashboard', compact('stats', 'recentResources'));
    }
}
