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
        $userModelClass = config('rolepermissionmanager.models.user')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        $totalUsers = 0;
        if (class_exists($userModelClass)) {
            try {
                $totalUsers = (new $userModelClass)->count();
            } catch (\Throwable $e) {
                $totalUsers = 0;
            }
        }

        $stats = [
            'total_users'       => $totalUsers,
            'total_roles'       => Role::count(),
            'total_permissions' => Permission::count(),
            'total_resources'   => SecuredResource::where('is_deprecated', false)->count(),
            'public_resources'  => SecuredResource::where('is_public', true)->where('is_deprecated', false)->count(),
            'protected_resources' => SecuredResource::where('is_public', false)->where('is_deprecated', false)->count(),
            'deprecated_resources' => SecuredResource::where('is_deprecated', true)->count(),
            'unlinked_resources' => SecuredResource::where('is_deprecated', false)
                ->where('is_public', false)
                ->whereDoesntHave('permissions')
                ->count(),
        ];

        $recentResources = SecuredResource::where('is_deprecated', false)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('acl::dashboard', compact('stats', 'recentResources'));
    }
}
