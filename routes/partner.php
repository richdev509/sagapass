<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner API Routes (Deprecated)
|--------------------------------------------------------------------------
|
| L'ancien flux partenaire (machine-to-machine) a été remplacé par
| le flux OAuth app-to-app via SAGA ID mobile.
|
| Les endpoints OAuth sont maintenant dans routes/api.php :
|   - GET  /api/oauth/app-info          (infos app pour consent screen)
|   - POST /api/oauth/mobile-authorize  (consentement mobile)
|   - GET  /api/oauth/userinfo          (données utilisateur via token)
|
| Et dans routes/web.php :
|   - POST /oauth/token                 (échange code → token)
|   - POST /oauth/revoke                (révocation token)
|   - POST /oauth/introspect            (introspection token)
|
*/
