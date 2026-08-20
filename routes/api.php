<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController;
use SalvatoreCervone\RolePermissionManager\Models\Permission;
use SalvatoreCervone\RolePermissionManager\Models\Role;
use SalvatoreCervone\RolePermissionManager\Models\SecuredResource;

$apiPrefix = config('rolepermissionmanager.api.prefix', 'acl-api');
$apiMiddleware = config('rolepermissionmanager.api.middleware', ['api']);

Route::prefix($apiPrefix)
    ->middleware($apiMiddleware)
    ->name('acl.api.')
    ->group(function () {

        // Users Autocomplete Search
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');

        // Roles JSON API
        Route::get('/roles', function () {
            return response()->json(Role::with('permissions')->get());
        })->name('roles');

        // Permissions JSON API
        Route::get('/permissions', function () {
            return response()->json(Permission::all());
        })->name('permissions');

        // Secured Resources JSON API
        Route::get('/resources', function () {
            return response()->json(SecuredResource::with('permissions')->get());
        })->name('resources');

        // Programmatic Route Sync API Trigger
        Route::post('/sync', function (Request $request) {
            $clean = $request->boolean('clean', false);
            $autoPermissions = $request->boolean('auto_permissions', false);

            Artisan::call('acl:sync', [
                '--clean'            => $clean,
                '--auto-permissions' => $autoPermissions,
                '--notify'           => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ACL Route synchronization completed.',
                'output'  => Artisan::output(),
            ]);
        })->name('sync');
    });
