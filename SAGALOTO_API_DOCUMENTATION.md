# Documentation API Sagaloto - Intégration Telegram Bot

## Vue d'ensemble

Cette documentation décrit les formats de requêtes et réponses entre **SagaPass** (backend Laravel du bot Telegram) et **Sagaloto** (système de loterie).

**Architecture:**
```
Utilisateur Telegram ↔ SagaPass (Laravel) ↔ Sagaloto (API Loterie)
```

**Authentification:**
- Type: Bearer Token
- Header: `Authorization: Bearer {SAGALOTO_API_KEY}`
- Content-Type: `application/json`
- Accept: `application/json`

---

## 1. GET /api/branches

### Description
Récupère la liste des branches disponibles pour un utilisateur Telegram.

### Requête envoyée par SagaPass

**Méthode:** `GET`  
**URL:** `{SAGALOTO_API_URL}/api/branches`

**Headers:**
```http
Authorization: Bearer {SAGALOTO_API_KEY}
Accept: application/json
```

**Query Parameters:**
```json
{
  "telegram_username": "Sagaloto"
}
```

**Exemple complet:**
```bash
curl -X GET "https://api.sagaloto.com/api/branches?telegram_username=Sagaloto" \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Accept: application/json"
```

### Réponse attendue de Sagaloto

**Status Code:** `200 OK`

**Format JSON:**
```json
{
  "success": true,
  "data": {
    "branches": [
      {
        "id": 1,
        "name": "Port-au-Prince Centre",
        "code": "PAP-001",
        "is_active": true
      },
      {
        "id": 2,
        "name": "Cap-Haïtien",
        "code": "CAP-001",
        "is_active": true
      },
      {
        "id": 3,
        "name": "Les Cayes",
        "code": "CAY-001",
        "is_active": true
      }
    ],
    "total": 3
  },
  "message": "Branches récupérées avec succès"
}
```

**Champs obligatoires:**
- `success` (boolean): Indique si la requête a réussi
- `data.branches` (array): Liste des branches
  - `id` (integer): ID unique de la branche
  - `name` (string): Nom de la branche
  - `code` (string): Code unique de la branche
  - `is_active` (boolean): Statut actif/inactif

**Cas d'erreur:**

**Utilisateur non trouvé (404):**
```json
{
  "success": false,
  "message": "Utilisateur Telegram non trouvé",
  "error": "USER_NOT_FOUND"
}
```

**Aucune branche (200):**
```json
{
  "success": true,
  "data": {
    "branches": [],
    "total": 0
  },
  "message": "Aucune branche disponible"
}
```

---

## 2. POST /api/rapports

### Description
Récupère le rapport ou tirage pour une branche, période et loterie spécifiques.

### Requête envoyée par SagaPass

**Méthode:** `POST`  
**URL:** `{SAGALOTO_API_URL}/api/rapports`

**Headers:**
```http
Authorization: Bearer {SAGALOTO_API_KEY}
Accept: application/json
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "telegram_username": "Sagaloto",
  "branch_id": 1,
  "periode": "matin",
  "tirage": "tennessee",
  "type": "rapport"
}
```

**Champs:**
- `telegram_username` (string, required): Username Telegram sans @
- `branch_id` (integer, required): ID de la branche sélectionnée
- `periode` (string, required): Valeurs possibles: `matin`, `apres_midi`, `soir`
- `tirage` (string, required): Valeurs possibles: `tennessee`, `texas`, `georgia`, `florida`, `new_york`
- `type` (string, required): Valeurs possibles: `rapport`, `tirage`

**Exemple complet:**
```bash
curl -X POST "https://api.sagaloto.com/api/rapports" \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "telegram_username": "Sagaloto",
    "branch_id": 1,
    "periode": "matin",
    "tirage": "tennessee",
    "type": "rapport"
  }'
```

### Réponse attendue de Sagaloto

**Status Code:** `200 OK`

**Format JSON (type: rapport):**
```json
{
  "success": true,
  "data": {
    "rapport": {
      "tirage_info": {
        "name": "Tennessee",
        "periode": "matin",
        "date": "2026-02-05",
        "heure": "10:30:00"
      },
      "branch_info": {
        "id": 1,
        "name": "Port-au-Prince Centre",
        "code": "PAP-001"
      },
      "statistiques": {
        "total_ventes": 125000.00,
        "total_tickets": 2500,
        "total_gains": 85000.00,
        "benefice_net": 40000.00,
        "taux_retour": 68.0
      },
      "numeros_gagnants": {
        "borlette_3": "456",
        "borlette_4": "7892",
        "mariage": "45-67"
      },
      "tickets_gagnants": [
        {
          "numero": "456",
          "type": "Borlette 3",
          "montant_mise": 100.00,
          "montant_gain": 6000.00,
          "nombre_gagnants": 5
        },
        {
          "numero": "7892",
          "type": "Borlette 4",
          "montant_mise": 50.00,
          "montant_gain": 25000.00,
          "nombre_gagnants": 2
        }
      ]
    }
  },
  "message": "Rapport récupéré avec succès"
}
```

