# ⚠️ CHANGEMENTS IMPORTANTS - API Partner v1 (MARS 2026)

## 🔴 MISE À JOUR OBLIGATOIRE POUR LES PARTENAIRES

Date de mise à jour : **31 Mars 2026**

---

## 📊 Résumé des Changements

Le système de vérification a été **renforcé** avec deux nouveaux cas de réponse. **Toutes les intégrations existantes doivent être mises à jour.**

### 🆕 Nouvelles Réponses API

| Code HTTP | Status | Ancien Comportement | Nouveau Comportement |
|-----------|--------|---------------------|----------------------|
| **202** | `pending_user_confirmation` | ❌ N'existait pas | ✅ **NOUVEAU** - User doit confirmer |
| **409** | `data_not_matching` | ❌ N'existait pas | ✅ **NOUVEAU** - Données ne correspondent pas |

### ⚡ Impact sur les Intégrations

**AVANT (Ancien flow)** :
```
Compte vérifié + NIU correspond 
  → HTTP 200 + verified: true
  → ✅ Accès autorisé immédiatement
```

**APRÈS (Nouveau flow)** :
```
Compte vérifié + NIU correspond
  → HTTP 202 + pending_user_confirmation
  → ⏳ L'utilisateur DOIT confirmer dans SagaPass
  → ⏳ Le partenaire attend la confirmation
```

---

## 🔴 1. NOUVEAU CAS : Confirmation Utilisateur Requise (HTTP 202)

### Description

**Même si le compte est vérifié et que les données correspondent exactement**, le système demande maintenant à l'utilisateur de **confirmer activement** que c'est bien lui qui a demandé cette vérification.

### Pourquoi ?

**🔒 Sécurité renforcée** : Prévenir l'usurpation d'identité
- Si quelqu'un connaît le NIU d'un citoyen, il ne peut plus automatiquement accéder à des services en son nom
- L'utilisateur doit explicitement approuver chaque demande de vérification

### Réponse API

