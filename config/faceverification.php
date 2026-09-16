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

    'timeout_seconds' => (int) env('FACE_VERIFICATION_TIMEOUT_SECONDS', 90),

    // DeepFace télécharge ses modèles au premier lancement — cache pointé hors
    // du home de l'utilisateur système pour rester lisible/inscriptible par
    // www-data, même principe que PUPPETEER_CACHE_DIR pour niu-lookup côté
    // SwapLajan.
    'model_cache_dir' => env('DEEPFACE_HOME', base_path('scripts/face-verification/.deepface-cache')),

];