**Format JSON (type: tirage):**
```json
{
  "success": true,
  "data": {
    "tirage": {
      "tirage_info": {
        "name": "Tennessee",
        "periode": "matin",
        "date": "2026-02-05",
        "heure": "10:30:00",
        "statut": "termine"
      },
      "branch_info": {
        "id": 1,
        "name": "Port-au-Prince Centre",
        "code": "PAP-001"
      },
      "numeros_tires": {
        "borlette_3": "456",
        "borlette_4": "7892",
        "mariage": "45-67",
        "lotto_3": "123",
        "lotto_4": "5678"
      },
      "statistiques_rapides": {
        "total_ventes": 125000.00,
        "total_tickets": 2500
      }
    }
  },
  "message": "Tirage récupéré avec succès"
}
```

**Champs obligatoires:**

**Pour un rapport:**
- `success` (boolean)
- `data.rapport.tirage_info` (object)
  - `name` (string): Nom du tirage
  - `periode` (string): Période du tirage
  - `date` (string, format: YYYY-MM-DD): Date du tirage
  - `heure` (string, format: HH:MM:SS): Heure du tirage
- `data.rapport.branch_info` (object)
  - `id` (integer): ID de la branche
  - `name` (string): Nom de la branche
- `data.rapport.statistiques` (object)
  - `total_ventes` (float): Total des ventes en HTG
  - `total_tickets` (integer): Nombre de tickets vendus
  - `total_gains` (float): Total des gains payés
  - `benefice_net` (float): Bénéfice net
  - `taux_retour` (float): Taux de retour en %
- `data.rapport.numeros_gagnants` (object): Numéros tirés
- `data.rapport.tickets_gagnants` (array, optional): Détails des tickets gagnants

**Pour un tirage:**
- `success` (boolean)
- `data.tirage.tirage_info` (object)
- `data.tirage.branch_info` (object)
- `data.tirage.numeros_tires` (object): Numéros tirés
- `data.tirage.statistiques_rapides` (object, optional)

**Cas d'erreur:**

**Données non disponibles (404):**
```json
{
  "success": false,
  "message": "Aucun rapport disponible pour ce tirage",
  "error": "RAPPORT_NOT_FOUND"
}
```

**Paramètres invalides (422):**
```json
{
  "success": false,
  "message": "Paramètres invalides",
  "errors": {
    "branch_id": ["La branche sélectionnée n'existe pas"],
    "periode": ["La période doit être: matin, apres_midi ou soir"]
  }
}
```

---

## 3. POST /api/ventes

### Description
Récupère les statistiques de ventes pour une période donnée.

### Requête envoyée par SagaPass

**Méthode:** `POST`  
**URL:** `{SAGALOTO_API_URL}/api/ventes`

**Headers:**
```http
Authorization: Bearer {SAGALOTO_API_KEY}
Accept: application/json
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "telegram_username": "Sagaloto",
  "branch_id": 1,
  "periode": "jour"
}
```

**Champs:**
- `telegram_username` (string, required): Username Telegram sans @
- `branch_id` (integer, required): ID de la branche sélectionnée
- `periode` (string, required): Valeurs possibles: `jour`, `semaine`

**Exemple complet:**
```bash
curl -X POST "https://api.sagaloto.com/api/ventes" \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "telegram_username": "Sagaloto",
    "branch_id": 1,
    "periode": "jour"
  }'
```

### Réponse attendue de Sagaloto

**Status Code:** `200 OK`

