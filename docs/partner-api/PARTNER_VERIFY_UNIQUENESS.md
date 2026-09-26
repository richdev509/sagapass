# Validation Unicité NIU et Téléphone - Documentation

## 📋 Vue d'ensemble

Le système SAGA ID impose maintenant des **contraintes d'unicité strictes** sur le NIU et le numéro de téléphone pour éviter les doublons et garantir l'intégrité des données citoyennes.

## 🔒 Règles d'unicité

### 1. NIU (Numéro d'Identification Unique)
- ✅ **Chaque NIU ne peut être associé qu'à un seul compte citoyen**
- ❌ **Deux personnes ne peuvent pas avoir le même NIU**
- 📌 **Format** : 10 chiffres exactement (`regex: /^[0-9]{10}$/`)

### 2. Numéro de téléphone
- ✅ **Chaque numéro de téléphone ne peut être associé qu'à un seul compte citoyen**
- ❌ **Deux personnes ne peuvent pas avoir le même numéro**
- 📌 **Format** : Jusqu'à 20 caractères

## 🔍 Logique de vérification

Lorsqu'un service externe envoie une demande de vérification via `/api/partner/v1/verify`, le système :

### Étape 1 : Vérification des doublons avancée

La méthode `isMatchingUser()` compare les données pour distinguer les **vrais doublons** des **mises à jour légitimes** et des **homonymes**.

#### Données comparées (dans cet ordre) :

1. **Nom + Prénom** (normalisés : minuscules, trimés)
2. **Date de naissance** (format YYYY-MM-DD)
3. **NIU** (si présent dans les deux enregistrements) ⭐ **NOUVEAU**

```
SI NIU fourni ET NIU existe déjà dans la base :
    ├─ Comparer nom + prénom + date de naissance
    ├─ SI nom ET date correspondent :
    │   ├─ Comparer NIU existant avec NIU demandé
    │   ├─ SI NIU identiques → OK (même personne, mise à jour)
    │   └─ SI NIU différents → OK (homonymes, personnes différentes)
    └─ SI nom OU date diffèrent → ERREUR 409 (duplicate_niu, vraie fraude)

SI téléphone fourni ET téléphone existe déjà dans la base :
    ├─ Comparer nom + prénom + date de naissance
    ├─ SI nom ET date correspondent :
    │   ├─ Comparer NIU existant avec NIU demandé
    │   ├─ SI NIU identiques → OK (même personne)
    │   └─ SI NIU différents → OK (homonymes)
    └─ SI nom OU date diffèrent → ERREUR 409 (duplicate_phone)
```

#### ⚠️ Cas spécial : Homonymes

Deux personnes peuvent légitimement avoir :
- Le même nom complet (ex: Jean Dupont)
- La même date de naissance (ex: 1990-01-15)

**Le NIU permet de les distinguer** :
```
Personne A : Jean Dupont, né 1990-01-15, NIU 1234567890 ✅
Personne B : Jean Dupont, né 1990-01-15, NIU 9876543210 ✅

→ Les deux comptes peuvent coexister (NIU différents)
```

### Étape 2 : Recherche du citoyen
```
Recherche par ordre de priorité :
1. NIU (si fourni)
2. Email (si fourni)
3. Téléphone (si fourni)
```

### Étape 3 : Création ou mise à jour
- Si trouvé → retourner le statut du compte
- Si non trouvé → créer un nouveau compte

---

## 📝 Exemples de scénarios

### ✅ Scénario 1 : Mise à jour légitime (même personne)

**Situation** : Jean Pierre existe déjà avec NIU 1234567890, un service envoie une mise à jour de son email.

```json
Compte existant : Jean Pierre, 1990-01-15, NIU 1234567890
Requête reçue  : Jean Pierre, 1990-01-15, NIU 1234567890, nouvel email
```

**Résultat** : ✅ **Autorisé** - Nom + date + NIU correspondent → Mise à jour OK

---

### ❌ Scénario 2 : Fraude détectée (NIU volé)

**Situation** : Marie Jeanne essaie d'utiliser le NIU de Jean Pierre.

```json
Compte existant : Jean Pierre, 1990-01-15, NIU 1234567890
Requête reçue  : Marie Jeanne, 1985-03-20, NIU 1234567890
```

