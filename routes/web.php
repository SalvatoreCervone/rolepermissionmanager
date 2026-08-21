<?php

use Illuminate\Support\Facades\Route;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\AuditLogController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\DashboardController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\ExportImportController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\MatrixController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\PermissionController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\RoleController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\RouteResourceController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\ScannerRuleController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\SecuredResourceController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\SimulatorController;
use SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController;

$prefix = config('rolepermissionmanager.admin_panel.prefix', 'acl-admin');
$middleware = config('rolepermissionmanager.admin_panel.middleware', ['web', 'auth']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('acl.')
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Users & ACL Assignment
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');

        // Roles Management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissions Management
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{id}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{id}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Role-Permission Interactive Matrix
        Route::get('/matrix', [MatrixController::class, 'index'])->name('matrix.index');
        Route::post('/matrix/toggle', [MatrixController::class, 'toggle'])->name('matrix.toggle');

        // HTTP Routes Management (Scanned)
        Route::get('/routes', [RouteResourceController::class, 'index'])->name('routes.index');
        Route::post('/routes/bulk-update', [RouteResourceController::class, 'bulkUpdate'])->name('routes.bulk_update');
        Route::get('/routes/{id}/edit', [RouteResourceController::class, 'edit'])->name('routes.edit');
        Route::put('/routes/{id}', [RouteResourceController::class, 'update'])->name('routes.update');
        Route::post('/routes/sync', [RouteResourceController::class, 'sync'])->name('routes.sync');

        // Custom Resources Management (Classes, Methods, UI Elements)
        Route::get('/resources', [SecuredResourceController::class, 'index'])->name('resources.index');
        Route::post('/resources/bulk-update', [SecuredResourceController::class, 'bulkUpdate'])->name('resources.bulk_update');
        Route::get('/resources/create', [SecuredResourceController::class, 'create'])->name('resources.create');
        Route::post('/resources', [SecuredResourceController::class, 'store'])->name('resources.store');
        Route::get('/resources/{id}/edit', [SecuredResourceController::class, 'edit'])->name('resources.edit');
        Route::put('/resources/{id}', [SecuredResourceController::class, 'update'])->name('resources.update');
        Route::delete('/resources/{id}', [SecuredResourceController::class, 'destroy'])->name('resources.destroy');
        Route::post('/resources/sync', [SecuredResourceController::class, 'sync'])->name('resources.sync');

        // Access Simulator / Diagnostic Tester
        Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');

        // Scanner Rules (Exclusions & Inclusions)
        Route::get('/scanner-rules', [ScannerRuleController::class, 'index'])->name('scanner_rules.index');
        Route::post('/scanner-rules', [ScannerRuleController::class, 'store'])->name('scanner_rules.store');
        Route::patch('/scanner-rules/{id}/toggle', [ScannerRuleController::class, 'toggle'])->name('scanner_rules.toggle');
        Route::delete('/scanner-rules/{id}', [ScannerRuleController::class, 'destroy'])->name('scanner_rules.destroy');

        // Export & Import Configuration
        Route::get('/export-import', [ExportImportController::class, 'index'])->name('export_import.index');
        Route::get('/export-import/export', [ExportImportController::class, 'export'])->name('export_import.export');
        Route::post('/export-import/import', [ExportImportController::class, 'import'])->name('export_import.import');

        // Audit Logs (History / Changes)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
        Route::post('/audit-logs/clear', [AuditLogController::class, 'clear'])->name('audit_logs.clear');
    });