**Code HTTP** : `202 Accepted` (En attente d'approbation)

```json
{
  "success": false,
  "verified": false,
  "error": "user_confirmation_required",
  "status": "pending_user_confirmation",
  "message": "L'utilisateur a été notifié et doit confirmer cette demande de vérification pour des raisons de sécurité.",
  "action_required": "L'utilisateur doit ouvrir son application SagaPass et confirmer qu'il est bien à l'origine de cette demande.",
  "challenge_id": 123,
  "expires_at": "2026-04-02T15:30:00Z"
}
```

### Champs Clés

| Champ | Type | Description |
|-------|------|-------------|
| `error` | string | `"user_confirmation_required"` |
| `status` | string | `"pending_user_confirmation"` |
| `challenge_id` | integer | ID du challenge de confirmation |
| `expires_at` | string | Date d'expiration (48h) |
| `action_required` | string | Instructions pour l'utilisateur |

### 🎯 Actions Requises par le Partenaire

1. **❌ NE PAS autoriser l'accès immédiatement**
2. **📱 Afficher un message à l'utilisateur** :
   ```
   "Veuillez ouvrir votre application SagaPass et confirmer cette demande."
   ```
3. **⏳ Implémenter un système d'attente** :
   - Polling (vérifier toutes les 10-30 secondes)
   - Webhook (si disponible)
   - Timeout après 48h (voir `expires_at`)

4. **🔄 Vérifier le statut ultérieurement** :
   - Faire une nouvelle requête `/api/partner/v1/verify` après confirmation
   - Ou implémenter un webhook pour recevoir la notification

### Exemple de Flow d'Intégration

```php
// 1. Première requête de vérification
$response = callSagaPassAPI($citizenData);

if ($response['status'] === 'pending_user_confirmation') {
    // 2. Afficher message à l'utilisateur
    echo "Veuillez confirmer dans votre app SagaPass (Challenge #{$response['challenge_id']})";
    
    // 3. Attendre confirmation (polling ou webhook)
    $challengeId = $response['challenge_id'];
    $expiresAt = $response['expires_at'];
    
    // Option A: Polling
    while (time() < strtotime($expiresAt)) {
        sleep(15); // Attendre 15 secondes
        $statusResponse = callSagaPassAPI($citizenData);
        
        if ($statusResponse['status'] === 'verified') {
            // ✅ Confirmation reçue
            allowAccess();
            break;
        }
    }
    
    // Option B: Webhook (voir section Webhooks)
    // Le système SagaPass appelle votre endpoint quand l'user confirme
}
```

---

## 🔴 2. NOUVEAU CAS : Discordance de Données (HTTP 409)

### Description

Lorsqu'une **variation de nom** est détectée (ex: "Sebastian" vs "Sebastien"), le système :
1. ❌ **N'envoie PAS les vraies données SagaPass au partenaire** (sécurité)
2. 📱 Notifie l'utilisateur dans SagaPass
3. ⚠️ Demande à l'utilisateur de corriger ses infos chez le partenaire

### Pourquoi ?

**🔒 Sécurité** : Ne jamais exposer les données officielles SagaPass
- Le partenaire ne doit pas connaître le nom exact si les données ne correspondent pas
- L'utilisateur corrige directement chez le partenaire

### Réponse API

**Code HTTP** : `409 Conflict` (Action utilisateur requise)

```json
{
  "success": false,
  "verified": false,
  "error": "data_mismatch",
  "status": "data_not_matching",
  "message": "Les informations fournies ne correspondent pas exactement aux données enregistrées. L'utilisateur a été notifié.",
  "action_required": "Demandez à l'utilisateur de vérifier et corriger ses informations chez vous pour qu'elles correspondent à son compte SagaPass officiel.",
  "challenge_id": 456,
  "expires_at": "2026-04-02T15:30:00Z"
}
```

### Champs Clés

| Champ | Type | Description |
|-------|------|-------------|
| `error` | string | `"data_mismatch"` |
| `status` | string | `"data_not_matching"` |
| `challenge_id` | integer | ID du challenge |
| `expires_at` | string | Date d'expiration |
| `action_required` | string | Instructions |

### ⚠️ CE QUI A CHANGÉ

**AVANT** :
```json
{
  "error": "name_verification_required",
  "data": {
    "sagapass_name": "Dary Sebastian Petion",  // ❌ EXPOSÉ
    "partner_name": "Dary Sebastien Petion"
  }
}
```

**APRÈS** :
```json
{
  "error": "data_mismatch",
  "message": "Les informations ne correspondent pas",
  // ✅ AUCUNE donnée SagaPass exposée
}
```

### 🎯 Actions Requises par le Partenaire

1. **❌ NE PAS autoriser l'accès**
2. **📱 Afficher un message à l'utilisateur** :
   ```
   "Les informations ne correspondent pas à votre compte SagaPass.
   Veuillez vérifier et corriger votre nom dans votre profil."
   ```
3. **✏️ Permettre à l'utilisateur de modifier ses informations** :
   - Ouvrir le formulaire de profil
   - Permettre la correction du nom
   - Proposer de comparer avec le nom officiel SagaPass

4. **🔄 Réessayer après correction** :
   ```php
   if ($response['error'] === 'data_mismatch') {
       echo "Vos informations ne correspondent pas.";
       echo "Veuillez mettre à jour votre profil.";
       
       // Rediriger vers formulaire de profil
       redirectToProfileEdit();
   }
   ```

---

## 📊 Tableau Comparatif Complet

### AVANT (Ancien comportement)

| Scénario | HTTP | Réponse | Action |
|----------|------|---------|--------|
| Compte vérifié + Données OK | 200 | `verified: true` | ✅ Accès immédiat |
| Compte vérifié + Variation nom | 409 | `name_verification_required` + données exposées | ⚠️ Challenge |
| Compte non vérifié | 200 | `pending_verification` | ⏳ Attendre |

### APRÈS (Nouveau comportement)

| Scénario | HTTP | Réponse | Action |
|----------|------|---------|--------|
| Compte vérifié + Données exactes | **202** | `pending_user_confirmation` | **⏳ User doit confirmer** |
| Compte vérifié + Variation nom | **409** | `data_not_matching` (sans données) | **⚠️ User corrige chez partner** |
| Compte non vérifié | 200 | `pending_verification` | ⏳ Attendre |

---

## 🔄 Guide de Migration

### Étape 1 : Gérer HTTP 202

**Ancien code :**
```php
if ($response['verified'] === true) {
    allowAccess(); // ✅ Accès immédiat
}
```

**Nouveau code :**
```php
if ($response['verified'] === true) {
    allowAccess(); // ✅ OK
} elseif ($response['status'] === 'pending_user_confirmation') {
    // 🆕 NOUVEAU CAS
    showConfirmationPendingMessage($response['challenge_id']);
    waitForUserConfirmation(); // Polling ou webhook
}
```

### Étape 2 : Gérer HTTP 409 (Data Mismatch)

**Ancien code :**
```php
if ($response['error'] === 'name_verification_required') {
    // Afficher les deux noms
    echo "SagaPass: " . $response['data']['sagapass_name'];
    echo "Votre profil: " . $response['data']['partner_name'];
}
```

**Nouveau code :**
```php
if ($response['error'] === 'data_mismatch') {
    // 🆕 PLUS DE DONNÉES EXPOSÉES
    echo "Les informations ne correspondent pas.";
    echo "Veuillez corriger votre profil.";
    redirectToProfileEdit();
}
```

### Étape 3 : Implémenter le Polling (Optional)

```php
function waitForUserConfirmation($citizenData, $expiresAt) {
    $maxAttempts = 96; // 48h / 30 sec = 96 tentatives
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        sleep(30); // Attendre 30 secondes
        
        $response = callSagaPassAPI($citizenData);
        
        if ($response['verified'] === true) {
            return true; // ✅ Confirmé
        }
        
        if ($response['status'] === 'rejected') {
            return false; // ❌ Rejeté par user
        }
        
        $attempt++;
    }
    
    return null; // ⏱️ Timeout
}
```

---

## 🎯 Checklist de Mise à Jour

### Pour les Développeurs Partenaires

- [ ] **Gérer HTTP 202** (`pending_user_confirmation`)
  - [ ] Afficher message "Confirmez dans SagaPass"
  - [ ] Implémenter attente (polling ou webhook)
  - [ ] Gérer timeout (48h)

- [ ] **Gérer HTTP 409** (`data_mismatch`)
  - [ ] Afficher message "Informations ne correspondent pas"
  - [ ] Rediriger vers formulaire de profil
  - [ ] Permettre correction

- [ ] **Retirer références aux anciennes réponses**
  - [ ] Supprimer gestion de `name_verification_required`
  - [ ] Supprimer lecture de `sagapass_name` / `partner_name` dans erreur

- [ ] **Tester les nouveaux scénarios**
  - [ ] Test: Compte vérifié → HTTP 202
  - [ ] Test: Variation nom → HTTP 409
  - [ ] Test: Confirmation user → HTTP 200

---

## � 3. NOUVELLE FONCTIONNALITÉ : Webhooks en Temps Réel

### Description

Au lieu d'attendre avec **polling** (vérifier toutes les 15-30 secondes), vous pouvez maintenant recevoir des **notifications instantanées** via webhook quand l'utilisateur confirme ou rejette une demande de vérification.

⚠️ **IMPORTANT** : Les webhooks sont **UNIQUEMENT** envoyés pour les challenges de type **"confirmation"** (HTTP 202). Pour les challenges de type **"data_mismatch"** (HTTP 409), aucun webhook n'est envoyé car vous recevez déjà la réponse immédiatement.

### Quand le Webhook est-il Envoyé ?

| Scénario | Réponse API | Webhook ? | Pourquoi ? |
|----------|-------------|-----------|------------|
| Compte vérifié + données exactes | **HTTP 202** (confirmation) | ✅ **OUI** | User doit confirmer dans SagaPass |
| Variation de nom détectée | **HTTP 409** (data_mismatch) | ❌ **NON** | User corrige chez le partenaire, pas d'attente |
| Compte non vérifié | HTTP 200 (pending) | ❌ **NON** | Pas de challenge créé |

### Avantages

Pour les challenges **"confirmation"** (HTTP 202) uniquement :

| Méthode | Délai | Charge Serveur | UX |
|---------|-------|----------------|-----|
| **Webhook** | ⚡ **0-2 secondes** | ✅ Minimal | ⭐⭐⭐⭐⭐ |
| Polling | 🐌 15-30 secondes | ❌ Élevé | ⭐⭐ |

Pour les challenges **"data_mismatch"** (HTTP 409) : **Pas de webhook nécessaire** car réponse immédiate.

### Configuration

**1. Ajoutez `webhook_url` lors de votre requête de vérification (optionnel) :**

```php
$payload = [
    'encrypted' => true,
    'data' => $encryptedData,
    'webhook_url' => 'https://votresite.com/api/sagapass/webhook' // 🆕 NOUVEAU (optionnel)
];
```

⚠️ **Note importante** :
- Le `webhook_url` est **optionnel**
- Il sera **utilisé uniquement** si la réponse est **HTTP 202** (pending_user_confirmation)
- Pour **HTTP 409** (data_mismatch), **aucun webhook ne sera envoyé** même si vous fournissez un webhook_url
- Vous pouvez toujours utiliser le **polling** comme fallback

**2. SagaPass enverra automatiquement une requête POST vers votre webhook :**

```http
POST /api/sagapass/webhook HTTP/1.1
Host: votresite.com
Content-Type: application/json
X-Saga-Signature: sha256=abc123def456...
X-Saga-Event: verification.confirmed
X-Saga-Challenge-Id: 9abc1234-5678-90ef

{
  "event": "verification.confirmed",
  "challenge_id": "9abc1234-5678-90ef",
  "status": "confirmed",
  "user": {
    "citizen_id": 42,
    "verified": true
  },
  "verification": {
    "reference": "ORDER-12345",
    "niu": "1234567890"
  },
  "timestamp": "2026-03-31T20:15:00Z"
}
```

### Événements Webhook

| Événement | Description | Payload Inclut |
|-----------|-------------|----------------|
| `verification.confirmed` | User a confirmé | `user.citizen_id`, `verified: true` |
| `verification.rejected` | User a rejeté (sécurité) | `user: null`, raison |
| `verification.expired` | Timeout 48h | `user: null` |

### Sécurité HMAC

⚠️ **IMPORTANT** : Toujours vérifier la signature pour éviter les webhooks forgés.

```php
function verifyWebhookSignature($request) {
    $signature = $request->header('X-Saga-Signature');
    $payload = $request->getContent();
    $secret = config('sagapass.app_key'); // Votre clé d'app
    
    $expectedHash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    return hash_equals($signature, $expectedHash);
}
```

### Exemple d'Intégration Complète

```php
// routes/api.php
Route::post('/sagapass/webhook', [SagaPassWebhookController::class, 'handle']);

// Controller
class SagaPassWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Vérifier la signature
        if (!$this->verifySignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // 2. Récupérer l'événement
        $event = $request->header('X-Saga-Event');
        $payload = $request->all();
        
        // 3. Traiter
        switch ($event) {
            case 'verification.confirmed':
                $this->handleConfirmed($payload);
                break;
            case 'verification.rejected':
                $this->handleRejected($payload);
                break;
        }
        
        // 4. Répondre rapidement (< 5 secondes)
        return response()->json(['success' => true], 200);
    }
    
    private function handleConfirmed($payload)
    {
        $reference = $payload['verification']['reference'];
        $order = Order::where('reference', $reference)->first();
        
        $order->update([
            'verified' => true,
            'citizen_id' => $payload['user']['citizen_id']
        ]);
        
        // Déclencher actions métier (paiement, livraison, etc.)
    }
}
```

### Retry Automatique

SagaPass fait **3 tentatives** de livraison :
- Tentative 1 : Immédiat
- Tentative 2 : Après 30 secondes
- Tentative 3 : Après 2 minutes
- Tentative 4 : Après 5 minutes

### Codes de Retour

| Code | Action SagaPass |
|------|-----------------|
| **200-299** | ✅ Succès, pas de retry |
| **4xx** | ❌ Erreur client, pas de retry |
| **5xx** | 🔁 Retry automatique (3x) |

### Documentation Complète

📖 Voir : **PARTNER_WEBHOOK_GUIDE.md** pour :
- Configuration complète
- Exemples en PHP, Node.js, Python
- Tests et debugging
- Gestion des signatures HMAC

---

## �📞 Support

En cas de questions sur la migration :

- 📧 Email : dev@sagaid.com
- 📖 Documentation complète : `PARTNER_API_RESPONSES.md`
- 🔧 Exemples de code : `PARTNER_API_INTEGRATION.md`

---

## ⏱️ Timeline

- **31 Mars 2026** : Déploiement des changements
- **15 Avril 2026** : Deadline recommandée pour migration
- **01 Mai 2026** : Anciennes réponses dépréciées

