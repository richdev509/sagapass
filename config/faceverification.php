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

    // Extraction OCR via l'API de vision Claude (Anthropic), en test face à
    // EasyOCR — voir extract_ocr_fields() dans analyze.py : essayée en
    // premier si la clé est configurée, repli automatique sur EasyOCR sinon
    // ou en cas d'échec de l'appel (jamais de dépendance dure à un service
    // tiers). Aucune clé = comportement identique à avant (EasyOCR seul).
    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    'anthropic_model' => env('ANTHROPIC_OCR_MODEL', 'claude-sonnet-5'),

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

    /*
    |--------------------------------------------------------------------------
    | Détection de doublons de visage (voir FaceDuplicateService)
    |--------------------------------------------------------------------------
    |
    | Similarité cosinus minimale entre deux empreintes SFace pour considérer
    | qu'il s'agit du même visage. 0.363 = seuil officiel du modèle SFace
    | (OpenCV Zoo), le même que le projet Security_system. Point de départ :
    | à recalibrer sur de vraies paires de visages (empreintes DeepFace, pas
    | OpenCV). Une fausse alerte coûte une revue manuelle ; un doublon manqué
    | coûte plus cher — d'où un seuil plutôt bas.
    */

    'duplicate_similarity_threshold' => (float) env('FACE_DUPLICATE_SIMILARITY_THRESHOLD', 0.363),

    // Version des conditions d'utilisation acceptées sur la page de capture —
    // enregistrée avec la session comme preuve de consentement. À changer quand
    // les conditions (/terms, /privacy) sont modifiées.
    'consent_terms_version' => env('FACE_VERIFICATION_TERMS_VERSION', '2026-09'),

    // Page TEMPORAIRE de test du moteur facial, sans connexion (voir
    // Public\FaceTestController). Désactivée par défaut ; accessible seulement
    // par /face-test/<token>.
    'face_test' => [
        'enabled' => (bool) env('FACE_TEST_ENABLED', false),
        'token' => env('FACE_TEST_TOKEN'),
    ],

];
