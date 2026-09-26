# 🔐 Système de Vérification Supplémentaire Partner API

## Vue d'ensemble

Lorsqu'un partenaire (exemple: Kayapa) tente de vérifier l'identité d'un citoyen via l'API Partner, et qu'il y a une **correspondance partielle** dans le nom (ex: "Sebastian" vs "Sebastien") mais que le **NIU est identique**, le système crée un **challenge de vérification** qui apparaît dans l'application mobile SagaPass du citoyen.

---

## 📱 Flux Utilisateur

### 1. Scénario de Déclenchement

**Kayapa envoie:**
```json
{
  "niu": "1229182158",
  "first_name": "Dary Sebastien",
  "last_name": "Petion",
  "date_of_birth": "2007-03-16"
}
```

**Base de données SagaPass:**
```
NIU: 1229182158
Nom: Dary Sebastian Petion
```

➡️ **NIU identique** ✅ + **Nom similaire mais pas exact** (80%+ de similarité) → **Challenge créé**

---

### 2. Réponse API à Kayapa

Le partenaire reçoit une réponse HTTP 409 avec:

```json
{
  "success": false,
  "verified": false,
  "error": "name_verification_required",
  "status": "pending_user_confirmation",
  "message": "Une différence a été détectée dans le nom. L'utilisateur doit confirmer son identité dans son application SagaPass.",
  "data": {
    "challenge_id": 42,
    "sagapass_name": "Dary Sebastian Petion",
    "partner_name": "Dary Sebastien Petion",
    "expires_at": "2026-04-02T17:48:15Z",
    "action_required": "L'utilisateur recevra une notification dans son application SagaPass..."
  }
}
```

---

### 3. Notification In-App SagaPass

L'utilisateur ouvre son application SagaPass et voit une **alerte/notification** dans son écran d'accueil:

```
⚠️ VÉRIFICATION D'IDENTITÉ REQUISE

Le service "Kayapa" tente de vérifier votre identité mais a détecté
une différence dans votre nom:

• Votre nom officiel SagaPass: Dary Sebastian Petion
• Nom que vous avez fourni à Kayapa: Dary Sebastien Petion

⚠️ IMPORTANT: Votre nom officiel SagaPass ne peut pas être modifié ici.

Que souhaitez-vous faire ?

[✅ C'est bien moi - Je vais corriger mon nom chez Kayapa]
[❌ Ce n'est pas moi - Tentative frauduleuse]
```

**Note importante**: Le nom dans SagaPass est le nom **officiel** et ne changera jamais automatiquement. Si l'utilisateur confirme son identité, c'est **lui qui doit corriger son nom côté Kayapa** pour qu'il corresponde à son nom SagaPass.

---

## 🔌 API Endpoints Mobile

### 1. Lister les Challenges en Attente

```http
GET /api/mobile/verification-challenges
Authorization: Bearer {user_token}
```

**Réponse:**
```json
{
  "success": true,
  "count": 2,
  "challenges": [
    {
      "id": 42,
      "partner_name": "Kayapa",
      "name_discrepancy": {
        "sagapass_name": "Dary Sebastian Petion",
        "partner_name": "Dary Sebastien Petion",
        "similarity": 92.31
      },
      "created_at": "2026-03-31T17:48:15Z",
      "expires_at": "2026-04-02T17:48:15Z",
      "time_left_hours": 45
    }
  ]
}
```

---

### 2. Détails d'un Challenge

```http
GET /api/mobile/verification-challenges/{id}
Authorization: Bearer {user_token}
```

---

### 3. Répondre au Challenge

```http
POST /api/mobile/verification-challenges/{id}/respond
Authorization: Bearer {user_token}
Content-Type: application/json

{
  "action": "confirm",  // "confirm" | "reject"
  "message": "C'est bien moi, je vais corriger mon nom chez Kayapa"
}
```

**Actions possibles:**

