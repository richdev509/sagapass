# 🚀 Guide de Migration SagaPass - Laravel

**Version API:** 2.0  
**Date de déploiement:** 31 Mars 2026  
**Date limite de migration:** 15 Avril 2026

---

## 📋 Table des Matières

1. [Résumé des Changements](#résumé-des-changements)
2. [Système de Challenge à Deux Types](#système-de-challenge-à-deux-types)
3. [Webhooks en Temps Réel](#webhooks-en-temps-réel)
4. [Migration Laravel - Étape par Étape](#migration-laravel---étape-par-étape)
5. [Code Laravel de Traitement Webhook](#code-laravel-de-traitement-webhook)
6. [Tests et Validation](#tests-et-validation)
7. [Checklist de Déploiement](#checklist-de-déploiement)

---

## 🔄 Résumé des Changements

### ✅ Ce qui a changé

| Aspect | Avant | Maintenant |
|--------|-------|------------|
| **Types de challenges** | Un seul type | 2 types: `confirmation` et `data_mismatch` |
| **Réponse HTTP 202** | Attendait confirmation | Toujours requis (type `confirmation`) |
| **Réponse HTTP 409** | N'existait pas | Nouveau: variation de nom détectée |
| **Notifications** | Polling uniquement | **Webhooks en temps réel** (optionnel) |
| **Expiration** | 48h (manuel) | 48h avec **webhook automatique** |
| **Sécurité** | Pas de signature | **HMAC SHA-256** obligatoire |

### 🎯 Pourquoi ces changements?

- ✅ **Sécurité renforcée**: Confirmation obligatoire même pour comptes vérifiés
- ⚡ **Performance**: Webhooks évitent le polling constant
- 🎨 **UX améliorée**: Distinction claire entre erreur de saisie vs confirmation
- 🔒 **Protection des données**: Jamais d'exposition des données SagaPass au partenaire

---

## 🔀 Système de Challenge à Deux Types

### Type 1: `confirmation` (HTTP 202)

**Quand?** Compte vérifié SagaPass + données **exactes**

**Workflow:**
```
1. Partenaire → API SagaPass (niu, nom, email...)
2. ← HTTP 202 + challenge_id
3. Utilisateur reçoit notification SagaPass mobile
4. Utilisateur confirme/rejette dans l'app
5. → Webhook envoyé au partenaire (si configuré)
```

**Réponse API:**
```json
{
    "success": false,
    "message": "Vérification en attente de confirmation de l'utilisateur",
    "challenge_id": "9abc1234-5678-90ef-1234-567890abcdef",
    "challenge_type": "confirmation",
    "expires_at": "2026-04-02T20:00:00Z",
    "http_code": 202
}
```

**Actions requises:**
- ✅ Afficher message "En attente de confirmation..."
- ✅ Attendre webhook OU polling du statut
- ✅ Traiter `verification.confirmed` ou `verification.rejected`

---

### Type 2: `data_mismatch` (HTTP 409)

**Quand?** Compte vérifié SagaPass + **variation de nom** détectée (< 80% similarité)

**Workflow:**
```
1. Partenaire → API SagaPass (niu, nom, email...)
2. ← HTTP 409 immediately (pas d'attente)
3. Utilisateur corrige son nom chez le partenaire
4. Partenaire → Nouvelle requête avec nom corrigé
5. ← HTTP 202 (confirmation) ou HTTP 200 (vérifié)
```

**Réponse API:**
```json
{
    "success": false,
    "message": "Les informations fournies ne correspondent pas exactement",
    "reason": "name_mismatch",
    "challenge_id": "9abc5678-1234-90ef-5678-1234567890ab",
    "challenge_type": "data_mismatch",
    "corrections_needed": {
        "name": "Veuillez vérifier l'orthographe du nom"
    },
    "http_code": 409
}
```

**Actions requises:**
- ✅ Afficher formulaire de correction
- ✅ Demander à l'utilisateur de vérifier le nom
- ✅ Faire nouvelle requête avec nom corrigé
- ❌ **PAS de webhook** envoyé (correction immédiate)

---

## 🔔 Webhooks en Temps Réel

### Configuration

Ajoutez le paramètre `webhook_url` à votre requête de vérification:

```php
use Illuminate\Support\Facades\Http;

$response = Http::post('https://sagapass.com/api/partner/verify', [
    'encrypted' => true,
    'data' => $encryptedData,
    'webhook_url' => route('sagapass.webhook'), // NOUVEAU
]);
```

**Route Laravel requise:**
```php
// routes/web.php ou routes/api.php
Route::post('/sagapass/webhook', [SagaPassController::class, 'handleWebhook'])
    ->name('sagapass.webhook')
    ->withoutMiddleware('csrf'); // Important pour les webhooks externes
```

### Événements Webhook

| Événement | Quand? | Challenge Type | Payload Include User Data? |
|-----------|--------|----------------|---------------------------|
| `verification.confirmed` | Utilisateur confirme | `confirmation` | ✅ Oui |
| `verification.rejected` | Utilisateur rejette | `confirmation` | ❌ Non |
| `verification.expired` | 48h sans réponse | `confirmation` | ❌ Non |

**Note:** Aucun webhook pour `data_mismatch` (HTTP 409)

---

## 🛠️ Migration Laravel - Étape par Étape

### Étape 1: Migration de Base de Données

Ajoutez une colonne `challenge_type` pour distinguer les types:

```php
// database/migrations/2026_03_31_000001_add_sagapass_challenge_type.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sagapass_verifications', function (Blueprint $table) {
            $table->enum('challenge_type', ['confirmation', 'data_mismatch'])
                ->nullable()
                ->after('challenge_id')
                ->comment('Type de challenge: confirmation (HTTP 202) ou data_mismatch (HTTP 409)');
        });
    }

    public function down(): void
    {
        Schema::table('sagapass_verifications', function (Blueprint $table) {
            $table->dropColumn('challenge_type');
        });
    }
};
```

### Étape 2: Mettre à Jour le Model

```php
// app/Models/SagaPassVerification.php

class SagaPassVerification extends Model
{
    protected $fillable = [
        'user_id',
        'challenge_id',
        'challenge_type', // NOUVEAU
        'status',
        'niu',
        'reference',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Helper methods
    public function isConfirmationType(): bool
    {
        return $this->challenge_type === 'confirmation';
    }

    public function isDataMismatchType(): bool
    {
        return $this->challenge_type === 'data_mismatch';
    }

    public function needsUserAction(): bool
    {
        return $this->isConfirmationType() && $this->status === 'pending';
    }
}
```

### Étape 3: Adapter le Service de Vérification

```php
// app/Services/SagaPassService.php

namespace App\Services;

use App\Models\SagaPassVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SagaPassService
{
    private string $apiUrl;
    private string $appKey;

    public function __construct()
    {
        $this->apiUrl = config('services.sagapass.api_url');
        $this->appKey = config('services.sagapass.app_key');
    }

    /**
     * Vérifier un utilisateur avec SagaPass
     * 
     * @return array ['status' => 'confirmed|pending|mismatch', 'data' => [...]]
     */
    public function verifyUser(array $userData): array
    {
        // 1. Chiffrer les données
        $encryptedData = $this->encryptData($userData);

        // 2. Appeler l'API SagaPass avec webhook
        $response = Http::timeout(30)->post($this->apiUrl . '/partner/verify', [
            'encrypted' => true,
            'data' => $encryptedData,
            'webhook_url' => route('sagapass.webhook'), // IMPORTANT
        ]);

        $data = $response->json();
        $httpCode = $response->status();

        // 3. Traiter selon le code HTTP
        return match ($httpCode) {
            200 => $this->handleVerified($data),           // Déjà vérifié
            202 => $this->handleConfirmation($data),       // Confirmation requise
            409 => $this->handleDataMismatch($data),       // Variation détectée
            403 => $this->handleRejected($data),           // Compte rejeté
            default => $this->handleError($data, $httpCode),
        };
    }

    private function handleVerified(array $data): array
    {
        Log::info('SagaPass: Utilisateur vérifié', [
            'user_id' => $data['user']['citizen_id'] ?? null,
        ]);

        return [
            'status' => 'confirmed',
            'user_id' => $data['user']['citizen_id'],
            'verified' => true,
        ];
    }

    private function handleConfirmation(array $data): array
    {
        // Sauvegarder le challenge pour tracking
        $verification = SagaPassVerification::create([
            'challenge_id' => $data['challenge_id'],
            'challenge_type' => 'confirmation', // IMPORTANT
            'status' => 'pending',
            'expires_at' => $data['expires_at'],
        ]);

        Log::info('SagaPass: Confirmation requise', [
            'challenge_id' => $data['challenge_id'],
            'expires_at' => $data['expires_at'],
        ]);

        return [
            'status' => 'pending',
            'challenge_id' => $data['challenge_id'],
            'message' => 'En attente de confirmation de l\'utilisateur',
            'expires_at' => $data['expires_at'],
        ];
    }

    private function handleDataMismatch(array $data): array
    {
        // Sauvegarder pour historique
        SagaPassVerification::create([
            'challenge_id' => $data['challenge_id'],
            'challenge_type' => 'data_mismatch', // IMPORTANT
            'status' => 'correction_needed',
        ]);

        Log::warning('SagaPass: Variation de nom détectée', [
            'challenge_id' => $data['challenge_id'],
            'reason' => $data['reason'] ?? 'name_mismatch',
        ]);

        return [
            'status' => 'mismatch',
            'challenge_id' => $data['challenge_id'],
            'message' => $data['message'],
            'corrections_needed' => $data['corrections_needed'] ?? [],
        ];
    }

    private function handleRejected(array $data): array
    {
        Log::warning('SagaPass: Compte rejeté', ['reason' => $data['reason'] ?? null]);

        return [
            'status' => 'rejected',
            'message' => $data['message'],
        ];
    }

    private function handleError(array $data, int $httpCode): array
    {
        Log::error('SagaPass: Erreur API', [
            'http_code' => $httpCode,
            'message' => $data['message'] ?? 'Unknown error',
        ]);

        throw new \Exception('Erreur SagaPass: ' . ($data['message'] ?? 'Unknown'));
    }

    private function encryptData(array $data): string
    {
        // Implémentation du chiffrement AES (voir documentation SagaPass)
        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        
        $encrypted = openssl_encrypt(
            json_encode($data),
            $cipher,
            $this->appKey,
            0,
            $iv
        );
        
        return base64_encode($iv) . ':' . $encrypted;
    }
}
```

### Étape 4: Controller de Vérification

```php
// app/Http/Controllers/UserVerificationController.php

namespace App\Http\Controllers;

use App\Services\SagaPassService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserVerificationController extends Controller
{
    public function __construct(
        private SagaPassService $sagaPass
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'niu' => 'required|string|size:10',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string',
        ]);

        try {
            $result = $this->sagaPass->verifyUser($validated);

            return match ($result['status']) {
                'confirmed' => response()->json([
                    'success' => true,
                    'message' => 'Utilisateur vérifié avec succès',
                    'user_id' => $result['user_id'],
                ]),

                'pending' => response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'En attente de confirmation de l\'utilisateur',
                    'challenge_id' => $result['challenge_id'],
                    'expires_at' => $result['expires_at'],
                    'action_required' => 'L\'utilisateur doit confirmer dans l\'app SagaPass',
                ], 202),

                'mismatch' => response()->json([
                    'success' => false,
                    'status' => 'correction_needed',
                    'message' => 'Veuillez vérifier les informations saisies',
                    'corrections_needed' => $result['corrections_needed'],
                    'action_required' => 'Corrigez les informations et réessayez',
                ], 409),

                'rejected' => response()->json([
                    'success' => false,
                    'status' => 'rejected',
                    'message' => $result['message'],
                ], 403),

                default => response()->json([
                    'success' => false,
                    'message' => 'Erreur inconnue',
                ], 500),
            };
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

---

## 🔐 Code Laravel de Traitement Webhook

### Configuration

```php
// config/services.php

return [
    'sagapass' => [
        'api_url' => env('SAGAPASS_API_URL', 'https://sagapass.com/api'),
        'app_key' => env('SAGAPASS_APP_KEY'),
        'webhook_secret' => env('SAGAPASS_APP_KEY'), // Même clé pour HMAC
    ],
];
```

### Middleware de Vérification HMAC

```php
// app/Http/Middleware/VerifySagaPassSignature.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifySagaPassSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Saga-Signature');

        if (!$signature) {
            Log::warning('SagaPass Webhook: Signature manquante', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        // Vérifier la signature HMAC SHA-256
        if (!$this->verifySignature($request->getContent(), $signature)) {
            Log::warning('SagaPass Webhook: Signature invalide', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signature, 7);
        $secret = config('services.sagapass.webhook_secret');

        $computedHash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedHash, $expectedHash);
    }
}
```

### Controller Webhook

```php
// app/Http/Controllers/SagaPassWebhookController.php

namespace App\Http\Controllers;

use App\Models\SagaPassVerification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SagaPassWebhookController extends Controller
{
    /**
     * Traiter les webhooks SagaPass
     * 
     * Route: POST /sagapass/webhook
     * Middleware: VerifySagaPassSignature
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // 1. Extraire les headers et données
        $event = $request->header('X-Saga-Event');
        $challengeId = $request->header('X-Saga-Challenge-Id');
        $payload = $request->all();

        Log::info('SagaPass Webhook reçu', [
            'event' => $event,
            'challenge_id' => $challengeId,
            'reference' => $payload['verification']['reference'] ?? null,
        ]);

        // 2. Trouver la vérification
        $verification = SagaPassVerification::where('challenge_id', $challengeId)
            ->first();

        if (!$verification) {
            Log::warning('SagaPass Webhook: Challenge inconnu', [
                'challenge_id' => $challengeId,
            ]);
            // Retourner 200 quand même pour éviter les retries
            return response()->json(['success' => true]);
        }

        // 3. Traiter selon l'événement
        try {
            match ($event) {
                'verification.confirmed' => $this->handleConfirmed($verification, $payload),
                'verification.rejected' => $this->handleRejected($verification, $payload),
                'verification.expired' => $this->handleExpired($verification, $payload),
                default => Log::warning('SagaPass Webhook: Événement inconnu', ['event' => $event]),
            };

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('SagaPass Webhook: Erreur de traitement', [
                'challenge_id' => $challengeId,
                'error' => $e->getMessage(),
            ]);

            // Retourner 500 pour que SagaPass réessaye
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function handleConfirmed(SagaPassVerification $verification, array $payload): void
    {
        // Mettre à jour la vérification
        $verification->update([
            'status' => 'confirmed',
            'user_id' => $payload['user']['citizen_id'] ?? null,
            'responded_at' => now(),
        ]);

        Log::info('SagaPass: Vérification confirmée', [
            'challenge_id' => $verification->challenge_id,
            'user_id' => $verification->user_id,
        ]);

        // 🎯 ACTIONS BUSINESS À IMPLÉMENTER ICI
        // Exemples:
        // - Activer le compte utilisateur
        // - Envoyer email de confirmation
        // - Débloquer une commande
        // - Démarrer un processus métier
        // - Notifier l'utilisateur frontend via WebSocket/Pusher

        // Exemple: Event Laravel pour découplage
        // event(new UserVerified($verification));
    }

    private function handleRejected(SagaPassVerification $verification, array $payload): void
    {
        $verification->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        Log::warning('SagaPass: Vérification rejetée par l\'utilisateur', [
            'challenge_id' => $verification->challenge_id,
        ]);

        // 🎯 ACTIONS BUSINESS À IMPLÉMENTER ICI
        // Exemples:
        // - Bloquer le compte temporairement
        // - Alerter l'équipe sécurité
        // - Annuler la commande/demande
        // - Envoyer notification à l'admin
    }

    private function handleExpired(SagaPassVerification $verification, array $payload): void
    {
        $verification->update([
            'status' => 'expired',
        ]);

        Log::info('SagaPass: Vérification expirée (48h)', [
            'challenge_id' => $verification->challenge_id,
        ]);

        // 🎯 ACTIONS BUSINESS À IMPLÉMENTER ICI
        // Exemples:
        // - Annuler automatiquement la demande
        // - Envoyer rappel à l'utilisateur
        // - Archiver la tentative
        // - Permettre une nouvelle soumission
    }
}
```

### Routes

```php
// routes/api.php

use App\Http\Controllers\SagaPassWebhookController;
use App\Http\Middleware\VerifySagaPassSignature;

Route::post('/sagapass/webhook', [SagaPassWebhookController::class, 'handleWebhook'])
    ->middleware(VerifySagaPassSignature::class)
    ->name('sagapass.webhook');
```

### Enregistrement du Middleware

```php
// bootstrap/app.php (Laravel 11)

->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'sagapass.signature' => \App\Http\Middleware\VerifySagaPassSignature::class,
    ]);
})
```

---

## 🧪 Tests et Validation

### Test Unitaire du Middleware

```php
// tests/Unit/VerifySagaPassSignatureTest.php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Middleware\VerifySagaPassSignature;

class VerifySagaPassSignatureTest extends TestCase
{
    public function test_rejects_request_without_signature()
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"test": true}');
        $middleware = new VerifySagaPassSignature();

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(401, $response->status());
    }

    public function test_accepts_valid_signature()
    {
        $payload = json_encode(['event' => 'test']);
        $secret = config('services.sagapass.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_Saga_Signature' => $signature,
        ], $payload);

        $middleware = new VerifySagaPassSignature();

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->content());
    }
}
```

### Test d'Intégration

```php
// tests/Feature/SagaPassWebhookTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SagaPassVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SagaPassWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function signPayload(array $payload): string
    {
        $secret = config('services.sagapass.webhook_secret');
        $json = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $json, $secret);
    }

    public function test_handles_confirmed_webhook()
    {
        $verification = SagaPassVerification::create([
            'challenge_id' => 'test-123',
            'challenge_type' => 'confirmation',
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'verification.confirmed',
            'challenge_id' => 'test-123',
            'status' => 'confirmed',
            'user' => ['citizen_id' => 42],
        ];

        $response = $this->postJson('/api/sagapass/webhook', $payload, [
            'X-Saga-Event' => 'verification.confirmed',
            'X-Saga-Challenge-Id' => 'test-123',
            'X-Saga-Signature' => $this->signPayload($payload),
        ]);

        $response->assertOk();
        $this->assertEquals('confirmed', $verification->fresh()->status);
    }

    public function test_handles_rejected_webhook()
    {
        $verification = SagaPassVerification::create([
            'challenge_id' => 'test-456',
            'challenge_type' => 'confirmation',
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'verification.rejected',
            'challenge_id' => 'test-456',
            'status' => 'rejected',
        ];

        $response = $this->postJson('/api/sagapass/webhook', $payload, [
            'X-Saga-Event' => 'verification.rejected',
            'X-Saga-Challenge-Id' => 'test-456',
            'X-Saga-Signature' => $this->signPayload($payload),
        ]);

        $response->assertOk();
        $this->assertEquals('rejected', $verification->fresh()->status);
    }
}
```

### Test Manuel avec Webhook.site

```bash
# 1. Aller sur https://webhook.site
# 2. Copier votre URL unique
# 3. Tester l'envoi:

curl -X POST https://sagapass.com/api/partner/verify \
  -H "Content-Type: application/json" \
  -d '{
    "encrypted": true,
    "data": "YOUR_ENCRYPTED_DATA",
    "webhook_url": "https://webhook.site/YOUR-UNIQUE-ID"
  }'

# 4. Voir le webhook arriver sur webhook.site
# 5. Vérifier la signature HMAC
```

---

## ✅ Checklist de Déploiement

### Avant le Déploiement

- [ ] **Base de données**
  - [ ] Migration `add_sagapass_challenge_type` créée et testée
  - [ ] Colonne `challenge_type` ajoutée à la table
  - [ ] Backup de la base de données effectué

- [ ] **Configuration**
  - [ ] `SAGAPASS_API_URL` dans `.env`
  - [ ] `SAGAPASS_APP_KEY` dans `.env`
  - [ ] Route webhook accessible publiquement
  - [ ] Middleware HMAC implémenté et testé

- [ ] **Code**
  - [ ] `SagaPassService` mis à jour avec gestion des 2 types
  - [ ] `SagaPassWebhookController` créé
  - [ ] Middleware `VerifySagaPassSignature` créé
  - [ ] Routes webhook configurées (sans CSRF)
  - [ ] Model `SagaPassVerification` mis à jour

- [ ] **Tests**
  - [ ] Tests unitaires passent (signature HMAC)
  - [ ] Tests d'intégration passent (webhook endpoints)
  - [ ] Test manuel avec webhook.site réussi
  - [ ] Test en staging avec vrais webhooks SagaPass

### Pendant le Déploiement

- [ ] **Étape 1: Déployer le code**
  ```bash
  git pull origin main
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan optimize
  ```

- [ ] **Étape 2: Vérifier la route webhook**
  ```bash
  php artisan route:list | grep sagapass
  # Doit afficher: POST /api/sagapass/webhook
  ```

- [ ] **Étape 3: Tester le endpoint**
  ```bash
  curl -X POST https://votre-domaine.com/api/sagapass/webhook \
    -H "Content-Type: application/json" \
    -H "X-Saga-Signature: sha256=test" \
    -d '{"test": true}'
  # Doit retourner 401 (signature invalide) - C'est normal!
  ```

### Après le Déploiement

- [ ] **Monitoring**
  - [ ] Logs Laravel surveillés (`storage/logs/laravel.log`)
  - [ ] Webhooks arrivent correctement
  - [ ] Signatures HMAC validées sans erreurs
  - [ ] Aucune erreur 500 dans les logs

- [ ] **Validation fonctionnelle**
  - [ ] Test HTTP 202 (confirmation) → webhook `verified.confirmed` reçu
  - [ ] Test HTTP 409 (data_mismatch) → pas de webhook (normal)
  - [ ] Test HTTP 200 (déjà vérifié) → fonctionnel
  - [ ] Test expiration → webhook `verification.expired` reçu (48h)

- [ ] **Documentation interne**
  - [ ] Équipe informée des nouveaux codes HTTP
  - [ ] Procédure de debug documentée
  - [ ] Alerts configurées pour erreurs webhook

---

## 🔍 Debugging et Troubleshooting

### Webhook ne reçoit rien

**Vérifications:**
```bash
# 1. Route accessible?
curl -I https://votre-domaine.com/api/sagapass/webhook
# Doit retourner 405 (Method Not Allowed) pour GET

# 2. Logs SagaPass
tail -f storage/logs/laravel.log | grep -i sagapass

# 3. Firewall/Proxy bloque?
# Vérifier les règles firewall pour webhooks externes
```

### Erreur 401 - Signature invalide

**Causes probables:**
1. Clé `SAGAPASS_APP_KEY` incorrecte dans `.env`
2. Payload modifié par proxy/middleware
3. Cache config Laravel pas rafraîchi

**Solutions:**
```bash
# Vérifier la clé
php artisan config:show services.sagapass.webhook_secret

# Rafraîchir le cache
php artisan config:clear
php artisan cache:clear

# Tester manuellement
php artisan tinker
>>> hash_hmac('sha256', '{"test":true}', config('services.sagapass.webhook_secret'))
```

### Webhook reçu mais pas traité

**Logs à vérifier:**
```php
// Dans SagaPassWebhookController.php, ajouter temporairement:
Log::info('DEBUG Webhook', [
    'headers' => $request->headers->all(),
    'payload' => $request->all(),
    'raw_content' => $request->getContent(),
]);
```

### Retries multiples

Si SagaPass réessaye 3 fois:
1. ✅ Vérifier que vous retournez **200 OK** rapidement (< 5s)
2. ✅ Ne pas faire de traitements longs dans le controller
3. ✅ Utiliser des **Jobs Laravel** pour le traitement asynchrone

**Exemple avec Job:**
```php
// Dans handleConfirmed()
dispatch(new ProcessSagaPassConfirmation($verification, $payload));

return response()->json(['success' => true]); // Réponse immédiate
```

---

## 📊 Résumé des Codes HTTP

| Code | Type | Signification | Action Partenaire | Webhook? |
|------|------|---------------|-------------------|----------|
| **200** | ✅ Success | Déjà vérifié | Continuer le processus | ❌ Non |
| **202** | ⏳ Pending | Confirmation requise | Attendre webhook/polling | ✅ Oui |
| **403** | ❌ Forbidden | Compte rejeté/banni | Bloquer l'utilisateur | ❌ Non |
| **409** | ⚠️ Conflict | Variation de nom | Demander correction | ❌ Non |
| **500** | 💥 Error | Erreur serveur | Réessayer plus tard | ❌ Non |

---

## 📞 Support et Contact

### En cas de problème

1. **Logs Laravel**
   ```bash
   tail -f storage/logs/laravel.log | grep -i sagapass
   ```

2. **Support SagaPass**
   - 📧 Email: support@sagapass.com
   - 📖 Documentation: https://sagapass.com/docs
   - 🔐 Panel développeur: https://sagapass.com/developer

3. **Délai de réponse**: Sous 24h (jours ouvrables)

### Questions fréquentes

**Q: Puis-je utiliser les webhooks en local (localhost)?**  
R: Non, SagaPass ne peut pas envoyer vers localhost. Utilisez **ngrok** ou **webhook.site** pour les tests locaux.

**Q: Combien de temps SagaPass réessaye en cas d'erreur?**  
R: 3 tentatives avec délais: 30s, 2min, 5min. Total ~7 minutes.

**Q: Que faire si mon serveur est inaccessible pendant > 7 min?**  
R: Implémentez un système de **polling de fallback** pour vérifier le statut du challenge.

**Q: Les webhooks fonctionnent-ils en HTTP (pas HTTPS)?**  
R: Non, **HTTPS obligatoire** en production pour la sécurité.

---

**Document mis à jour le:** 31 Mars 2026  
**Version:** 1.0  
**Prochaine révision:** 15 Avril 2026
