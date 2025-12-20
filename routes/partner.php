<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Partner\PartnerVerificationController;

/*
|--------------------------------------------------------------------------
| Partner API Routes
|--------------------------------------------------------------------------
|
| Routes dédiées à l'API Partner pour la création de citoyens SAGAPASS
| par des services partenaires authentifiés (ex: KAYPA, etc.)
|
| Authentification requise : Token Bearer (OAuth Application)
| Middleware : partner.auth (AuthenticatePartner)
|
*/

Route::prefix('api/partner/v1')
    ->middleware(['api', 'partner.auth', 'throttle:60,1']) // API middleware + auth
    ->group(function () {

        /**
         * Créer un nouveau citoyen SAGAPASS
         *
         * POST /api/partner/v1/create-citizen
         *
         * Headers:
         *   Authorization: Bearer {partner_token}
         *   Content-Type: application/json
         *
         * Body: Voir documentation complète
         */
        Route::post('/create-citizen', [PartnerVerificationController::class, 'createCitizen'])
            ->name('partner.create-citizen');

        /**
         * Vérifier le statut de vérification d'un citoyen
         *
         * GET /api/partner/v1/check-verification?email={email}
         * GET /api/partner/v1/check-verification?reference={partner_reference}
         */
        Route::get('/check-verification', [PartnerVerificationController::class, 'checkVerification'])
            ->name('partner.check-verification');

        /**
         * Obtenir les détails d'un citoyen par référence partenaire
         *
         * GET /api/partner/v1/citizen/{reference}
         */
        Route::get('/citizen/{reference}', [PartnerVerificationController::class, 'getCitizenDetails'])
            ->name('partner.get-citizen');

        /**
         * Générer un token pour le widget (URL sécurisée)
         *
         * POST /api/partner/v1/widget/generate-token
         */
        Route::post('/widget/generate-token', [\App\Http\Controllers\Partner\PartnerWidgetController::class, 'generateWidgetToken'])
            ->name('partner.widget.generate-token');
    });

/*
|--------------------------------------------------------------------------
| Partner Widget Routes (Public - No Auth)
|--------------------------------------------------------------------------
|
| Routes pour le widget embeddable SAGAPASS
| Ces routes sont publiques et utilisent un système de token temporaire
|
*/

Route::prefix('partner/widget')
    ->middleware(['web', 'throttle:30,1']) // 30 requêtes par minute pour le widget
    ->group(function () {

        /**
         * Afficher le popup de capture (photo/vidéo)
         *
         * GET /partner/widget/verify?partner_id={id}&email={email}&first_name={name}&last_name={name}
         */
        Route::get('/verify', [\App\Http\Controllers\Partner\PartnerWidgetController::class, 'showWidget'])
            ->name('partner.widget.verify');

        /**
         * Soumettre la vérification (photo + vidéo)
         *
         * POST /partner/widget/submit
         */
        Route::post('/submit', [\App\Http\Controllers\Partner\PartnerWidgetController::class, 'submitWidget'])
            ->name('partner.widget.submit');
    });
