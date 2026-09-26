<?php

use App\Http\Controllers\Partner\DashboardController;
use App\Http\Controllers\Public\PartnerApplicationController;
use App\Http\Controllers\Public\PartnerAuthController;
use App\Http\Controllers\Public\PartnerDocsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Routes (entreprises partenaires)
|--------------------------------------------------------------------------
|
| Demande de partenariat (publique) + tableau de bord (guard 'partner',
| distinct du guard 'admin' et du guard 'web' citoyen - voir
| config/auth.php et App\Models\PartnerAccount).
|
*/

Route::prefix('partenaire')->name('partner.')->group(function () {
    // Demande de partenariat + connexion : publiques, aucune authentification
    // requise pour les atteindre.
    Route::get('/demande', [PartnerApplicationController::class, 'create'])->name('apply');
    Route::post('/demande', [PartnerApplicationController::class, 'store'])->name('apply.submit');

    Route::get('/connexion', [PartnerAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/connexion', [PartnerAuthController::class, 'login'])->name('login.submit');
    Route::post('/deconnexion', [PartnerAuthController::class, 'logout'])->name('logout');

    // Verification en 2 etapes (code envoye par email) - throttle serre sur
    // la soumission du code, seul point qu'un brute force viserait.
    Route::get('/otp', [PartnerAuthController::class, 'showOtpForm'])->name('otp.verify');
    Route::middleware('throttle:8,1')->group(function () {
        Route::post('/otp', [PartnerAuthController::class, 'verifyOtp'])->name('otp.verify.submit');
        Route::post('/otp/renvoyer', [PartnerAuthController::class, 'resendOtp'])->name('otp.resend');
    });

    // Documentation : publique (pas de contenu sensible), utile avant même
    // qu'une demande soit approuvée.
    Route::prefix('documentation')->name('docs.')->group(function () {
        Route::get('/', [PartnerDocsController::class, 'index'])->name('index');
        Route::get('/{slug}', [PartnerDocsController::class, 'show'])->name('show');
    });

    Route::middleware('auth:partner')->group(function () {
        Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');
        Route::patch('/tableau-de-bord', [DashboardController::class, 'updateProfile'])->name('dashboard.update');
        Route::post('/tableau-de-bord/regenerer-client-secret', [DashboardController::class, 'regenerateClientSecret'])->name('dashboard.regenerate-client-secret');
        Route::post('/tableau-de-bord/regenerer-webhook-secret', [DashboardController::class, 'regenerateWebhookSecret'])->name('dashboard.regenerate-webhook-secret');
    });
});