**Format JSON:**
```json
{
  "success": true,
  "data": {
    "ventes": {
      "periode": "jour",
      "date_debut": "2026-02-05",
      "date_fin": "2026-02-05",
      "branch_info": {
        "id": 1,
        "name": "Port-au-Prince Centre",
        "code": "PAP-001"
      },
      "statistiques": {
        "total_ventes": 450000.00,
        "total_tickets": 9000,
        "total_gains": 285000.00,
        "benefice_net": 165000.00,
        "taux_retour": 63.33
      },
      "ventes_par_tirage": [
        {
          "tirage": "Tennessee",
          "periode": "matin",
          "ventes": 125000.00,
          "tickets": 2500,
          "gains": 85000.00
        },
        {
          "tirage": "Texas",
          "periode": "matin",
          "ventes": 95000.00,
          "tickets": 1900,
          "gains": 60000.00
        },
        {
          "tirage": "Georgia",
          "periode": "apres_midi",
          "ventes": 110000.00,
          "tickets": 2200,
          "gains": 70000.00
        },
        {
          "tirage": "Florida",
          "periode": "soir",
          "ventes": 120000.00,
          "tickets": 2400,
          "gains": 70000.00
        }
      ],
      "top_numeros": [
        {
          "numero": "123",
          "type": "Borlette 3",
          "frequence": 45,
          "montant_total": 4500.00
        },
        {
          "numero": "45",
          "type": "Mariage",
          "frequence": 38,
          "montant_total": 3800.00
        }
      ]
    }
  },
  "message": "Statistiques de ventes récupérées avec succès"
}
```

**Champs obligatoires:**
- `success` (boolean)
- `data.ventes.periode` (string)
- `data.ventes.date_debut` (string, format: YYYY-MM-DD)
- `data.ventes.date_fin` (string, format: YYYY-MM-DD)
- `data.ventes.branch_info` (object)
- `data.ventes.statistiques` (object)
  - `total_ventes` (float)
  - `total_tickets` (integer)
  - `total_gains` (float)
  - `benefice_net` (float)
  - `taux_retour` (float)
- `data.ventes.ventes_par_tirage` (array, optional)
- `data.ventes.top_numeros` (array, optional)

---

## Configuration SagaPass

### Fichier .env

Ajouter les variables suivantes au fichier `.env`:

```env
# Sagaloto API Configuration
SAGALOTO_API_URL=https://api.sagaloto.com
SAGALOTO_API_KEY=your_secret_api_key_here
```

### Fichier config/services.php

Ajouter la configuration Sagaloto:

```php
'sagaloto' => [
    'api_url' => env('SAGALOTO_API_URL'),
    'api_key' => env('SAGALOTO_API_KEY'),
],
```

---

## Gestion des erreurs

### Codes de statut HTTP

| Code | Signification | Action SagaPass |
|------|---------------|-----------------|
| 200 | Succès | Traiter les données |
| 400 | Requête invalide | Log erreur, afficher message générique |
| 401 | Non authentifié | Log erreur critique, vérifier API key |
| 404 | Ressource non trouvée | Afficher "Données non disponibles" |
| 422 | Validation échouée | Log erreur, afficher message d'erreur |
| 500 | Erreur serveur | Log erreur, réessayer après délai |
| 503 | Service indisponible | Afficher "Service temporairement indisponible" |

### Timeout et retry

- **Timeout de connexion:** 10 secondes
- **Timeout de lecture:** 30 secondes
- **Retry automatique:** Non (éviter les doublons)
- **Logging:** Toutes les erreurs sont loguées dans `storage/logs/laravel.log`

---

## Exemples d'utilisation dans SagaPass

### Récupérer les branches

```php
$sagalotoApi = app(SagalotoApiService::class);
$result = $sagalotoApi->getUserBranches('Sagaloto');

if ($result && $result['success']) {
    $branches = $result['data']['branches'];
    // Stocker dans la session et afficher le menu
}
```

### Récupérer un rapport

```php
$sagalotoApi = app(SagalotoApiService::class);
$result = $sagalotoApi->getRapport(
    telegramUsername: 'Sagaloto',
    branchId: 1,
    periode: 'matin',
    tirage: 'tennessee',
    type: 'rapport'
);

if ($result && $result['success']) {
    $rapport = $result['data']['rapport'];
    // Formater et envoyer à l'utilisateur
}
```

### Récupérer les ventes

```php
$sagalotoApi = app(SagalotoApiService::class);
$result = $sagalotoApi->getVentes(
    telegramUsername: 'Sagaloto',
    branchId: 1,
    periode: 'jour'
);

if ($result && $result['success']) {
    $ventes = $result['data']['ventes'];
    // Formater et envoyer à l'utilisateur
}
```

---

## Notes importantes

1. **Username Telegram:** Toujours envoyer le username **sans le @**
2. **Branch ID:** Toujours stocker le branch_id sélectionné dans le contexte de session
3. **Logging:** Toutes les requêtes API sont loguées pour le debugging
4. **Sécurité:** L'API key ne doit jamais être exposée côté client
5. **Cache:** Envisager de cacher les branches pour réduire les appels API
6. **Validation:** Toujours valider les réponses avant utilisation

---

## Support et contact

Pour toute question sur l'intégration API, contacter l'équipe Sagaloto.
