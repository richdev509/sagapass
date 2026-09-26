# Documentation des Réponses API Partner - SAGA ID

## 📋 Vue d'ensemble

Ce document liste **toutes les réponses possibles** retournées par l'endpoint Partner API :

```
POST /api/partner/v1/verify
```

### 🎯 Total : 9 Types de Réponses

| # | Type | HTTP | Status | Description |
|---|------|------|--------|-------------|
| 1️⃣ | ✅ Succès | 200 | `verified` | Citoyen vérifié |
| 2️⃣ | ✅ Succès | 200 | `pending_verification` | Compte existe - Non vérifié |
| 3️⃣ | ✅ Succès | 200 | `pending_verification` + `meta: account_created` | Nouveau compte créé |
| 🚫 | ⚠️ Rejet | **403** | `rejected` | **Compte rejeté lors de la vérification** |
| 4️⃣ | ❌ Erreur | 401 | - | Authentification invalide |
| 5️⃣ | ❌ Erreur | 422 | - | Chiffrement échoué |
| 6️⃣ | ❌ Erreur | 422 | - | Validation des données |
| 7️⃣ | ❌ Erreur | 409 | - | NIU en double |
| 8️⃣ | ❌ Erreur | 409 | - | Téléphone en double |

---

## 📧 Notifications Email Automatiques

**Important :** Le système envoie automatiquement des emails de notification dans certains cas :

| Cas | Email envoyé | Destinataire | Objectif |
|-----|-------------|--------------|----------|
| **CAS 1** : Vérifié | ❌ NON | - | Déjà vérifié, pas besoin |
| **CAS 2** : Non vérifié | ✅ OUI | Email utilisateur | Inviter à compléter la vérification |
| **CAS 3** : Compte créé | ✅ OUI | Email fourni dans payload | Informer de la création + activation |

📖 **Guide complet** : Voir [PARTNER_EMAIL_GUIDE.md](./PARTNER_EMAIL_GUIDE.md)

---

## ✅ RÉPONSES DE SUCCÈS (HTTP 200)

### 1️⃣ Citoyen Vérifié

**Conditions** : Le citoyen a un compte Sagapass avec `account_level = "verified"`