| Action | Description | Effet |
|--------|-------------|-------|
| `confirm` | "C'est bien moi" | ✅ **L'utilisateur DOIT corriger son nom chez Kayapa**<br>✅ SagaPass garde le nom officiel (JAMAIS modifié automatiquement)<br>✅ Prochaine vérification avec nom corrigé réussira |
| `reject` | "Ce n'est pas moi" | ❌ Signale une tentative frauduleuse<br>❌ La vérification Kayapa échoue définitivement<br>⚠️ Alerte de sécurité enregistrée |

**Réponse:**
```json
{
  "success": true,
  "message": "Vous avez confirmé votre identité. Veuillez corriger votre nom sur le service partenaire (Kayapa) pour qu'il corresponde à votre nom SagaPass.",
  "data": {
    "action_required": "Corrigez votre nom chez Kayapa: Dary Sebastian Petion",
    "sagapass_official_name": "Dary Sebastian Petion"
  },
  "challenge": {
    "id": 42,
    "status": "confirmed",
    "responded_at": "2026-03-31T18:12:45Z"
  }
}
```

---

## 🎯 Logique de Détection

### Algorithme de Correspondance

Le système utilise `similar_text()` pour calculer la similarité entre les noms:

```php
// Exemple 1: Correspondance exacte (100%)
"Dary Sebastian" vs "Dary Sebastian" → 100% → Pas de challenge

// Exemple 2: Correspondance partielle (>80%)
"Dary Sebastian Petion" vs "Dary Sebastien Petion" → 92% → Challenge créé

// Exemple 3: Aucune correspondance (<80%)
"Jean Dupont" vs "Pierre Martin" → 15% → Erreur "duplicate_niu"
```

### Critères de Challenge

Un challenge est créé SI ET SEULEMENT SI:
1. ✅ **NIU identique** (1229182158 = 1229182158)
2. ✅ **Similarité de nom entre 80% et 99%** (pas exactement pareil, mais proche)
3. ✅ **Pas de challenge déjà en attente** pour ce couple user/NIU

---

## 🗄️ Base de Données

### Table: `partner_verification_challenges`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint | ID unique |
| `user_id` | bigint | Citoyen SagaPass concerné |
| `partner_verification_id` | bigint | Lien vers `partner_verifications` |
| `partner_name` | string | Nom du partenaire (ex: "Kayapa") |
| `name_discrepancy` | json | `{"sagapass_name": "...", "partner_name": "...", "similarity": 92}` |
| `verification_data` | json | Données complètes envoyées par le partenaire |
| `status` | enum | `pending`, `confirmed`, `rejected`, `expired` |
| `user_response` | text | Réponse textuelle de l'utilisateur |
| `responded_at` | timestamp | Date de réponse |
| `expires_at` | timestamp | Expiration (48h après création) |

---

## 📊 Cas d'Usage Réels

### Cas 1: Typo Simple (Sebastian → Sebastien)
```
NIU: 1229182158 (identique) ✅
Nom SagaPass (OFFICIEL): "Dary Sebastian Petion"
Nom Kayapa (INCORRECT): "Dary Sebastien Petion"
Similarité: 92%

→ Challenge créé
→ Notification envoyée à l'utilisateur
→ Utilisateur confirme "c'est bien moi"
→ Message: "Corrigez votre nom chez Kayapa en: Dary Sebastian Petion"
→ Utilisateur corrige son nom chez Kayapa
→ Prochaine tentative Kayapa: succès automatique
```

**IMPORTANT**: Le nom SagaPass n'est JAMAIS modifié. C'est le nom officiel et fait foi.

### Cas 2: Nom de Famille Différent (Mariage, Erreur d'État Civil)
```
NIU: 7890123456 (identique) ✅
Nom SagaPass: "Marie Dubois"
Nom Kayapa: "Marie Jean-Baptiste"
Similarité: 45%

→ Erreur "duplicate_niu" (similarité < 80%)
→ Pas de challenge (trop de différence)
→ L'utilisateur doit contacter le support
→ Mise à jour possible via admin panel
```

