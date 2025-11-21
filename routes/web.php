<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Developer\DeveloperController;
use App\Http\Controllers\Developer\DeveloperAuthController;
use App\Http\Controllers\OAuth\OAuthController;
use App\Http\Controllers\Auth\RegisterBasicController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

/*
|--------------------------------------------------------------------------
| Inscription Basic (3 étapes: infos → photo → vidéo)
|--------------------------------------------------------------------------
*/
Route::prefix('register/basic')->name('register.basic.')->group(function () {
    // Étape 1 : Informations de base
    Route::get('/step1', [RegisterBasicController::class, 'showStep1'])->name('step1');
    Route::post('/step1', [RegisterBasicController::class, 'postStep1'])->name('step1.submit');

    // Étape 2 : Photo de profil (webcam)
    Route::get('/step2', [RegisterBasicController::class, 'showStep2'])->name('step2');
    Route::post('/step2', [RegisterBasicController::class, 'postStep2'])->name('step2.submit');

    // Étape 3 : Vidéo de vérification
    Route::get('/step3', [RegisterBasicController::class, 'showStep3'])->name('step3');
    Route::post('/step3', [RegisterBasicController::class, 'postStep3'])->name('step3.submit');

    // Page de confirmation
    Route::get('/complete', [RegisterBasicController::class, 'complete'])->name('complete')->middleware('auth:web');
});

// Routes de vérification d'email
Route::middleware(['auth:web'])->group(function () {
    // Page de notification de vérification
    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');

    // Traitement de la vérification
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('success', 'Votre email a été vérifié avec succès !');
    })->middleware(['signed'])->name('verification.verify');

    // Renvoyer l'email de vérification
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('resent', true);
    })->middleware(['throttle:6,1'])->name('verification.resend');
});

// Routes protégées pour les citoyens (guard: web)
Route::middleware(['auth:web'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');

    // Documents
    Route::resource('documents', DocumentController::class);

    // Route pour servir les images privées des documents
    Route::get('/documents/{id}/image/{type}', [DocumentController::class, 'serveImage'])
        ->name('documents.image')
        ->where('type', 'front|back');
});

// Redirection de /home vers /dashboard
Route::get('/home', function () {
    return redirect()->route('dashboard');
})->middleware('auth:web');

/*
|--------------------------------------------------------------------------
| Developer Dashboard Routes
|--------------------------------------------------------------------------
*/

// Routes publiques pour développeurs (inscription et connexion)
Route::prefix('developers')->name('developers.')->group(function () {
    // Inscription développeur (accessible sans authentification)
    Route::get('/register', [DeveloperAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [DeveloperAuthController::class, 'register'])->name('register.store');

    // Connexion développeur
    Route::get('/login', [DeveloperAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [DeveloperAuthController::class, 'login'])->name('login.store');

    // Déconnexion développeur
    Route::post('/logout', [DeveloperAuthController::class, 'logout'])->name('logout');
});

// Routes protégées pour développeurs (require is_developer = true)
Route::middleware(['auth:web', 'developer'])->prefix('developers')->name('developers.')->group(function () {
    // Dashboard développeur
    Route::get('/dashboard', [DeveloperController::class, 'dashboard'])->name('dashboard');

    // Gestion des applications OAuth
    Route::get('/applications', [DeveloperController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [DeveloperController::class, 'create'])->name('applications.create');
    Route::post('/applications', [DeveloperController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}', [DeveloperController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/edit', [DeveloperController::class, 'edit'])->name('applications.edit');
    Route::put('/applications/{application}', [DeveloperController::class, 'update'])->name('applications.update');
    Route::delete('/applications/{application}', [DeveloperController::class, 'destroy'])->name('applications.destroy');

    // Régénérer le client secret
    Route::post('/applications/{application}/regenerate-secret', [DeveloperController::class, 'regenerateSecret'])->name('applications.regenerate-secret');

    // Demande de scopes additionnels
    Route::post('/applications/{application}/request-scopes', [DeveloperController::class, 'requestScopes'])->name('applications.request-scopes');

    // Statistiques de l'application
    Route::get('/applications/{application}/stats', [DeveloperController::class, 'stats'])->name('applications.stats');

    // Documentation
    Route::get('/documentation', [DeveloperController::class, 'documentation'])->name('documentation');
});

/*
|--------------------------------------------------------------------------
| OAuth2 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('oauth')->name('oauth.')->group(function () {
    // OAuth Login (doit être AVANT les routes protégées)
    Route::get('/login', [OAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [OAuthController::class, 'processLogin'])->name('login.submit');

    // Authorization endpoint (écran de consentement) - Utilise le middleware OAuth personnalisé
    Route::get('/authorize', [OAuthController::class, 'showAuthorization'])->middleware(['oauth.auth', 'verified'])->name('authorize');
    Route::post('/authorize', [OAuthController::class, 'approveOrDeny'])->middleware(['oauth.auth', 'verified'])->name('authorize.decision');

    // Token endpoint (échange code contre access token)
    Route::post('/token', [OAuthController::class, 'issueToken'])->name('token');

    // Revoke token
    Route::post('/revoke', [OAuthController::class, 'revokeToken'])->name('revoke');

    // Introspect token (optionnel, pour vérifier validité)
    Route::post('/introspect', [OAuthController::class, 'introspect'])->name('introspect');
});

/*
|--------------------------------------------------------------------------
| User Connected Services (Mes Connexions)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web', 'verified'])->prefix('profile')->name('profile.')->group(function () {
    // Voir les services connectés
    Route::get('/connected-services', [ProfileController::class, 'connectedServices'])->name('connected-services');

    // Révoquer l'accès à un service
    Route::delete('/connected-services/{authorization}', [ProfileController::class, 'revokeService'])->name('revoke-service');

    // Historique des connexions
    Route::get('/connection-history', [ProfileController::class, 'connectionHistory'])->name('connection-history');
});
