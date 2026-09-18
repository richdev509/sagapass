<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Auth\RegisterBasicController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Public\FaceCaptureController;
use App\Http\Controllers\Public\FaceTestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Page publique de capture (pièce d'identité, étape 1, puis vivacité
// faciale, étape 2 — flux session partenaire QR) — aucune authentification,
// le jeton dans l'URL en tient lieu. Throttle plus strict (IP anonyme) qu'un
// endpoint applicatif normal.
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/capture/{token}', [FaceCaptureController::class, 'show'])->name('capture.show');
    Route::post('/capture/{token}/id', [FaceCaptureController::class, 'submitId'])->name('capture.submit-id');
    Route::post('/capture/{token}/selfie', [FaceCaptureController::class, 'submitSelfie'])->name('capture.submit-selfie');
});

// Page TEMPORAIRE de test du moteur facial, sans connexion (lien secret +
// désactivée par défaut) — voir Public\FaceTestController. À désactiver
// (FACE_TEST_ENABLED=false) une fois les tests terminés.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/face-test/{token}', [FaceTestController::class, 'show'])->name('face-test.show');
    Route::post('/face-test/{token}/compare', [FaceTestController::class, 'compare'])->name('face-test.compare');
});

// Known Errors Page (public)
Route::get('/erreurs-connues', function () {
    return view('known-errors');
})->name('known-errors');

/*
|--------------------------------------------------------------------------
| Pages Statiques
|--------------------------------------------------------------------------
*/
// Entreprise
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/careers', [PageController::class, 'careers'])->name('careers');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Ressources
Route::get('/documentation', [PageController::class, 'documentation'])->name('documentation');
Route::get('/api', [PageController::class, 'api'])->name('api');
Route::get('/support', [PageController::class, 'support'])->name('support');
Route::get('/status', [PageController::class, 'status'])->name('status');

// Légal
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/legal', [PageController::class, 'legal'])->name('legal');
Route::get('/cookies', [PageController::class, 'cookies'])->name('cookies');

Auth::routes();

/*
|--------------------------------------------------------------------------
| Inscription Basic (2 étapes: infos → photo — l'étape vidéo a été retirée,
| voir Admin\VerificationController et Services\FaceVerification\* pour la
| vérification automatisée document+selfie qui la remplace)
|--------------------------------------------------------------------------
*/
Route::prefix('register/basic')->name('register.basic.')->group(function () {
    // Étape 0 : Demande d'email et vérification
    Route::get('/email-request', [RegisterBasicController::class, 'showEmailRequest'])->name('email-request');
    Route::post('/send-code', [RegisterBasicController::class, 'sendVerificationCode'])->name('send-code');
    Route::get('/verify-code', [RegisterBasicController::class, 'showVerifyCode'])->name('verify-code');
    Route::post('/verify-code', [RegisterBasicController::class, 'verifyCode'])->name('verify-code.submit');

    // Étape 1 : Informations de base (protégée par email vérifié)
    Route::middleware('verify.email.session')->group(function () {
        Route::get('/step1', [RegisterBasicController::class, 'showStep1'])->name('step1');
        Route::post('/step1', [RegisterBasicController::class, 'postStep1'])->name('step1.submit');

        // Étape 2 : Photo de profil (webcam) — crée le compte à la soumission
        Route::get('/step2', [RegisterBasicController::class, 'showStep2'])->name('step2');
        Route::post('/step2', [RegisterBasicController::class, 'postStep2'])->name('step2.submit');
    });

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
        try {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('success', 'Email de vérification renvoyé avec succès !');
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email vérification: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.');
        }
    })->name('verification.resend');
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
| (Portail développeur self-service, OAuth "Login with SagaID" et
| "Services connectés" retirés — hors périmètre du cas d'usage
| vérification document+selfie pour partenaires. La gestion admin des
| DeveloperApplication pour l'API partenaire reste dans routes/admin.php.)
|--------------------------------------------------------------------------
*/