### Cas 3: Tentative Frauduleuse
```
NIU: 5555555555 (identique) ✅
Nom SagaPass: "Jean Dupont"
Nom Kayapa: "Louis Martin"
Similarité: 10%

→ Erreur "duplicate_niu"
→ Log de sécurité créé
→ Alerte automatique au service anti-fraude
```

---

## 🔔 Notifications Push (À Implémenter)

Pour envoyer des notifications push à l'app mobile SagaPass:

### Firebase Cloud Messaging (FCM)

```php
// Dans PartnerVerifyController::createVerificationChallenge()

// Récupérer le token FCM de l'utilisateur
$fcmToken = $user->fcm_token;

if ($fcmToken) {
    $notification = [
        'title' => '⚠️ Vérification d'identité requise',
        'body' => "Le service {$app->name} a détecté une différence dans votre nom. Veuillez confirmer votre identité.",
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'data' => [
            'type' => 'verification_challenge',
            'challenge_id' => $challenge->id,
            'partner_name' => $app->name,
        ],
    ];
    
    // Envoyer via FCM
    \App\Services\FirebaseService::send($fcmToken, $notification);
}
```

---

## 🧪 Tests

### Script de Test

```php
<?php
// test-verification-challenge.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;

// 1. Créer un utilisateur test
$user = User::create([
    'first_name' => 'Dary',
    'last_name' => 'Sebastian Petion',
    'niu' => '1229182158',
    'email' => 'dary.test@example.ht',
    'phone' => '+50947974323',
    'date_of_birth' => '2007-03-16',
    'account_level' => 'basic',
]);

// 2. Simuler appel Partner API avec variation nom
$response = Http::withBasicAuth('client_id', 'client_secret')
    ->post('http://localhost:8080/api/partner/v1/verify', [
        'encrypted' => false,
        'first_name' => 'Dary Sebastien',
        'last_name' => 'Petion',
        'niu' => '1229182158',
        'date_of_birth' => '2007-03-16',
    ]);

// 3. Vérifier la réponse
assert($response->status() === 409);
assert($response->json('error') === 'name_verification_required');
echo "✅ Challenge créé avec succès\n";
echo "Challenge ID: " . $response->json('data.challenge_id') . "\n";
```

---

## 📋 Checklist d'Implémentation

### Backend ✅
- [x] Migration `partner_verification_challenges`
- [x] Model `PartnerVerificationChallenge`
- [x] Logique de détection dans `PartnerVerifyController`
- [x] Endpoints API mobile (`VerificationChallengeController`)
- [x] Routes API protégées

### Frontend Mobile (À Faire)
- [ ] Écran "Notifications/Alertes"
- [ ] Composant `VerificationChallengeCard`
- [ ] Boutons d'action (Confirmer/Rejeter/Corriger)
- [ ] Service API `verification_challenge_service.dart`
- [ ] Notification push handler

### Production
- [ ] Configurer Firebase Cloud Messaging
- [ ] Tester avec vrais utilisateurs
- [ ] Monitoring des challenges expirés
- [ ] Cleanup automatique (supprimer challenges > 30 jours)

---

## 🚀 Prochaines Étapes

1. **Intégration FCM**: Envoyer notification push quand challenge créé
2. **UI Mobile**: Créer l'écran de notification dans l'app Flutter SagaPass
3. **Webhook Kayapa** (Optionnel): Notifier Kayapa quand utilisateur répond
4. **Analytics**: Tracker taux de confirmation/rejet
5. **Automatisation**: Auto-expirer challenges après 48h via cron job

---

## 📞 Support

Pour questions sur ce système:
- Développeur Backend: Architecture API
- Développeur Mobile: Intégration notification
- Security Team: Validation anti-fraude

**Date de création**: Mars 2026 
**Version**: 1.0
