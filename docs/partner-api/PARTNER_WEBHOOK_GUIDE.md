# 🔔 Guide Webhooks SagaPass - Notifications en Temps Réel

## 📋 Table des Matières
- [Vue d'ensemble](#vue-densemble)
- [Configuration](#configuration)
- [Événements Webhook](#événements-webhook)
- [Sécurité HMAC](#sécurité-hmac)
- [Exemples d'Intégration](#exemples-dintégration)
- [Automatisation de l'Expiration](#automatisation-de-lexpiration)
- [Tests et Debugging](#tests-et-debugging)

---

## 🎯 Vue d'ensemble

Les **webhooks SagaPass** permettent à votre application de recevoir des notifications en temps réel quand un utilisateur **confirme ou rejette** une demande de vérification d'identité de **type "confirmation"**.

⚠️ **IMPORTANT** : Les webhooks sont **UNIQUEMENT** envoyés pour les challenges de type **"confirmation"** (HTTP 202). Pour les challenges de type **"data_mismatch"** (HTTP 409), aucun webhook n'est envoyé car le partenaire reçoit déjà la réponse immédiatement.

### Deux Types de Challenges

| Type | HTTP | Quand ? | Webhook ? |
|------|------|---------|-----------|
| **confirmation** | 202 | Compte vérifié + données exactes | ✅ **OUI** (user doit confirmer) |
| **data_mismatch** | 409 | Variation de nom détectée | ❌ **NON** (réponse immédiate) |

### Pourquoi Pas de Webhook pour data_mismatch ?

Dans le cas **data_mismatch** :
1. ✅ Le partenaire reçoit **immédiatement** HTTP 409
2. 📝 L'utilisateur **corrige ses infos chez le partenaire**
3. 🔄 Le partenaire fait une **nouvelle requête de vérification**
4. ✅ Si correct → HTTP 202 (avec webhook) ou HTTP 200 (vérifié)

→ **Pas besoin d'attendre une réponse de l'utilisateur dans SagaPass**

### Avantages des Webhooks vs Polling

| Méthode | Délai | Charge Serveur | Expérience Utilisateur |
|---------|-------|----------------|------------------------|
| **Webhook** | ⚡ **Instantané (0-2s)** | ✅ Minimal | ⭐⭐⭐⭐⭐ Excellent |
| Polling (15s) | 🐌 Jusqu'à 15s | ❌ Élevé (1 req/15s) | ⭐⭐ Moyen |
| Long Polling | 🚶 2-5s | ⚠️ Moyen | ⭐⭐⭐⭐ Bon |

---

## ⚙️ Configuration

### 1. Envoi du Webhook URL lors de la Vérification

Lors de votre requête de vérification, ajoutez le champ `webhook_url` **seulement si vous voulez être notifié en temps réel** :

**Payload (encrypté ou non) :**
```json
{
    "encrypted": true,
    "data": "...",
    "webhook_url": "https://votresite.com/api/sagapass/webhook"
}
```

⚠️ **Note** : Le webhook sera envoyé **UNIQUEMENT** si la réponse est **HTTP 202** (`pending_user_confirmation`).

**Ou en clair (dev uniquement) :**
```json
{
    "encrypted": false,
    "first_name": "Jean",
    "last_name": "Dupont",
    "date_of_birth": "1990-01-15",
    "niu": "1234567890",
    "email": "jean@example.com",
    "webhook_url": "https://votresite.com/api/sagapass/webhook"
}
```

### 2. Cas HTTP 202 (Confirmation Requise)

SagaPass retourne **immédiatement** avec HTTP 202 - **Webhook sera envoyé** :

```json
{
    "success": false,
    "verified": false,
    "status": "pending_user_confirmation",
    "challenge_id": "uuid-challenge",
    "expires_at": "2026-04-02T12:00:00Z",
    "message": "L'utilisateur a été notifié et doit confirmer cette demande."
}
```

→ Votre webhook recevra la réponse quand l'utilisateur confirmera.

### 3. Cas HTTP 409 (Data Mismatch)

SagaPass retourne **immédiatement** avec HTTP 409 - **Aucun webhook envoyé** :

```json
{
    "success": false,
    "verified": false,
    "error": "data_mismatch",
    "status": "data_not_matching",
    "challenge_id": "uuid-challenge",
    "message": "Les informations ne correspondent pas exactement..."
}
```

→ **Pas de webhook** car l'utilisateur doit corriger ses infos chez vous, puis vous refaites une requête.

### 4. Notification Webhook Automatique (HTTP 202 seulement)

Quand l'utilisateur répond dans l'app SagaPass pour un challenge **"confirmation"** :
- ✅ **Webhook POST envoyé automatiquement** vers votre `webhook_url`
- 🔁 **3 tentatives de retry** (30s, 2min, 5min)
- 🔒 **Signature HMAC** pour vérifier l'authenticité

---

## 📨 Événements Webhook

⚠️ **Ces événements sont UNIQUEMENT envoyés pour les challenges de type "confirmation"** (HTTP 202).  
Pour les challenges "data_mismatch" (HTTP 409), **aucun webhook n'est envoyé** car aucune action n'est attendue de l'utilisateur dans SagaPass.

### `verification.confirmed`

L'utilisateur a confirmé qu'il est bien à l'origine de la demande **(challenge type: confirmation)**.

**Payload reçu :**
```json
{
    "event": "verification.confirmed",
    "challenge_id": "9abc1234-5678-90ef-1234-567890abcdef",
    "status": "confirmed",
    "user_response": "{\"confirmed\":true,\"needs_correction\":false,\"timestamp\":\"2026-03-31T20:15:00Z\"}",
    "challenge_type": "confirmation",
    
    "verification": {
        "reference": "ORDER-12345",
        "niu": "1234567890",
        "email": "jean@example.com",
        "phone": "+50937001234"
    },
    
    "timestamp": "2026-03-31T20:15:00Z",
    "responded_at": "2026-03-31T20:15:00Z",
    "expires_at": "2026-04-02T12:00:00Z",
    
    "user": {
        "citizen_id": 42,
        "verified": true
    }
}
```

### `verification.rejected`

L'utilisateur a indiqué que ce n'est pas lui (sécurité).

**Payload reçu :**
```json
{
    "event": "verification.rejected",
    "challenge_id": "9abc1234-5678-90ef-1234-567890abcdef",
    "status": "rejected",
    "user_response": "{\"confirmed\":false,\"timestamp\":\"2026-03-31T20:15:00Z\"}",
    "challenge_type": "confirmation",
    
    "verification": {
        "reference": "ORDER-12345",
        "niu": "1234567890",
        "email": "jean@example.com",
        "phone": null
    },
    
    "timestamp": "2026-03-31T20:15:00Z",
    "responded_at": "2026-03-31T20:15:00Z",
    "expires_at": "2026-04-02T12:00:00Z",
    
    "user": null
}
```

### `verification.expired`

Le délai de 48h est écoulé sans réponse de l'utilisateur.

**Payload reçu :**
```json
{
    "event": "verification.expired",
    "challenge_id": "9abc1234-5678-90ef-1234-567890abcdef",
    "status": "expired",
    "user_response": null,
    "challenge_type": "confirmation",
    
    "verification": {
        "reference": "ORDER-12345",
        "niu": "1234567890",
        "email": "jean@example.com"
    },
    
    "timestamp": "2026-04-02T12:00:01Z",
    "responded_at": null,
    "expires_at": "2026-04-02T12:00:00Z",
    
    "user": null
}
```

---

## 🔒 Sécurité HMAC

### Headers de Vérification

Chaque webhook inclut ces headers :

```http
POST /api/sagapass/webhook HTTP/1.1
Host: votresite.com
Content-Type: application/json
X-Saga-Signature: sha256=abc123def456...
X-Saga-Event: verification.confirmed
X-Saga-Challenge-Id: 9abc1234-5678-90ef-1234-567890abcdef
User-Agent: SagaPass-Webhook/1.0
```

### Vérification de Signature (OBLIGATOIRE)

⚠️ **IMPORTANT** : Toujours vérifier la signature pour éviter les webhooks forgés.

**La signature est calculée avec :**
```
HMAC-SHA256(json_payload, APP_KEY)
```

---

## 💻 Exemples d'Intégration

### PHP / Laravel

**Controller Webhook :**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SagaPassWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Vérifier la signature HMAC
        if (!$this->verifySignature($request)) {
            Log::warning('SagaPass Webhook - Signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 2. Récupérer les données
        $event = $request->header('X-Saga-Event');
        $challengeId = $request->header('X-Saga-Challenge-Id');
        $payload = $request->all();

        Log::info('SagaPass Webhook reçu', [
            'event' => $event,
            'challenge_id' => $challengeId,
        ]);

        // 3. Traiter selon l'événement
        switch ($event) {
            case 'verification.confirmed':
                $this->handleConfirmed($payload);
                break;

            case 'verification.rejected':
                $this->handleRejected($payload);
                break;

            case 'verification.expired':
                $this->handleExpired($payload);
                break;
        }

        // 4. Répondre rapidement (200 OK)
        return response()->json(['success' => true], 200);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Saga-Signature');
        if (!$signature || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signature, 7); // Remove "sha256="
        $payload = $request->getContent();
        $secret = config('sagapass.app_key'); // Votre APP_KEY SagaPass

        $computedHash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computedHash, $expectedHash);
    }

    private function handleConfirmed(array $payload)
    {
        $reference = $payload['verification']['reference'];
        $citizenId = $payload['user']['citizen_id'];

        // Mettre à jour votre commande/client
        $order = Order::where('reference', $reference)->first();
        if ($order) {
            $order->update([
                'sagapass_verified' => true,
                'sagapass_citizen_id' => $citizenId,
                'verification_status' => 'confirmed',
                'verified_at' => now(),
            ]);

            // Déclencher les actions métier (paiement, livraison, etc.)
            $this->processVerifiedOrder($order);

            Log::info('SagaPass Webhook - Commande vérifiée', [
                'order_id' => $order->id,
                'citizen_id' => $citizenId,
            ]);
        }
    }

    private function handleRejected(array $payload)
    {
        $reference = $payload['verification']['reference'];

        $order = Order::where('reference', $reference)->first();
        if ($order) {
            $order->update([
                'verification_status' => 'rejected',
                'verified_at' => now(),
            ]);

            // Notifier l'équipe fraude
            $this->notifySecurityTeam($order);

            Log::warning('SagaPass Webhook - Vérification rejetée', [
                'order_id' => $order->id,
            ]);
        }
    }

    private function handleExpired(array $payload)
    {
        $reference = $payload['verification']['reference'];

        $order = Order::where('reference', $reference)->first();
        if ($order) {
            $order->update([
                'verification_status' => 'expired',
            ]);

            // Relancer le client
            $this->sendReminderEmail($order);

            Log::info('SagaPass Webhook - Challenge expiré', [
                'order_id' => $order->id,
            ]);
        }
    }
}
```

**Route (routes/api.php) :**
```php
Route::post('/sagapass/webhook', [SagaPassWebhookController::class, 'handle'])
    ->name('sagapass.webhook');
```

**⚠️ IMPORTANT :** Désactivez le CSRF pour cette route dans `App\Http\Middleware\VerifyCsrfToken.php` :
```php
protected $except = [
    '/api/sagapass/webhook',
];
```

---

### Node.js / Express

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json());

app.post('/api/sagapass/webhook', (req, res) => {
    // 1. Vérifier la signature
    const signature = req.headers['x-saga-signature'];
    const payload = JSON.stringify(req.body);
    const secret = process.env.SAGAPASS_APP_KEY;
    
    const expectedHash = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    if (signature !== expectedHash) {
        console.log('❌ Signature invalide');
        return res.status(401).json({ error: 'Invalid signature' });
    }
    
    // 2. Traiter l'événement
    const event = req.headers['x-saga-event'];
    const { verification, user, status } = req.body;
    
    console.log(`✅ Webhook reçu: ${event}`, verification.reference);
    
    if (event === 'verification.confirmed') {
        // Mettre à jour votre DB
        updateOrder(verification.reference, {
            verified: true,
            citizen_id: user.citizen_id,
            status: 'confirmed'
        });
    }
    
    // 3. Répondre immédiatement
    res.json({ success: true });
});

app.listen(3000, () => console.log('Webhook listener démarré'));
```

---

### Python / Django

```python
import hmac
import hashlib
import json
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.conf import settings
import logging

logger = logging.getLogger(__name__)

@csrf_exempt
def sagapass_webhook(request):
    if request.method != 'POST':
        return JsonResponse({'error': 'Method not allowed'}, status=405)
    
    # 1. Vérifier la signature
    signature = request.headers.get('X-Saga-Signature', '')
    if not verify_signature(request.body, signature):
        logger.warning('SagaPass Webhook - Signature invalide')
        return JsonResponse({'error': 'Invalid signature'}, status=401)
    
    # 2. Parser les données
    payload = json.loads(request.body)
    event = request.headers.get('X-Saga-Event')
    challenge_id = request.headers.get('X-Saga-Challenge-Id')
    
    logger.info(f'SagaPass Webhook reçu: {event}', extra={
        'challenge_id': challenge_id,
        'reference': payload['verification']['reference']
    })
    
    # 3. Traiter selon l'événement
    if event == 'verification.confirmed':
        handle_confirmed(payload)
    elif event == 'verification.rejected':
        handle_rejected(payload)
    elif event == 'verification.expired':
        handle_expired(payload)
    
    # 4. Répondre rapidement
    return JsonResponse({'success': True})

def verify_signature(payload, signature):
    if not signature.startswith('sha256='):
        return False
    
    expected_hash = signature[7:]
    secret = settings.SAGAPASS_APP_KEY
    
    computed_hash = hmac.new(
        secret.encode(),
        payload,
        hashlib.sha256
    ).hexdigest()
    
    return hmac.compare_digest(computed_hash, expected_hash)

def handle_confirmed(payload):
    reference = payload['verification']['reference']
    citizen_id = payload['user']['citizen_id']
    
    # Mettre à jour votre modèle
    from orders.models import Order
    try:
        order = Order.objects.get(reference=reference)
        order.sagapass_verified = True
        order.citizen_id = citizen_id
        order.verification_status = 'confirmed'
        order.save()
        
        logger.info(f'Commande {order.id} vérifiée avec succès')
    except Order.DoesNotExist:
        logger.error(f'Commande {reference} introuvable')
```

---

## 🧪 Tests et Debugging

### Tester le Webhook URL

**Exemple de payload test (simulation) :**

```bash
curl -X POST https://votresite.com/api/sagapass/webhook \
  -H "Content-Type: application/json" \
  -H "X-Saga-Event: verification.confirmed" \
  -H "X-Saga-Challenge-Id: test-challenge-123" \
  -H "X-Saga-Signature: sha256=VOTRE_SIGNATURE_CALCULEE" \
  -d '{
    "event": "verification.confirmed",
    "challenge_id": "test-123",
    "status": "confirmed",
    "verification": {
      "reference": "TEST-ORDER",
      "niu": "1234567890"
    },
    "user": {
      "citizen_id": 99,
      "verified": true
    }
  }'
```

### Logs SagaPass

Les webhooks sont loggés dans `storage/logs/laravel.log` :

```log
[2026-03-31 20:15:00] local.INFO: NotifyPartnerWebhook - Webhook envoyé avec succès
  {"challenge_id": "9abc1234", "event": "verification.confirmed", "webhook_url": "votresite.com/api/sagapass/webhook", "status_code": 200, "attempt": 1}
```

### Codes de Retour Attendus

| Code | Signification | Action SagaPass |
|------|---------------|-----------------|
| **200-299** | ✅ Succès | Pas de retry |
| **4xx** | ❌ Erreur client | Pas de retry (webhook mal configuré) |
| **5xx** | ⚠️ Erreur serveur | 3 tentatives de retry (30s, 2min, 5min) |
| **Timeout** | ⏱️ Pas de réponse | 3 tentatives de retry |

---

## 🔄 Stratégie de Fallback

Si votre webhook est temporairement down, vous pouvez toujours utiliser le polling :

```php
// Polling de fallback (à implémenter dans votre frontend)
async function pollChallengeStatus(challengeId) {
    const interval = setInterval(async () => {
        const response = await fetch(`/api/check-sagapass-status/${challengeId}`);
        const data = await response.json();
        
        if (data.status === 'confirmed' || data.status === 'rejected') {
            clearInterval(interval);
            handleVerificationResult(data);
        }
    }, 15000); // Check chaque 15 secondes
    
    // Timeout après 10 minutes
    setTimeout(() => clearInterval(interval), 600000);
}
```

---

## ⏰ Automatisation de l'Expiration

### Tâche Planifiée Automatique

SagaPass utilise une **tâche planifiée Laravel** pour nettoyer automatiquement les challenges expirés et envoyer les webhooks `verification.expired`.

**Configuration du scheduler** (dans `bootstrap/app.php`) :

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('challenges:clean-expired')
        ->hourly()                  // S'exécute toutes les heures
        ->withoutOverlapping()      // Évite les exécutions simultanées
        ->onOneServer();           // En production multi-serveur
})
```

### Commande de Nettoyage

La commande `challenges:clean-expired` :
1. ✅ Trouve tous les challenges `pending` où `expires_at < maintenant`
2. ✅ Marque leur statut comme `expired`
3. ✅ **Envoie un webhook `verification.expired`** si :
   - `challenge_type = 'confirmation'` (seulement)
   - `webhook_url` est configuré

**Test manuel** :
```bash
# Mode dry-run (simulation sans modification)
php artisan challenges:clean-expired --dry-run

# Exécution réelle
php artisan challenges:clean-expired
```

### Fréquence d'Exécution

| Fréquence | Commande | Impact |
|-----------|----------|--------|
| **Toutes les heures** | `->hourly()` | ✅ **Recommandé** (délai max 1h) |
| Toutes les 30 min | `->everyThirtyMinutes()` | ⚡ Plus réactif |
| Une fois par jour | `->daily()` | ⚠️ Pas assez fréquent |

**Note** : Avec `->hourly()`, un challenge expirant à 14h05 sera traité au plus tard à 15h00.

### Activation du Scheduler

Pour que les tâches planifiées s'exécutent, ajoutez à votre **cron** (Linux/Mac) ou **Task Scheduler** (Windows) :

**Linux/Mac (crontab)** :
```bash
* * * * * cd /path-to-sagapass && php artisan schedule:run >> /dev/null 2>&1
```

**Windows Task Scheduler** :
```powershell
Action: Start a program
Program: C:\PHP\php.exe
Arguments: artisan schedule:run
Start in: C:\path-to-sagapass\saga-id
Trigger: Repeat every 1 minute
```

Le scheduler Laravel s'occupe ensuite d'exécuter `challenges:clean-expired` à l'heure prévue.

---

## 📚 Résumé

✅ **Configuration** : Ajoutez `webhook_url` lors de la vérification  
✅ **Sécurité** : Vérifiez toujours la signature HMAC  
✅ **Performance** : Répondez rapidement (< 5 secondes)  
✅ **Idempotence** : Traitez les retries de manière idempotente  
✅ **Fallback** : Gardez un système de polling en backup  

---

## 🆘 Support

En cas de problème :
- 📧 Email : support@sagapass.com
- 📖 Documentation : https://sagapass.com/docs
- 🔐 Clés API : Panel développeur SagaPass

**Délai de réponse** : Sous 24h (jours ouvrables)