**Résultat** : ❌ **HTTP 409** - `duplicate_niu` (nom/date différents, NIU identique = fraude)

---

### ✅ Scénario 3 : Homonymes légitimes

**Situation** : Deux personnes différentes ont le même nom et date de naissance.

```json
Compte existant : Michel Bernard, 1985-06-12, NIU 8888888801
Requête reçue  : Michel Bernard, 1985-06-12, NIU 8888888802
```

**Résultat** : ✅ **Autorisé** - Nom + date identiques MAIS NIU différent → Personnes différentes

---

### ✅ Scénario 4 : Nouveau compte

**Situation** : Paul Durant n'existe pas dans le système.

```json
Compte existant : Aucun
Requête reçue  : Paul Durant, 1992-07-10, NIU 9999999902
```

**Résultat** : ✅ **Autorisé** - Nouveau compte créé

---

## 🚨 Codes d'erreur

### `duplicate_niu` (409 Conflict)

**Cause** : Le NIU fourni existe déjà pour un autre utilisateur avec des données personnelles différentes.

**Réponse** :
```json
{
  "success": false,
  "error": "duplicate_niu",
  "message": "Ce NIU est déjà associé à un autre compte citoyen. Chaque NIU doit être unique.",
  "details": "Le NIU fourni existe déjà dans le système pour une personne différente."
}
```

**Actions à prendre** :
1. ✅ Vérifier que le NIU fourni est correct
2. ✅ Vérifier l'identité de la personne
3. ✅ Demander au citoyen de vérifier son NIU sur ses documents officiels
4. ✅ Si le NIU est correct mais associé à quelqu'un d'autre, contacter l'équipe SAGA ID

---

### `duplicate_phone` (409 Conflict)

**Cause** : Le numéro de téléphone fourni existe déjà pour un autre utilisateur avec des données personnelles différentes.

**Réponse** :
```json
{
  "success": false,
  "error": "duplicate_phone",
  "message": "Ce numéro de téléphone est déjà associé à un autre compte citoyen. Chaque numéro doit être unique.",
  "details": "Le numéro de téléphone fourni existe déjà dans le système pour une personne différente."
}
```

**Actions à prendre** :
1. ✅ Vérifier que le numéro de téléphone fourni est correct
2. ✅ Vérifier l'identité de la personne
3. ✅ Demander au citoyen de confirmer son numéro de téléphone
4. ✅ Si le numéro appartient à quelqu'un d'autre, demander au citoyen de fournir un autre numéro

---

## ️ Sécurité et confidentialité

### Ce que le système vérifie :
- ✅ Nom + Prénom (normalisés en minuscules, sans espaces superflus)
- ✅ Date de naissance (format YYYY-MM-DD)- ✅ NIU (pour distinguer les homonymes et détecter la fraude) ⭐ **NOUVEAU**

### Logique de matching intelligente :
1. Si **nom + date + NIU** correspondent → Même personne (mise à jour OK)
2. Si **nom + date** correspondent mais **NIU différent** → Homonymes (création OK)
3. Si **nom OU date** diffèrent avec **même NIU** → Fraude détectée (erreur 409)
### Ce que le système NE révèle PAS dans l'erreur :
- ❌ Le nom de l'utilisateur existant
- ❌ Les autres informations de l'utilisateur existant
- ❌ L'ID du compte existant

### Logs d'audit :
Tous les cas de doublons sont loggués avec :
- Le NIU ou téléphone en conflit
- L'ID de l'utilisateur existant
- Les noms (pour investigation interne uniquement)
- L'application partenaire qui a fait la requête

---

## 💻 Gestion des erreurs côté SDK

### PHP SDK