**Réponse** :
```json
{
  "success": true,
  "verified": true,
  "status": "verified",
  "citizen_id": 123,
  "message": "Identité vérifiée avec succès.",
  "data": {
    "full_name": "Jean Pierre",
    "verification_date": "2026-03-15T10:30:00Z"
  }
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `true` | Requête réussie |
| `verified` | boolean | `true` | Identité vérifiée |
| `status` | string | `"verified"` | Statut du compte |
| `citizen_id` | integer | ID utilisateur | Identifiant unique du citoyen |
| `data.full_name` | string | Nom complet | Prénom + Nom |
| `data.verification_date` | string ISO 8601 | Date/heure | Date de vérification |

**Action recommandée** : ✅ **Autoriser l'accès/transaction**

---

### 2️⃣ Compte Existe - Non Vérifié

**Conditions** : Le citoyen a un compte mais `account_level ≠ "verified"`

**Réponse** :
```json
{
  "success": true,
  "verified": false,
  "status": "pending_verification",
  "citizen_id": 456,
  "message": "Ce citoyen a un compte Sagapass, mais la vérification d'identité n'est pas encore complète.",
  "action": "Invitez le citoyen à ouvrir l'application Sagapass pour finaliser sa vérification.",
  "app_links": {
    "android": "https://play.google.com/store/apps/details?id=ht.sagapass",
    "ios": "https://apps.apple.com/app/sagapass"
  }
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `true` | Requête réussie |
| `verified` | boolean | `false` | Identité non vérifiée |
| `status` | string | `"pending_verification"` | En attente de vérification |
| `citizen_id` | integer | ID utilisateur | Identifiant du compte |
| `action` | string | Instructions | Action à effectuer |
| `app_links` | object | Liens app stores | Android + iOS |

**Action recommandée** : 
- 📱 Inviter le citoyen à compléter sa vérification
- ⏳ Réessayer ultérieurement
- 📧 Envoyer notification avec liens app stores

**📧 Email automatique** : ✅ **Un email est envoyé à l'utilisateur** avec :
- Notification de la demande de vérification
- Nom du service partenaire
- Instructions pour compléter la vérification
- Liens de téléchargement de l'application (Android + iOS)

---

### 3️⃣ Nouveau Compte Créé

**Conditions** : Aucun compte trouvé, création automatique effectuée

**Réponse** :
```json
{
  "success": true,
  "verified": false,
  "status": "pending_verification",
  "meta": "account_created",
  "citizen_id": 789,
  "message": "Aucun compte trouvé. Un compte a été créé pour ce citoyen.",
  "action": "Invitez le citoyen à télécharger l'application Sagapass pour finaliser la vérification de son identité.",
  "app_links": {
    "android": "https://play.google.com/store/apps/details?id=ht.sagapass",
    "ios": "https://apps.apple.com/app/sagapass"
  }
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `true` | Requête réussie |
| `verified` | boolean | `false` | Pas encore vérifié |
| `status` | string | `"pending_verification"` | En attente |
| `meta` | string | `"account_created"` | ⭐ Indicateur de création |
| `citizen_id` | integer | ID utilisateur | Nouvel ID assigné |
| `action` | string | Instructions | Télécharger l'app |
| `app_links` | object | Liens | Android + iOS |

**Action recommandée** :
- 📧 Envoyer email d'invitation
- 📱 SMS avec liens app stores
- ℹ️ Informer que le compte est créé mais inactif

**📧 Email automatique** : ✅ **Un email est envoyé au nouvel utilisateur** avec :
- Notification de création de compte
- Nom du service partenaire qui a initié la création
- Instructions pour télécharger l'app et activer le compte
- Liens de téléchargement de l'application (Android + iOS)

**Distinction avec cas 2️⃣** : Le champ `meta: "account_created"` indique que c'est un nouveau compte (pas un compte existant).

---

## ⚠️ CAS SPÉCIAL

### 🚫 Compte Rejeté - Vérification Refusée (HTTP 403)

**Conditions** : Le compte existe mais a été rejeté lors de la vérification d'identité (`video_status = "rejected"`)

**Réponse** :
```json
{
  "success": false,
  "verified": false,
  "error": "verification_rejected",
  "status": "rejected",
  "citizen_id": 456,
  "message": "Ce compte a été rejeté lors de la vérification d'identité.",
  "action": "L'utilisateur doit se connecter sur Sagapass avec son email pour corriger ses informations ou soumettre à nouveau ses documents de vérification.",
  "app_links": {
    "android": "https://play.google.com/store/apps/details?id=ht.sagapass",
    "ios": "https://apps.apple.com/app/sagapass"
  }
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | La vérification a échoué |
| `verified` | boolean | `false` | Compte non vérifié |
| `error` | string | `"verification_rejected"` | Code erreur spécifique |
| `status` | string | `"rejected"` | ⚠️ Statut rejeté |
| `citizen_id` | integer | ID utilisateur | Identifiant du compte |
| `action` | string | Instructions | Action corrective |
| `app_links` | object | Liens app stores | Android + iOS |

**Raisons possibles du rejet** :
- 🚫 Vidéo de vérification refusée par l'équipe de modération
- 🚫 Documents d'identité non conformes
- 🚫 Informations fournies incohérentes
- 🚫 Suspicion de fraude

**Action recommandée par le service partenaire** :
1. ❌ **NE PAS autoriser la transaction/l'accès**
2. 📧 Informer l'utilisateur que son compte Sagapass a été rejeté
3. 🔒 Demander à l'utilisateur de :
   - Se connecter sur l'application Sagapass avec son email
   - Consulter les raisons du rejet
   - Soumettre à nouveau ses documents avec les corrections nécessaires
4. ⏳ Réessayer la vérification ultérieurement (après correction)

**Note importante** : Ce statut indique un problème avec les informations d'identité de l'utilisateur, pas avec la requête API elle-même. Le compte existe mais n'est pas approuvé pour la vérification.

---

## ❌ RÉPONSES D'ERREUR

### 4️⃣ Authentification Invalide (HTTP 401)

**Conditions** : `client_id` ou `client_secret` incorrect

**Réponse** :
```json
{
  "success": false,
  "error": "invalid_client",
  "message": "Authentification invalide. Vérifiez votre client_id et client_secret."
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | Échec |
| `error` | string | `"invalid_client"` | Code erreur |
| `message` | string | Description | Message d'erreur |

**Causes possibles** :
- ❌ `client_id` incorrect
- ❌ `client_secret` incorrect
- ❌ Header `Authorization` mal formaté
- ❌ Application désactivée (`status ≠ "approved"`)

**Action** : 🔑 Vérifier les identifiants API

---

### 5️⃣ Chiffrement Invalide (HTTP 422)

**Conditions** : Impossible de décrypter le payload AES-256-CBC

**Réponse** :
```json
{
  "success": false,
  "error": "invalid_payload",
  "message": "Impossible de décrypter les données. Vérifiez le chiffrement AES-256-CBC."
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | Échec |
| `error` | string | `"invalid_payload"` | Code erreur |
| `message` | string | Description | Erreur de décryptage |

**Causes possibles** :
- ❌ `app_key` incorrecte
- ❌ Format `data` invalide (doit être `IV:ciphertext`)
- ❌ Erreur dans l'algorithme de chiffrement
- ❌ Clé dérivée incorrecte

**Action** : 🔧 Vérifier l'implémentation du chiffrement

---

### 6️⃣ Validation Échouée (HTTP 422)

**Conditions** : Les données déchiffrées ne respectent pas les règles de validation

**Réponse** :
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Données invalides.",
  "errors": {
    "niu": ["Le NINU doit faire exactement 10 chiffres."],
    "date_of_birth": ["Le champ date of birth doit être une date valide."],
    "email": ["Le champ email doit être une adresse email valide."]
  }
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | Échec |
| `error` | string | `"validation_error"` | Code erreur |
| `message` | string | Description | Erreur de validation |
| `errors` | object | Détails | Erreurs par champ |

**Règles de validation** :
- `first_name` : requis, string, max 100 caractères
- `last_name` : requis, string, max 100 caractères
- `date_of_birth` : requis, format `YYYY-MM-DD`
- `niu` : optionnel, exactement 10 chiffres
- `email` : optionnel, format email valide
- `phone` : optionnel, max 20 caractères
- `reference` : optionnel, max 255 caractères

**Action** : 📝 Corriger les données selon les messages d'erreur

---

### 7️⃣ NIU en Double (HTTP 409)

**Conditions** : Le NIU existe déjà pour une personne avec des données différentes

**Réponse** :
```json
{
  "success": false,
  "error": "duplicate_niu",
  "message": "Ce NIU est déjà associé à un autre compte citoyen. Chaque NIU doit être unique.",
  "details": "Le NIU fourni existe déjà dans le système pour une personne différente."
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | Échec |
| `error` | string | `"duplicate_niu"` | Code erreur |
| `message` | string | Description | NIU déjà utilisé |
| `details` | string | Détails | Explication |

**Logique de détection** :
1. Recherche compte avec même NIU
2. Compare nom + prénom + date de naissance
3. Compare NIU si noms/dates identiques (homonymes)
4. Si données diffèrent → Erreur 409

**Action** : ⚠️ Vérifier le NIU avec le citoyen, possibilité de fraude

---

### 8️⃣ Téléphone en Double (HTTP 409)

**Conditions** : Le téléphone existe déjà pour une personne avec des données différentes

**Réponse** :
```json
{
  "success": false,
  "error": "duplicate_phone",
  "message": "Ce numéro de téléphone est déjà associé à un autre compte citoyen. Chaque numéro doit être unique.",
  "details": "Le numéro de téléphone fourni existe déjà dans le système pour une personne différente."
}
```

**Champs clés** :
| Champ | Type | Valeur | Description |
|-------|------|--------|-------------|
| `success` | boolean | `false` | Échec |
| `error` | string | `"duplicate_phone"` | Code erreur |
| `message` | string | Description | Téléphone déjà utilisé |
| `details` | string | Détails | Explication |

**Action** : 📞 Demander un autre numéro au citoyen

---

## 📊 TABLEAU RÉCAPITULATIF

| # | HTTP | success | verified | status | error | meta | Description |
|---|------|---------|----------|--------|-------|------|-------------|
| **1️⃣** | 200 | `true` | `true` | `verified` | - | - | ✅ Vérifié |
| **2️⃣** | 200 | `true` | `false` | `pending_verification` | - | - | ⏳ En attente |
| **3️⃣** | 200 | `true` | `false` | `pending_verification` | - | `account_created` | 🆕 Créé |
| **4️⃣** | 401 | `false` | - | - | `invalid_client` | - | 🔑 Auth KO |
| **5️⃣** | 422 | `false` | - | - | `invalid_payload` | - | 🔐 Crypto KO |
| **6️⃣** | 422 | `false` | - | - | `validation_error` | - | 📝 Données KO |
| **7️⃣** | 409 | `false` | - | - | `duplicate_niu` | - | ⚠️ NIU doublon |
| **8️⃣** | 409 | `false` | - | - | `duplicate_phone` | - | ⚠️ Tel doublon |

---

## 🔍 COMMENT DISTINGUER LES CAS ?

### Logique de gestion côté client

```php
<?php

function handlePartnerResponse(array $response, int $httpCode): void
{
    // ── Erreurs HTTP ───────────────────────────────────────────────
    if ($httpCode !== 200) {
        switch ($httpCode) {
            case 401:
                handleAuthError($response);
                break;
            case 422:
                handleValidationError($response);
                break;
            case 409:
                handleConflictError($response);
                break;
            default:
                handleUnexpectedError($response, $httpCode);
        }
        return;
    }

    // ── Succès HTTP 200 ────────────────────────────────────────────
    if ($response['success'] === true) {
        
        // CAS 1 : Citoyen vérifié
        if ($response['verified'] === true && $response['status'] === 'verified') {
            handleVerifiedCitizen($response);
            return;
        }

        // CAS 3 : Nouveau compte créé
        if (isset($response['meta']) && $response['meta'] === 'account_created') {
            handleAccountCreated($response);
            return;
        }

        // CAS 2 : Compte existe mais non vérifié
        if ($response['verified'] === false && $response['status'] === 'pending_verification') {
            handlePendingVerification($response);
            return;
        }
    }

    // Cas non géré
    handleUnexpectedResponse($response);
}

function handleVerifiedCitizen(array $response): void
{
    echo "✅ IDENTITÉ VÉRIFIÉE\n";
    echo "   Citoyen ID: {$response['citizen_id']}\n";
    echo "   Nom: {$response['data']['full_name']}\n";
    echo "   Vérifié le: {$response['data']['verification_date']}\n";
    // → Autoriser l'accès
}

function handleAccountCreated(array $response): void
{
    echo "🆕 COMPTE CRÉÉ\n";
    echo "   Citoyen ID: {$response['citizen_id']}\n";
    echo "   Action: {$response['action']}\n";
    echo "   Android: {$response['app_links']['android']}\n";
    echo "   iOS: {$response['app_links']['ios']}\n";
    // → Envoyer invitation (email/SMS)
}

function handlePendingVerification(array $response): void
{
    echo "⏳ VÉRIFICATION EN ATTENTE\n";
    echo "   Citoyen ID: {$response['citizen_id']}\n";
    echo "   Message: {$response['message']}\n";
    // → Inviter à compléter la vérification
}

function handleAuthError(array $response): void
{
    echo "❌ ERREUR D'AUTHENTIFICATION\n";
    echo "   Code: {$response['error']}\n";
    echo "   Message: {$response['message']}\n";
    // → Vérifier client_id/client_secret
}

function handleValidationError(array $response): void
{
    echo "❌ ERREUR DE VALIDATION\n";
    echo "   Code: {$response['error']}\n";
    
    if ($response['error'] === 'invalid_payload') {
        echo "   Problème de chiffrement\n";
        // → Vérifier l'implémentation AES-256-CBC
    } else {
        echo "   Erreurs:\n";
        foreach ($response['errors'] as $field => $messages) {
            echo "   - {$field}: " . implode(', ', $messages) . "\n";
        }
        // → Corriger les données
    }
}

function handleConflictError(array $response): void
{
    echo "⚠️ CONFLIT DÉTECTÉ\n";
    echo "   Code: {$response['error']}\n";
    echo "   Message: {$response['message']}\n";
    echo "   Détails: {$response['details']}\n";
    
    if ($response['error'] === 'duplicate_niu') {
        // → Vérifier le NIU avec le citoyen
    } elseif ($response['error'] === 'duplicate_phone') {
        // → Demander un autre numéro
    }
}
```

---

## 📌 NOTES IMPORTANTES

### Gestion des liens app stores

Les liens `app_links` sont **dynamiques** et stockés dans la table `system_settings` :

```sql
SELECT * FROM system_settings WHERE key IN ('app_store_android', 'app_store_ios');
```

Pour mettre à jour :
```php
use App\Models\SystemSetting;

SystemSetting::set('app_store_android', 'https://play.google.com/...');
SystemSetting::set('app_store_ios', 'https://apps.apple.com/...');
```

### Cache des liens

Les liens sont mis en cache pendant **1 heure** par défaut. Pour vider le cache :

```bash
php artisan cache:forget setting_app_store_android
php artisan cache:forget setting_app_store_ios
```

Ou vider tout le cache :
```bash
php artisan cache:clear
```

---

## 🧪 TESTS RECOMMANDÉS

### Test 1 : Citoyen vérifié
```bash
# Créer un compte vérifié puis tester
```

### Test 2 : Compte non vérifié
```bash
# Créer un compte avec account_level = 'basic' puis tester
```

### Test 3 : Nouveau compte
```bash
# Tester avec des données inexistantes
```

### Test 4 : Erreur d'authentification
```bash
# Utiliser un client_id invalide
```

### Test 5 : Doublon NIU
```bash
# Tenter de créer un compte avec un NIU existant mais nom différent
```

---

## 📞 SUPPORT

- **Documentation complète** : [PARTNER_API_GUIDE.md](PARTNER_API_GUIDE.md)
- **Validation unicité** : [PARTNER_VERIFY_UNIQUENESS.md](PARTNER_VERIFY_UNIQUENESS.md)
- **Email** : support@sagaid.ht

---

**Version** : 1.0.0  
**Date** : 30 mars 2026  
**Auteur** : Équipe technique SAGA ID
