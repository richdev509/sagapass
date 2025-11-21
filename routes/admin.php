<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\CitizenController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes pour les administrateurs avec middleware auth:admin
| et permissions Spatie pour le contrôle d'accès granulaire
|
*/

// Routes publiques admin (login)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Routes protégées par le guard admin
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard Admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Vérification des documents
    Route::prefix('verification')->name('verification.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])
            ->middleware('permission:verify-documents,admin')
            ->name('index');

        Route::get('/verified', [VerificationController::class, 'verified'])
            ->middleware('permission:verify-documents,admin')
            ->name('verified');

        Route::get('/rejected', [VerificationController::class, 'rejected'])
            ->middleware('permission:verify-documents,admin')
            ->name('rejected');

        Route::get('/{document}', [VerificationController::class, 'show'])
            ->middleware('permission:verify-documents,admin')
            ->name('show');

        Route::get('/{document}/image/{type}', [VerificationController::class, 'serveImage'])
            ->middleware('permission:verify-documents,admin')
            ->name('image');

        Route::post('/{document}/approve', [VerificationController::class, 'approve'])
            ->middleware('permission:verify-documents,admin')
            ->name('approve');

        Route::post('/{document}/reject', [VerificationController::class, 'reject'])
            ->middleware('permission:verify-documents,admin')
            ->name('reject');
    });

    // Gestion des citoyens
    Route::prefix('citizens')->name('citizens.')->middleware('permission:view-users,admin')->group(function () {
        Route::get('/', [CitizenController::class, 'index'])->name('index');

        Route::get('/search', [CitizenController::class, 'search'])
            ->middleware('permission:search-users,admin')
            ->name('search');

        Route::get('/export', [CitizenController::class, 'export'])
            ->middleware('permission:export-users,admin')
            ->name('export');

        Route::get('/{id}', [CitizenController::class, 'show'])
            ->middleware('permission:view-user-details,admin')
            ->name('show');

        Route::put('/{id}', [CitizenController::class, 'update'])
            ->middleware('permission:edit-users,admin')
            ->name('update');

        Route::post('/{id}/suspend', [CitizenController::class, 'suspend'])
            ->middleware('permission:suspend-users,admin')
            ->name('suspend');

        Route::post('/{id}/activate', [CitizenController::class, 'activate'])
            ->middleware('permission:activate-users,admin')
            ->name('activate');

        Route::post('/{id}/reset-password', [CitizenController::class, 'resetPassword'])
            ->middleware('permission:reset-user-password,admin')
            ->name('reset-password');
    });

    // Gestion des administrateurs (réservé au Super Admin)
    Route::prefix('admins')->name('admins.')->middleware('permission:manage-admins,admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/create', [AdminController::class, 'create'])->name('create');
        Route::post('/', [AdminController::class, 'store'])->name('store');

        // Routes spécifiques AVANT les routes avec {admin}
        Route::get('/{admin}/roles', [\App\Http\Controllers\Admin\RolePermissionController::class, 'manageAdminRoles'])->name('roles');
        Route::post('/{admin}/roles', [\App\Http\Controllers\Admin\RolePermissionController::class, 'assignRoles'])->name('assign-roles');

        Route::get('/{admin}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::patch('/{admin}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('destroy');
        Route::patch('/{admin}/toggle-status', [AdminController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{admin}/permissions', [AdminController::class, 'editPermissions'])->name('permissions');
        Route::patch('/{admin}/permissions', [AdminController::class, 'updatePermissions'])->name('permissions.update');
    });

    // Logs d'audit (réservé au Super Admin)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:view-audit-logs,admin')
        ->name('audit-logs');

    // Statistiques avancées (réservé au Super Admin)
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->middleware('role:Super Admin,admin')
        ->name('statistics');

    // Gestion OAuth
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'index'])->name('index');

        // Demandes de scopes (AVANT les routes avec {application})
        Route::get('/scope-requests', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'scopeRequests'])->name('scope-requests');
        Route::post('/scope-requests/{scopeRequest}/approve', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'approveScopeRequest'])->name('approve-scope-request');
        Route::post('/scope-requests/{scopeRequest}/reject', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'rejectScopeRequest'])->name('reject-scope-request');

        // Routes avec {application}
        Route::get('/{application}', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'show'])->name('show');
        Route::post('/{application}/approve', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'approve'])->name('approve');
        Route::post('/{application}/reject', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'reject'])->name('reject');
        Route::post('/{application}/suspend', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'suspend'])->name('suspend');
        Route::post('/{application}/reactivate', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'reactivate'])->name('reactivate');
        Route::get('/{application}/users', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'users'])->name('users');
        Route::post('/authorizations/{authorization}/revoke', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'revokeUserAuthorization'])->name('revoke-authorization');

        // Gestion des scopes
        Route::post('/{application}/scopes/add', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'addScope'])->name('add-scope');
        Route::delete('/{application}/scopes/{scope}', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'removeScope'])->name('remove-scope');
    });

    // Gestion des rôles et permissions
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RolePermissionController::class, 'roles'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\RolePermissionController::class, 'createRole'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\RolePermissionController::class, 'storeRole'])->name('store');
        Route::get('/{role}/edit', [\App\Http\Controllers\Admin\RolePermissionController::class, 'editRole'])->name('edit');
        Route::put('/{role}', [\App\Http\Controllers\Admin\RolePermissionController::class, 'updateRole'])->name('update');
        Route::delete('/{role}', [\App\Http\Controllers\Admin\RolePermissionController::class, 'deleteRole'])->name('delete');
    });

    Route::get('/permissions', [\App\Http\Controllers\Admin\RolePermissionController::class, 'permissions'])->name('permissions.index');

    // Routes de sécurité (Super Admin + Cyber Admin)
    Route::prefix('security')->name('security.')->middleware('permission:view-security-logs,admin')->group(function () {
        // Dashboard de sécurité
        Route::get('/', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('dashboard');

        // Gestion des logs
        Route::get('/logs', [\App\Http\Controllers\Admin\SecurityController::class, 'logsPage'])->name('logs');
        Route::get('/logs/{id}', [\App\Http\Controllers\Admin\SecurityController::class, 'showLog'])->name('logs.show');

        // IPs bloquées
        Route::get('/blocked-ips', [\App\Http\Controllers\Admin\SecurityController::class, 'blockedIpsPage'])
            ->middleware('permission:view-blocked-ips,admin')
            ->name('blocked-ips');

        // API endpoints pour AJAX
        Route::post('/api/logs', [\App\Http\Controllers\Admin\SecurityController::class, 'logs'])->name('api.logs');
        Route::get('/api/stats', [\App\Http\Controllers\Admin\SecurityController::class, 'stats'])->name('api.stats');

        // Actions (nécessitent permissions spécifiques)
        Route::post('/block-ip', [\App\Http\Controllers\Admin\SecurityController::class, 'blockIp'])
            ->middleware('permission:block-ips,admin')
            ->name('block-ip');
        Route::post('/unblock-ip', [\App\Http\Controllers\Admin\SecurityController::class, 'unblockIp'])
            ->middleware('permission:unblock-ips,admin')
            ->name('unblock-ip');
        Route::delete('/logs', [\App\Http\Controllers\Admin\SecurityController::class, 'deleteLogs'])
            ->middleware('permission:delete-security-logs,admin')
            ->name('logs.delete');
        Route::post('/clean-expired', [\App\Http\Controllers\Admin\SecurityController::class, 'cleanExpiredBlocks'])
            ->middleware('permission:manage-security,admin')
            ->name('clean-expired');
    });
});

