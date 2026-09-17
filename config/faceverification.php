<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moteur d'extraction OCR + vivacité + correspondance faciale
    |--------------------------------------------------------------------------
    |
    | Script Python dédié (scripts/face-verification/), invoqué via la façade
    | Process de Laravel — même principe que PuppeteerNiuLookupClient/
    | TronSignatureVerifier côté SwapLajan (Process::path()->timeout()->run()),
    | pour rendre l'appel interceptable en test via Process::fake().
    |
    */

    'script_path' => base_path('scripts/face-verification'),

    // Interpréteur à invoquer — 'python3' par défaut (dev/tests), pointé vers
    // le binaire d'un virtualenv dédié en production (voir requirements.txt)
    // pour ne pas installer TensorFlow/PyTorch dans le Python système, partagé
    // avec d'autres projets sur le même serveur.
    'python_binary' => env('FACE_VERIFICATION_PYTHON_BIN', 'python3'),

    'timeout_seconds' => (int) env('FACE_VERIFICATION_TIMEOUT_SECONDS', 90),

    // DeepFace télécharge ses modèles au premier lancement — cache pointé hors
    // du home de l'utilisateur système pour rester lisible/inscriptible par
    // www-data, même principe que PUPPETEER_CACHE_DIR pour niu-lookup côté
    // SwapLajan.
    'model_cache_dir' => env('DEEPFACE_HOME', base_path('scripts/face-verification/.deepface-cache')),

    /*
    |--------------------------------------------------------------------------
    | Sessions de vérification partenaire (flux QR — voir
    | PartnerVerificationSession / Api\Partner\PartnerVerificationSessionController)
    |--------------------------------------------------------------------------
    */

    'session_ttl_minutes' => (int) env('FACE_VERIFICATION_SESSION_TTL_MINUTES', 15),

    // Durée de validité du KYC ID durable (PartnerVerifiedIdentity) une fois
    // une session complétée avec succès — renouvelée automatiquement à chaque
    // nouvelle session complétée pour le même document/partenaire.
    'kyc_validity_days' => (int) env('FACE_VERIFICATION_KYC_VALIDITY_DAYS', 90),

];