```php
use SagaId\Sdk\SagaIdClient;
use SagaId\Sdk\Exceptions\SagaIdException;

try {
    $result = $sagaId->verifyIdentity([
        'first_name' => 'Jean',
        'last_name' => 'Pierre',
        'date_of_birth' => '1990-01-15',
        'niu' => '1234567890',
        'phone' => '+50912345678',
    ]);

    if ($result['verified']) {
        // Citoyen vérifié
    }
} catch (GuzzleException $e) {
    $response = json_decode($e->getResponse()->getBody(), true);
    
    if (isset($response['error'])) {
        switch ($response['error']) {
            case 'duplicate_niu':
                // Gérer l'erreur de NIU en double
                log_error('NIU déjà utilisé : ' . $response['message']);
                // Demander au citoyen de vérifier son NIU
                break;
                
            case 'duplicate_phone':
                // Gérer l'erreur de téléphone en double
                log_error('Téléphone déjà utilisé : ' . $response['message']);
                // Demander au citoyen de fournir un autre numéro
                break;
                
            default:
                // Autre erreur
                log_error('Erreur de vérification : ' . $response['message']);
        }
    }
}
```

---

## 🔧 Tests et validation

### Test 1 : Créer deux comptes avec le même NIU
```bash
# Première requête - doit réussir
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jean",
    "last_name": "Pierre",
    "date_of_birth": "1990-01-15",
    "niu": "1234567890"
  }'

# Deuxième requête avec même NIU mais données différentes - doit échouer
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Marie",
    "last_name": "Jeanne",
    "date_of_birth": "1985-03-20",
    "niu": "1234567890"
  }'
  
# Résultat attendu : 409 Conflict avec error "duplicate_niu"
```

### Test 2 : Créer deux comptes avec le même téléphone
```bash
# Première requête - doit réussir
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Paul",
    "last_name": "Durant",
    "date_of_birth": "1992-07-10",
    "phone": "+50911111111"
  }'

# Deuxième requête avec même téléphone mais données différentes - doit échouer
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Sophie",
    "last_name": "Martin",
    "date_of_birth": "1988-11-05",
    "phone": "+50911111111"
  }'
  
# Résultat attendu : 409 Conflict avec error "duplicate_phone"
```

### Test 3 : Homonymes avec NIU différents (doit réussir)
```bash
# Première requête - créer Michel Bernard avec NIU 8888888801
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Michel",
    "last_name": "Bernard",
    "date_of_birth": "1985-06-12",
    "niu": "8888888801",
    "phone": "+50912340010"
  }'

# Deuxième requête - même nom et date MAIS NIU différent - doit réussir
curl -X POST http://sagaid.ht/api/partner/v1/verify \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Michel",
    "last_name": "Bernard",
    "date_of_birth": "1985-06-12",
    "niu": "8888888802",
    "phone": "+50912340011"
  }'
  
# Résultat attendu : HTTP 200 (création réussie)
# Raison : Les NIU sont différents donc ce sont deux personnes différentes
```

---

## 📊 Impact et bénéfices

### Avantages :
- ✅ **Intégrité des données** : Pas de doublons NIU/téléphone
- ✅ **Détection de fraude** : Tentatives d'utilisation du NIU/téléphone d'autrui
- ✅ **Gestion des homonymes** : Deux personnes avec même nom et date peuvent avoir des comptes séparés ⭐ **NOUVEAU**
- ✅ **Audit complet** : Tous les cas suspects sont loggués
- ✅ **Expérience utilisateur** : Messages d'erreur clairs et actionnables

### Considérations :
- ⚠️ Les services externes doivent gérer les erreurs 409
- ⚠️ Les utilisateurs doivent être informés en cas de conflit
- ⚠️ Support nécessaire pour résoudre les cas légitimes de conflits

---

## 📞 Support

En cas de conflit légitime (ex: NIU correctement saisi mais signalé comme doublon), contacter :
- **Email** : support@sagaid.ht
- **Documentation** : [INTEGRATION_GUIDE_sagapass.md](../saga-id-sdk/INTEGRATION_GUIDE_sagapass.md)
- **Logs d'audit** : Accessible aux administrateurs SAGA ID

---

## ✅ Résumé

| Validation | Champ | Action si doublon détecté | Code HTTP |
|------------|-------|---------------------------|-----------|
| Unicité NIU | `niu` | Comparer données → Erreur si différent | 409 |
| Unicité téléphone | `phone` | Comparer données → Erreur si différent | 409 |

**Date de mise en œuvre** : 30 mars 2026  
**Version** : 1.0.0  
**Auteur** : Équipe technique SAGA ID
