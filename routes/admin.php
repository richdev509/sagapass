<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\PartnerSessionReviewController;
use App\Http\Controllers\Admin\MobileVerificationController;
use App\Http\Controllers\Admin\CitizenController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SystemSettingsController;
use App\Http\Controllers\Admin\TwoFactorController;
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

    // Vérification 2FA (après login, avant dashboard)
    Route::get('/two-factor/verify', [TwoFactorController::class, 'showVerify'])->name('two-factor.verify');
    Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify.post');
});

// Routes protégées par le guard admin + vérification 2FA obligatoire
Route::middleware(['auth:admin', 'ensure.2fa'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard Admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ============================================
    // Vérification des inscriptions mobiles (SAGA ID App)
    // ============================================
    Route::prefix('mobile-verification')->name('mobile-verification.')->group(function () {
        Route::get('/', [MobileVerificationController::class, 'index'])
            ->middleware('permission:verify-documents,admin')
            ->name('index');

        Route::get('/approved', [MobileVerificationController::class, 'approved'])
            ->middleware('permission:verify-documents,admin')
            ->name('approved');

        Route::get('/rejected', [MobileVerificationController::class, 'rejected'])
            ->middleware('permission:verify-documents,admin')
            ->name('rejected');

        Route::get('/{mobileVerification}', [MobileVerificationController::class, 'show'])
            ->middleware('permission:verify-documents,admin')
            ->name('show');

        Route::get('/{mobileVerification}/image/{type}', [MobileVerificationController::class, 'serveImage'])
            ->middleware('permission:verify-documents,admin')
            ->name('image');

        Route::post('/{mobileVerification}/approve', [MobileVerificationController::class, 'approve'])
            ->middleware('permission:verify-documents,admin')
            ->name('approve');

        Route::post('/{mobileVerification}/reject', [MobileVerificationController::class, 'reject'])
            ->middleware('permission:verify-documents,admin')
            ->name('reject');
    });

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

    // Revue manuelle des sessions partenaire (QR) dont l'analyse automatisée
    // a échoué techniquement — voir AnalyzePartnerSessionJob et
    // PartnerSessionReviewController. Middleware déjà appliqué dans le
    // constructeur du contrôleur (auth:admin + permission:verify-documents).
    Route::prefix('partner-sessions')->name('partner-sessions.')->group(function () {
        Route::get('/', [PartnerSessionReviewController::class, 'index'])->name('index');
        Route::get('/{partnerSession}', [PartnerSessionReviewController::class, 'show'])->name('show');
        Route::get('/{partnerSession}/image/{type}', [PartnerSessionReviewController::class, 'serveImage'])->name('image');
        Route::post('/{partnerSession}/approve', [PartnerSessionReviewController::class, 'approve'])->name('approve');
        Route::post('/{partnerSession}/reject', [PartnerSessionReviewController::class, 'reject'])->name('reject');
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

    // Gestion des applications partenaires (ex-"Gestion OAuth" — recentré sur
    // la seule approbation/gestion des DeveloperApplication utilisées par
    // l'API partenaire, voir Api\Partner\PartnerVerifyController).
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'index'])->name('index');

        // Routes avec {application}
        Route::get('/{application}', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'show'])->name('show');
        Route::post('/{application}/approve', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'approve'])->name('approve');
        Route::post('/{application}/reject', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'reject'])->name('reject');
        Route::post('/{application}/suspend', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'suspend'])->name('suspend');
        Route::post('/{application}/reactivate', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'reactivate'])->name('reactivate');

        Route::post('/{application}/regenerate-secret', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'regenerateSecret'])->name('regenerate-secret');
        Route::get('/{application}/secret', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'showSecret'])->name('show-secret');
        Route::post('/{application}/regenerate-app-key', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'regenerateAppKey'])->name('regenerate-app-key');
        Route::get('/{application}/app-key', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'showAppKey'])->name('show-app-key');
        Route::post('/{application}/regenerate-webhook-secret', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'regenerateWebhookSecret'])->name('regenerate-webhook-secret');
        Route::get('/{application}/webhook-secret', [\App\Http\Controllers\Admin\OAuthManagementController::class, 'showWebhookSecret'])->name('show-webhook-secret');
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

    // Paramètres Système (Beta Launch Features - Super Admin uniquement)
    Route::prefix('settings')->name('settings.')->middleware('permission:manage-settings,admin')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::post('/', [SystemSettingsController::class, 'update'])->name('update');
    });

    // Authentification à deux facteurs (2FA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/', [TwoFactorController::class, 'index'])->name('index');
        Route::get('/enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable.post');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
    });
});

