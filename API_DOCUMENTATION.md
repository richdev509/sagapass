# 📘 Documentation API SAGAPASS

**Version** : 1.0  
**Dernière mise à jour** : 20 novembre 2025  
**Base URL** : `https://api.sagapass.com` (Production) | `http://127.0.0.1:8000` (Développement)

---

## 📋 Table des matières

1. [Introduction](#introduction)
2. [Authentification OAuth 2.0](#authentification-oauth-20)
3. [Scopes (Permissions)](#scopes-permissions)
4. [Endpoints API](#endpoints-api)
5. [Codes d'erreur](#codes-derreur)
6. [Exemples d'intégration](#exemples-dintégration)
7. [Widget d'Intégration - Vérification d'Identité](#-widget-dintégration---vérification-didentité)
8. [Limites et quotas](#limites-et-quotas)
9. [Changelog](#changelog)

---

## 🎯 Introduction

SAGAPASS est un service d'identité numérique sécurisé qui permet aux citoyens de s'authentifier et de partager leurs informations vérifiées avec des applications tierces.

### Niveaux de compte

SAGAPASS utilise un système de vérification progressive en 3 niveaux :

| Niveau | État | Description |
|--------|------|-------------|
| **Pending** | `account_level = "pending"` | Inscription initiale, email vérifié |
| **Basic** | `account_level = "basic"` | Vidéo de vérification faciale approuvée |
| **Verified** | `account_level = "verified"` | Document d'identité (CNI/Passeport) vérifié |

### Cas d'usage

- **Authentification unique (SSO)** : Permettre aux utilisateurs de se connecter avec leur compte SAGAPASS
- **Vérification d'identité** : Confirmer l'identité d'un utilisateur avec des documents officiels vérifiés
- **Partage de données** : Accéder aux informations de profil avec le consentement de l'utilisateur
- **KYC (Know Your Customer)** : Récupérer des informations de documents vérifiés (compte Verified uniquement)

### Technologies

- **Protocole** : OAuth 2.0 Authorization Code Flow avec PKCE
- **Sécurité** : HTTPS obligatoire, tokens JWT, PKCE pour les applications publiques
- **Format** : JSON pour toutes les requêtes et réponses

---

## 🔐 Authentification OAuth 2.0

SAGAPASS utilise le protocole OAuth 2.0 avec le flux Authorization Code + PKCE pour garantir la sécurité maximale.

### Étape 1 : Créer une application

1. **Inscription développeur** : `GET /developers/register`
2. **Créer une application** : `GET /developers/applications/create`
3. **Attendre l'approbation** : L'administrateur doit approuver votre application

Vous recevrez :
- `client_id` : Identifiant public de votre application
- `client_secret` : Clé secrète (à garder confidentielle)

### Étape 2 : Générer le PKCE Challenge

```javascript
// Générer un code_verifier aléatoire (43-128 caractères)
const codeVerifier = generateRandomString(128);

// Créer le code_challenge (SHA256 du verifier)
const codeChallenge = base64URLEncode(sha256(codeVerifier));
```

### Étape 3 : Rediriger vers la page d'autorisation

```
GET /oauth/authorize?
  client_id={CLIENT_ID}&
  redirect_uri={REDIRECT_URI}&
  response_type=code&
  scope={SCOPES}&
  state={STATE}&
  code_challenge={CODE_CHALLENGE}&
  code_challenge_method=S256
```

**Paramètres** :
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `client_id` | string | ✅ | Identifiant de votre application |
| `redirect_uri` | string | ✅ | URL de redirection (doit être enregistrée) |
| `response_type` | string | ✅ | Doit être `code` |
| `scope` | string | ❌ | Scopes demandés (séparés par des espaces). Défaut: `profile` |
| `state` | string | ✅ | Valeur aléatoire pour prévenir les attaques CSRF |
| `code_challenge` | string | ✅ | Challenge PKCE (recommandé) |
| `code_challenge_method` | string | ✅ | Méthode : `S256` (SHA256) ou `plain` |

**Exemple** :
```
https://sagapass.com/oauth/authorize?
  client_id=abc123def456&
  redirect_uri=https://monapp.com/callback&
  response_type=code&
  scope=profile email phone&
  state=xyz789random&
  code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&
  code_challenge_method=S256
```

### Étape 4 : L'utilisateur accorde les permissions

L'utilisateur voit un écran de consentement montrant :
- Le nom et logo de votre application
- Les permissions demandées
- Les informations qui seront partagées

Il peut **Autoriser** ou **Refuser**.

### Étape 5 : Récupérer le code d'autorisation

Si l'utilisateur approuve, il est redirigé vers :
```
https://monapp.com/callback?code={AUTHORIZATION_CODE}&state={STATE}
```

**Important** : Vérifiez que le `state` correspond à celui envoyé.

### Étape 6 : Échanger le code contre un access token

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code={AUTHORIZATION_CODE}&
redirect_uri={REDIRECT_URI}&
client_id={CLIENT_ID}&
client_secret={CLIENT_SECRET}&
code_verifier={CODE_VERIFIER}
```

**Paramètres** :
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `grant_type` | string | ✅ | Doit être `authorization_code` |
| `code` | string | ✅ | Code reçu à l'étape 5 |
| `redirect_uri` | string | ✅ | Même URI qu'à l'étape 3 |
| `client_id` | string | ✅ | Identifiant de votre application |
| `client_secret` | string | ✅ | Clé secrète de votre application |
| `code_verifier` | string | ✅ | Verifier PKCE original |

**Réponse** :
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

### Étape 7 : Utiliser l'access token

Incluez le token dans l'en-tête `Authorization` de vos requêtes API :

```http
GET /api/v1/user
Authorization: Bearer {ACCESS_TOKEN}
Accept: application/json
```

---

## 🎫 Scopes (Permissions)

Les scopes définissent les données auxquelles votre application peut accéder.

| Scope | Description | Données retournées |
|-------|-------------|-------------------|
| `profile` | Informations de profil de base | `first_name`, `last_name`, `account_level`, `verification_level`, `video_status`, `is_verified` |
| `email` | Adresse email | `email`, `email_verified_at` |
| `phone` | Numéro de téléphone | `phone` |
| `address` | Adresse postale | `address` |
| `birthdate` | Date de naissance | `date_of_birth` |
| `photo` | Photo de profil | `profile_photo_path`, `profile_photo_url` |
| `documents` | Documents d'identité vérifiés (Verified uniquement) | `document_type`, `niu`, `card_number` (masqués), dates, statut de vérification |

**Notes importantes** :
- Demandez uniquement les scopes dont vous avez besoin
- L'utilisateur peut refuser certains scopes
- Votre application doit être approuvée pour certains scopes sensibles (ex: `documents`)

**Exemple de demande de scopes multiples** :
```
scope=profile email phone
```

---

## 🌐 Endpoints API

### 1. Obtenir le profil utilisateur

Récupère les informations de profil de l'utilisateur authentifié selon les scopes accordés.

**Endpoint** : `GET /api/v1/user`

**Authentification** : Bearer Token (OAuth 2.0)

**Scopes requis** : Au moins un parmi `profile`, `email`, `phone`, `address`, `birthdate`, `photo`

**Exemple de requête** :
```http
GET /api/v1/user HTTP/1.1
Host: api.sagapass.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
```

**Réponse (200 OK)** :
```json
{
  "first_name": "Jean",
  "last_name": "Dupont",
  "account_level": "verified",
  "verification_level": "document",
  "verification_status": "verified",
  "video_status": "approved",
  "video_verified_at": "2025-10-18",
  "verified_at": "2025-10-20",
  "is_verified": true,
  "email": "jean.dupont@example.com",
  "email_verified_at": "2025-10-15",
  "phone": "+33612345678",
  "address": "123 Rue de la Paix, 75001 Paris",
  "date_of_birth": "1990-05-15",
  "profile_photo_path": "profile-photos/abc123.jpg",
  "profile_photo_url": "https://sagapass.com/storage/profile-photos/abc123.jpg"
}
```

**Champs retournés selon les scopes** :

| Champ | Scope requis | Type | Description |
|-------|--------------|------|-------------|
| `first_name` | `profile` | string | Prénom |
| `last_name` | `profile` | string | Nom de famille |
| `account_level` | `profile` | string | Niveau : `pending`, `basic`, `verified` |
| `verification_level` | `profile` | string | Progression : `none`, `email`, `video`, `document` |
| `verification_status` | `profile` | string | Statut général : `pending`, `verified`, `rejected` |
| `video_status` | `profile` | string | Statut vidéo : `none`, `pending`, `approved`, `rejected` |
| `video_verified_at` | `profile` | date | Date d'approbation de la vidéo (null si non approuvée) |
| `verified_at` | `profile` | date | Date de passage en compte Verified (null si non vérifié) |
| `is_verified` | `profile` | boolean | `true` si `account_level === "verified"` |
| `email` | `email` | string | Adresse email |
| `email_verified_at` | `email` | date | Date de vérification email |
| `phone` | `phone` | string | Numéro de téléphone |
| `address` | `address` | string | Adresse postale complète |
| `date_of_birth` | `birthdate` | date | Date de naissance (format: YYYY-MM-DD) |
| `profile_photo_path` | `photo` | string | Chemin relatif de la photo de profil |
| `profile_photo_url` | `photo` | string | URL complète de la photo de profil |

**Erreurs** :
- `401 Unauthorized` : Token manquant ou invalide
- `403 Forbidden` : Aucun scope accordé

---

### 2. Obtenir les documents vérifiés

Récupère les informations sur les documents d'identité vérifiés de l'utilisateur. La réponse varie selon le niveau de compte.

**Endpoint** : `GET /api/v1/user/documents`

**Authentification** : Bearer Token (OAuth 2.0)

**Scopes requis** : `documents`

**Exemple de requête** :
```http
GET /api/v1/user/documents HTTP/1.1
Host: api.sagapass.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
```

#### Réponse selon le niveau de compte

**Réponse (200 OK) - Compte Verified** :
```json
{
  "account": {
    "level": "verified",
    "verification_level": "document",
    "can_access_documents": true
  },
  "document": {
    "verified": true,
    "type": "cni",
    "numbers": {
      "niu": "****567890",
      "card_number": "****3DEF"
    },
    "dates": {
      "issue": "2020-01-15",
      "expiry": "2030-01-15",
      "verified_at": "2025-10-20T14:30:00+00:00"
    }
  }
}
```

**Réponse (200 OK) - Compte Basic** :
```json
{
  "account": {
    "level": "basic",
    "verification_level": "video",
    "can_access_documents": false
  },
  "document": null,
  "upgrade_required": {
    "next_level": "verified",
    "requirements": [
      "Soumettre et faire vérifier un document d'identité (CNI ou Passeport)"
    ],
    "progress": {
      "video_submitted": true,
      "video_approved": true,
      "document_verified": false
    }
  }
}
```

**Réponse (200 OK) - Compte Pending** :
```json
{
  "account": {
    "level": "pending",
    "verification_level": "email",
    "can_access_documents": false
  },
  "document": null,
  "upgrade_required": {
    "next_level": "basic",
    "requirements": [
      "Soumettre une vidéo de vérification faciale"
    ],
    "progress": {
      "video_submitted": false,
      "video_approved": false,
      "document_verified": false
    }
  }
}
```

**Réponse (200 OK) - Compte Verified sans document** :
```json
{
  "account": {
    "level": "verified",
    "verification_level": "document",
    "can_access_documents": true
  },
  "document": null,
  "message": "Aucun document vérifié trouvé."
}
```

#### Structure de la réponse

**Section `account` (toujours présente)** :

| Champ | Type | Description |
|-------|------|-------------|
| `level` | string | Niveau du compte : `pending`, `basic`, `verified` |
| `verification_level` | string | Progression : `email`, `video`, `document` |
| `can_access_documents` | boolean | `true` uniquement si `level === "verified"` |

**Section `document` (null si pas de document ou compte non-Verified)** :

| Champ | Type | Description |
|-------|------|-------------|
| `verified` | boolean | Toujours `true` si présent |
| `type` | string | Type : `cni` (Carte Nationale) ou `passport` |
| `numbers.niu` | string | NIU masqué (10 chiffres, 4 derniers visibles) |
| `numbers.card_number` | string\|null | Numéro de carte masqué (9 caractères, 4 derniers visibles). Uniquement pour CNI |
| `dates.issue` | date | Date de délivrance du document (ISO 8601) |
| `dates.expiry` | date | Date d'expiration du document (ISO 8601) |
| `dates.verified_at` | datetime | Date et heure de vérification (ISO 8601) |

**Section `upgrade_required` (présente si compte non-Verified)** :

| Champ | Type | Description |
|-------|------|-------------|
| `next_level` | string | Prochain niveau à atteindre |
| `requirements` | array | Liste des exigences pour passer au niveau supérieur |
| `progress.video_submitted` | boolean | Vidéo soumise ? |
| `progress.video_approved` | boolean | Vidéo approuvée ? |
| `progress.document_verified` | boolean | Document vérifié ? |

#### Notes importantes

**Sécurité** :
- ✅ Les numéros de documents sont **toujours masqués** (4 derniers caractères visibles)
- ✅ Seul le **dernier document vérifié** est retourné
- ✅ Les **photos des documents** ne sont jamais accessibles via l'API
- ✅ Accès restreint aux comptes **Verified uniquement**

**Champ `card_number`** :
- Présent uniquement pour les **Cartes Nationales d'Identité (CNI)**
- `null` pour les passeports
- Format : 9 caractères alphanumériques (ex: `ABC123DEF`)
- Masqué : `****3DEF`

**Champ `niu` (NIU = Numéro d'Identification Unique)** :
- Présent pour **tous les documents** (CNI et passeports)
- Format : 10 chiffres
- Masqué : `****567890`

**Erreurs** :
- `401 Unauthorized` : Token manquant ou invalide
- `403 Forbidden` : Scope `documents` non accordé

---

### 3. Révoquer un token

Permet à l'utilisateur de révoquer l'accès d'une application.

**Endpoint** : `POST /oauth/revoke`

**Authentification** : Bearer Token (OAuth 2.0)

**Paramètres** :
```http
POST /oauth/revoke HTTP/1.1
Host: api.sagapass.com
Content-Type: application/x-www-form-urlencoded

token={ACCESS_TOKEN}
```

**Réponse (200 OK)** :
```json
{
  "success": true,
  "message": "Token révoqué avec succès."
}
```

**Erreurs** :
- `400 Bad Request` : Token manquant
- `401 Unauthorized` : Token invalide

---

### 4. Vérifier un token (Introspection)

Permet de vérifier la validité d'un token.

**Endpoint** : `POST /oauth/introspect`

**Authentification** : Client credentials (Basic Auth)

**Paramètres** :
```http
POST /oauth/introspect HTTP/1.1
Host: api.sagapass.com
Authorization: Basic {BASE64(client_id:client_secret)}
Content-Type: application/x-www-form-urlencoded

token={ACCESS_TOKEN}
```

**Réponse (200 OK) - Token valide** :
```json
{
  "active": true,
  "scope": "profile email",
  "client_id": "abc123def456",
  "user_id": 42,
  "exp": 1700000000
}
```

**Réponse (200 OK) - Token invalide** :
```json
{
  "active": false
}
```

---

## ⚠️ Codes d'erreur

### Erreurs OAuth

| Code | Erreur | Description |
|------|--------|-------------|
| 400 | `invalid_request` | Paramètres manquants ou invalides |
| 401 | `invalid_client` | Client ID ou secret invalide |
| 401 | `unauthorized_client` | Application non approuvée |
| 400 | `invalid_grant` | Code d'autorisation invalide ou expiré |
| 400 | `invalid_scope` | Scope demandé non autorisé |
| 400 | `unsupported_grant_type` | Type de grant non supporté |
| 400 | `redirect_uri_mismatch` | URI de redirection non autorisée |

### Erreurs API

| Code HTTP | Type | Description |
|-----------|------|-------------|
| 401 | `Unauthorized` | Token manquant, invalide ou expiré |
| 403 | `Forbidden` | Scopes insuffisants |
| 404 | `Not Found` | Ressource inexistante |
| 429 | `Too Many Requests` | Limite de taux dépassée |
| 500 | `Internal Server Error` | Erreur serveur |

**Format des erreurs** :
```json
{
  "error": "invalid_scope",
  "error_description": "Le scope 'documents' n'est pas autorisé pour cette application."
}
```

---

## 💡 Exemples d'intégration

### Exemple en JavaScript (Node.js)

```javascript
const express = require('express');
const axios = require('axios');
const crypto = require('crypto');

const app = express();
const CLIENT_ID = 'votre_client_id';
const CLIENT_SECRET = 'votre_client_secret';
const REDIRECT_URI = 'http://localhost:3000/callback';
const SAGA_ID_URL = 'http://127.0.0.1:8000';

// Générer PKCE
function generatePKCE() {
  const verifier = crypto.randomBytes(32).toString('base64url');
  const challenge = crypto
    .createHash('sha256')
    .update(verifier)
    .digest('base64url');
  
  return { verifier, challenge };
}

// Étape 1: Rediriger vers SAGAPASS
app.get('/login', (req, res) => {
  const pkce = generatePKCE();
  const state = crypto.randomBytes(16).toString('hex');
  
  // Sauvegarder en session
  req.session.pkce_verifier = pkce.verifier;
  req.session.state = state;
  
  const authUrl = `${SAGA_ID_URL}/oauth/authorize?` +
    `client_id=${CLIENT_ID}&` +
    `redirect_uri=${encodeURIComponent(REDIRECT_URI)}&` +
    `response_type=code&` +
    `scope=profile email&` +
    `state=${state}&` +
    `code_challenge=${pkce.challenge}&` +
    `code_challenge_method=S256`;
  
  res.redirect(authUrl);
});

// Étape 2: Callback - Échanger le code contre un token
app.get('/callback', async (req, res) => {
  const { code, state } = req.query;
  
  // Vérifier le state
  if (state !== req.session.state) {
    return res.status(400).send('Invalid state');
  }
  
  try {
    // Échanger le code contre un access token
    const response = await axios.post(`${SAGA_ID_URL}/oauth/token`, 
      new URLSearchParams({
        grant_type: 'authorization_code',
        code: code,
        redirect_uri: REDIRECT_URI,
        client_id: CLIENT_ID,
        client_secret: CLIENT_SECRET,
        code_verifier: req.session.pkce_verifier
      }),
      {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      }
    );
    
    const { access_token } = response.data;
    
    // Récupérer le profil utilisateur
    const userProfile = await axios.get(`${SAGA_ID_URL}/api/v1/user`, {
      headers: { 
        'Authorization': `Bearer ${access_token}`,
        'Accept': 'application/json'
      }
    });
    
    // Sauvegarder en session
    req.session.access_token = access_token;
    req.session.user = userProfile.data;
    
    res.json({
      message: 'Authentification réussie',
      user: userProfile.data
    });
    
  } catch (error) {
    console.error('Erreur OAuth:', error.response?.data);
    res.status(500).send('Erreur d\'authentification');
  }
});

// Étape 3: Utiliser l'API
app.get('/api/profile', async (req, res) => {
  const token = req.session.access_token;
  
  if (!token) {
    return res.status(401).json({ error: 'Non authentifié' });
  }
  
  try {
    const response = await axios.get(`${SAGA_ID_URL}/api/v1/user`, {
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    res.json(response.data);
  } catch (error) {
    res.status(error.response?.status || 500)
       .json({ error: 'Erreur API' });
  }
});

app.listen(3000, () => {
  console.log('App listening on http://localhost:3000');
});
```

### Exemple en PHP (Laravel)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SagaIdController extends Controller
{
    private $clientId = 'votre_client_id';
    private $clientSecret = 'votre_client_secret';
    private $redirectUri = 'http://localhost:8000/callback';
    private $sagaIdUrl = 'http://127.0.0.1:8000';
    
    // Générer PKCE
    private function generatePKCE()
    {
        $verifier = Str::random(128);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        
        return ['verifier' => $verifier, 'challenge' => $challenge];
    }
    
    // Étape 1: Rediriger vers SAGAPASS
    public function login(Request $request)
    {
        $pkce = $this->generatePKCE();
        $state = Str::random(40);
        
        // Sauvegarder en session
        session([
            'pkce_verifier' => $pkce['verifier'],
            'state' => $state
        ]);
        
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'profile email phone',
            'state' => $state,
            'code_challenge' => $pkce['challenge'],
            'code_challenge_method' => 'S256'
        ]);
        
        return redirect("{$this->sagaIdUrl}/oauth/authorize?{$query}");
    }
    
    // Étape 2: Callback
    public function callback(Request $request)
    {
        // Vérifier le state
        if ($request->state !== session('state')) {
            return response()->json(['error' => 'Invalid state'], 400);
        }
        
        // Échanger le code contre un token
        $response = Http::asForm()->post("{$this->sagaIdUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'code' => $request->code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code_verifier' => session('pkce_verifier')
        ]);
        
        if ($response->failed()) {
            return response()->json(['error' => 'Token exchange failed'], 400);
        }
        
        $token = $response->json('access_token');
        
        // Récupérer le profil
        $userResponse = Http::withToken($token)
            ->accept('application/json')
            ->get("{$this->sagaIdUrl}/api/v1/user");
        
        if ($userResponse->failed()) {
            return response()->json(['error' => 'Failed to fetch user'], 400);
        }
        
        // Sauvegarder en session
        session([
            'access_token' => $token,
            'user' => $userResponse->json()
        ]);
        
        return redirect('/dashboard');
    }
    
    // Étape 3: Utiliser l'API
    public function getDocuments()
    {
        $token = session('access_token');
        
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $response = Http::withToken($token)
            ->accept('application/json')
            ->get("{$this->sagaIdUrl}/api/v1/user/documents");
        
        return $response->json();
    }
}
```

### Exemple en Python (Flask)

```python
from flask import Flask, redirect, request, session, jsonify
import requests
import secrets
import hashlib
import base64

app = Flask(__name__)
app.secret_key = 'votre_secret_key'

CLIENT_ID = 'votre_client_id'
CLIENT_SECRET = 'votre_client_secret'
REDIRECT_URI = 'http://localhost:5000/callback'
SAGA_ID_URL = 'http://127.0.0.1:8000'

def generate_pkce():
    verifier = base64.urlsafe_b64encode(secrets.token_bytes(32)).decode('utf-8').rstrip('=')
    challenge = base64.urlsafe_b64encode(
        hashlib.sha256(verifier.encode('utf-8')).digest()
    ).decode('utf-8').rstrip('=')
    return {'verifier': verifier, 'challenge': challenge}

@app.route('/login')
def login():
    pkce = generate_pkce()
    state = secrets.token_urlsafe(32)
    
    session['pkce_verifier'] = pkce['verifier']
    session['state'] = state
    
    params = {
        'client_id': CLIENT_ID,
        'redirect_uri': REDIRECT_URI,
        'response_type': 'code',
        'scope': 'profile email',
        'state': state,
        'code_challenge': pkce['challenge'],
        'code_challenge_method': 'S256'
    }
    
    auth_url = f"{SAGA_ID_URL}/oauth/authorize?" + '&'.join([f"{k}={v}" for k, v in params.items()])
    return redirect(auth_url)

@app.route('/callback')
def callback():
    code = request.args.get('code')
    state = request.args.get('state')
    
    if state != session.get('state'):
        return jsonify({'error': 'Invalid state'}), 400
    
    # Échanger le code contre un token
    token_response = requests.post(f"{SAGA_ID_URL}/oauth/token", data={
        'grant_type': 'authorization_code',
        'code': code,
        'redirect_uri': REDIRECT_URI,
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'code_verifier': session['pkce_verifier']
    })
    
    if token_response.status_code != 200:
        return jsonify({'error': 'Token exchange failed'}), 400
    
    token_data = token_response.json()
    access_token = token_data['access_token']
    
    # Récupérer le profil
    user_response = requests.get(
        f"{SAGA_ID_URL}/api/v1/user",
        headers={
            'Authorization': f'Bearer {access_token}',
            'Accept': 'application/json'
        }
    )
    
    if user_response.status_code != 200:
        return jsonify({'error': 'Failed to fetch user'}), 400
    
    session['access_token'] = access_token
    session['user'] = user_response.json()
    
    return jsonify({
        'message': 'Authentication successful',
        'user': user_response.json()
    })

@app.route('/api/profile')
def profile():
    token = session.get('access_token')
    
    if not token:
        return jsonify({'error': 'Unauthorized'}), 401
    
    response = requests.get(
        f"{SAGA_ID_URL}/api/v1/user",
        headers={
            'Authorization': f'Bearer {token}',
            'Accept': 'application/json'
        }
    )
    
    return jsonify(response.json())

if __name__ == '__main__':
    app.run(debug=True)
```

---

## 🎨 Widget d'Intégration - Vérification d'Identité

SAGAPASS propose un **widget JavaScript** prêt à l'emploi qui permet d'intégrer facilement un processus de vérification d'identité complet dans votre application. Le widget s'ouvre dans une popup et guide l'utilisateur à travers toutes les étapes de vérification.

> **🔒 SÉCURITÉ - PRÉREQUIS OBLIGATOIRE**
> 
> Avant d'utiliser le widget, vous DEVEZ :
> 1. **Créer un endpoint backend** pour générer le token OAuth
> 2. **Obtenir un token** via le flux **client_credentials** avec le scope **partner:create-citizen**
> 3. **JAMAIS exposer** votre `client_secret` dans le code JavaScript frontend
> 4. Le token doit être récupéré **à chaque nouvelle vérification** (durée de vie : 1 heure)

### 🎯 Fonctionnalités du Widget

- ✅ **Capture de photo de profil** avec caméra
- ✅ **Capture de document d'identité** (recto et verso)
- ✅ **Vidéo de vérification** faciale (15 secondes)
- ✅ **Switch caméra** (avant/arrière) pour mobile
- ✅ **Interface responsive** et mobile-friendly
- ✅ **Validation en temps réel** des données
- ✅ **Notifications via postMessage** pour synchronisation
- ✅ **Sécurisé** : Connexion via OAuth client_credentials

### 🚀 Intégration rapide

#### Étape 1 : Obtenir vos identifiants

1. Créez une application dans le dashboard développeur SAGAPASS
2. Notez votre `client_id` et `client_secret`
3. Assurez-vous que votre application dispose du scope `partner:create-citizen`

#### Étape 2 : Inclure le script Widget

Ajoutez le script dans votre page HTML :

> **⚠️ IMPORTANT :** Le widget nécessite un **token OAuth valide** avec le flux **client_credentials** et le scope **partner:create-citizen**. Vous devez obtenir ce token depuis votre backend AVANT d'ouvrir le widget.

```html
<!DOCTYPE html>
<html>
<head>
    <title>Mon Application</title>
</head>
<body>
    <h1>Vérification d'identité SAGAPASS</h1>
    
    <button onclick="startVerification()">
        Vérifier mon identité
    </button>

    <!-- Inclure le widget SAGAPASS -->
    <script src="https://votre-domaine.com/js/widget.js"></script>
    
    <script>
        async function startVerification() {
            try {
                // Obtenir un token OAuth client_credentials
                const tokenResponse = await fetch('https://votre-domaine.com/oauth/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        grant_type: 'client_credentials',
                        client_id: 'VOTRE_CLIENT_ID',
                        client_secret: 'VOTRE_CLIENT_SECRET',
                        scope: 'partner:create-citizen'
                    })
                });
                
                const { access_token } = await tokenResponse.json();
                
                // Ouvrir le widget
                SagaPass.verify({
                    token: access_token,
                    email: 'utilisateur@example.com',
                    firstName: 'Jean',
                    lastName: 'Dupont',
                    callbackUrl: 'https://votre-site.com/verification-success',
                    
                    // Callbacks
                    onSuccess: function(data) {
                        console.log('Vérification réussie !', data);
                        alert('Votre identité a été vérifiée avec succès !');
                    },
                    
                    onError: function(error) {
                        console.error('Erreur de vérification:', error);
                        alert('Une erreur est survenue lors de la vérification.');
                    },
                    
                    onCancel: function() {
                        console.log('Vérification annulée par l\'utilisateur');
                    }
                });
                
            } catch (error) {
                console.error('Erreur lors de l\'obtention du token:', error);
            }
        }
    </script>
</body>
</html>
```

### 📋 Paramètres du Widget

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `token` | string | ✅ | Access token OAuth (client_credentials) |
| `email` | string | ✅ | Email de l'utilisateur à vérifier |
| `firstName` | string | ✅ | Prénom de l'utilisateur |
| `lastName` | string | ✅ | Nom de famille de l'utilisateur |
| `callbackUrl` | string | ❌ | URL de redirection après succès |
| `onSuccess` | function | ❌ | Callback appelé en cas de succès |
| `onError` | function | ❌ | Callback appelé en cas d'erreur |
| `onCancel` | function | ❌ | Callback appelé si l'utilisateur annule |

### 🔄 Flux de Vérification

Le widget guide l'utilisateur à travers **4 étapes** :

#### **Étape 1 : Informations personnelles**
- Date de naissance (18 ans minimum)
- Téléphone (optionnel)
- Adresse (optionnel)

#### **Étape 2 : Photo de profil**
- Capture via webcam/caméra mobile
- Possibilité de reprendre la photo

#### **Étape 3 : Document d'identité**
- Type de document : **CNI** (Carte Nationale d'Identité)
- **NINU** : 10 chiffres obligatoires
- **Numéro de carte** : 9 caractères alphanumériques
- Dates d'émission et d'expiration
- **Photo RECTO** : Caméra arrière par défaut sur mobile
- **Photo VERSO** : Caméra arrière par défaut sur mobile
- Bouton **Switch Caméra** pour alterner entre caméra avant/arrière

#### **Étape 4 : Vidéo de vérification**
- Enregistrement vidéo de 15 secondes
- Compte à rebours de 3 secondes avant le début
- Visualisation avant envoi

### 📱 Support Mobile

Le widget est optimisé pour mobile avec :

- ✅ **Caméra arrière par défaut** pour les photos de documents
- ✅ **Bouton Switch Caméra** pour basculer entre caméra avant/arrière
- ✅ **Capture manuelle** : L'utilisateur contrôle quand prendre la photo
- ✅ **Interface tactile** responsive
- ✅ **Validation des permissions** caméra/micro

#### Configuration WebView (Applications mobiles)

Si votre application utilise un **WebView**, configurez les permissions :

**Android :**
```java
WebView webView = findViewById(R.id.webview);
WebSettings webSettings = webView.getSettings();
webSettings.setJavaScriptEnabled(true);
webSettings.setMediaPlaybackRequiresUserGesture(false);

webView.setWebChromeClient(new WebChromeClient() {
    @Override
    public void onPermissionRequest(PermissionRequest request) {
        request.grant(request.getResources());
    }
});
```

**AndroidManifest.xml :**
```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-feature android:name="android.hardware.camera" />
```

**iOS (WKWebView) :**
```swift
let configuration = WKWebViewConfiguration()
configuration.allowsInlineMediaPlayback = true
```

**Info.plist :**
```xml
<key>NSCameraUsageDescription</key>
<string>Cette application nécessite l'accès à la caméra pour vérifier votre identité</string>
<key>NSMicrophoneUsageDescription</key>
<string>Cette application nécessite l'accès au microphone</string>
```

### 🔔 Notifications (postMessage)

Le widget communique avec votre page via **postMessage** :

```javascript
// Écouter les événements du widget
window.addEventListener('message', function(event) {
    if (event.data.type === 'SAGAPASS_VERIFICATION_SUCCESS') {
        console.log('Citoyen créé avec ID:', event.data.citizenId);
        console.log('Email:', event.data.email);
        
        // Rediriger vers votre page de succès
        window.location.href = '/verification-complete';
    }
    
    if (event.data.type === 'SAGAPASS_VERIFICATION_ERROR') {
        console.error('Erreur:', event.data.error);
        alert('Erreur lors de la vérification: ' + event.data.error);
    }
});
```

### 🔐 Authentification Backend (Client Credentials)

Le widget utilise le flux **OAuth Client Credentials** pour authentifier votre application.

> **📌 NOTE IMPORTANTE :** 
> - Le token doit être généré **côté serveur** (backend) pour protéger votre `client_secret`
> - **JAMAIS** exposer votre `client_secret` dans le code JavaScript frontend
> - Le token a une durée de vie de **1 heure**
> - Créez un endpoint API dans votre backend pour générer et fournir le token au frontend

---

#### 🟢 Node.js / Express (Backend)

```javascript
const express = require('express');
const axios = require('axios');
const app = express();

// Endpoint pour obtenir le token
app.get('/api/get-sagapass-token', async (req, res) => {
    try {
        const response = await axios.post('https://sagapass.com/oauth/token', 
            new URLSearchParams({
                grant_type: 'client_credentials',
                client_id: process.env.SAGAPASS_CLIENT_ID,
                client_secret: process.env.SAGAPASS_CLIENT_SECRET,
                scope: 'partner:create-citizen'
            }),
            {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }
        );
        
        res.json({ 
            success: true,
            token: response.data.access_token 
        });
    } catch (error) {
        res.status(500).json({ 
            success: false,
            error: 'Failed to get token' 
        });
    }
});

app.listen(3000);
```

**Frontend (JavaScript/HTML) :**
```html
<!DOCTYPE html>
<html>
<head>
    <title>Vérification SAGAPASS</title>
    <script src="https://sagapass.com/js/widget.js"></script>
</head>
<body>
    <button onclick="startVerification()">Vérifier mon identité</button>

    <script>
        async function startVerification() {
            try {
                // 1. Obtenir le token depuis votre backend
                const response = await fetch('/api/get-sagapass-token');
                const { token } = await response.json();
                
                // 2. Ouvrir le widget avec le token
                SagaPass.verify({
                    token: token,
                    email: 'user@example.com',
                    firstName: 'Jean',
                    lastName: 'Dupont',
                    
                    onSuccess: function(data) {
                        console.log('Succès:', data);
                        alert('Vérification réussie !');
                    },
                    
                    onError: function(error) {
                        console.error('Erreur:', error);
                    }
                });
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
        
        // Écouter les messages du widget
        window.addEventListener('message', function(event) {
            if (event.data.type === 'SAGAPASS_VERIFICATION_SUCCESS') {
                console.log('Citoyen ID:', event.data.citizenId);
                console.log('Email:', event.data.email);
                console.log('Nom:', event.data.firstName, event.data.lastName);
            }
        });
    </script>
</body>
</html>
```

---

#### 🔵 PHP / Laravel (Backend)

**Controller :**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SagaPassController extends Controller
{
    // Endpoint pour obtenir le token
    public function getToken()
    {
        try {
            $response = Http::asForm()->post(config('sagapass.url') . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('sagapass.client_id'),
                'client_secret' => config('sagapass.client_secret'),
                'scope' => 'partner:create-citizen'
            ]);
            
            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token generation failed'
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'token' => $response->json('access_token')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Vérifier le statut
    public function checkStatus(Request $request)
    {
        $email = $request->query('email');
        $token = $this->getPartnerToken();
        
        $response = Http::withToken($token)
            ->get(config('sagapass.url') . '/api/partner/v1/check-verification', [
                'email' => $email
            ]);
        
        return response()->json($response->json());
    }
    
    private function getPartnerToken()
    {
        $response = Http::asForm()->post(config('sagapass.url') . '/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('sagapass.client_id'),
            'client_secret' => config('sagapass.client_secret'),
            'scope' => 'partner:create-citizen'
        ]);
        
        return $response->json('access_token');
    }
}
```

**Routes (routes/web.php) :**
```php
Route::get('/api/get-sagapass-token', [SagaPassController::class, 'getToken']);
Route::get('/api/check-verification', [SagaPassController::class, 'checkStatus']);
```

**Config (config/sagapass.php) :**
```php
<?php

return [
    'url' => env('SAGAPASS_URL', 'https://sagapass.com'),
    'client_id' => env('SAGAPASS_CLIENT_ID'),
    'client_secret' => env('SAGAPASS_CLIENT_SECRET'),
];
```

**.env :**
```env
SAGAPASS_URL=https://sagapass.com
SAGAPASS_CLIENT_ID=votre_client_id
SAGAPASS_CLIENT_SECRET=votre_client_secret
```

**Frontend (Blade) :**
```blade
<!DOCTYPE html>
<html>
<head>
    <title>Vérification SAGAPASS</title>
    <script src="https://sagapass.com/js/widget.js"></script>
</head>
<body>
    <button onclick="startVerification()">Vérifier mon identité</button>

    <script>
        async function startVerification() {
            try {
                const response = await fetch('/api/get-sagapass-token');
                const { token } = await response.json();
                
                SagaPass.verify({
                    token: token,
                    email: '{{ $user->email }}',
                    firstName: '{{ $user->first_name }}',
                    lastName: '{{ $user->last_name }}',
                    
                    onSuccess: function(data) {
                        window.location.href = '/verification-success';
                    }
                });
            } catch (error) {
                alert('Erreur: ' + error.message);
            }
        }
    </script>
</body>
</html>
```

---

#### 🐍 Python / Django (Backend)

**views.py :**
```python
import requests
from django.http import JsonResponse
from django.conf import settings
from django.views.decorators.http import require_http_methods

@require_http_methods(["GET"])
def get_sagapass_token(request):
    try:
        response = requests.post(
            f"{settings.SAGAPASS_URL}/oauth/token",
            data={
                'grant_type': 'client_credentials',
                'client_id': settings.SAGAPASS_CLIENT_ID,
                'client_secret': settings.SAGAPASS_CLIENT_SECRET,
                'scope': 'partner:create-citizen'
            },
            headers={'Content-Type': 'application/x-www-form-urlencoded'}
        )
        
        if response.status_code == 200:
            data = response.json()
            return JsonResponse({
                'success': True,
                'token': data['access_token']
            })
        else:
            return JsonResponse({
                'success': False,
                'error': 'Failed to get token'
            }, status=500)
            
    except Exception as e:
        return JsonResponse({
            'success': False,
            'error': str(e)
        }, status=500)

@require_http_methods(["GET"])
def check_verification_status(request):
    email = request.GET.get('email')
    
    # Obtenir le token
    token_response = requests.post(
        f"{settings.SAGAPASS_URL}/oauth/token",
        data={
            'grant_type': 'client_credentials',
            'client_id': settings.SAGAPASS_CLIENT_ID,
            'client_secret': settings.SAGAPASS_CLIENT_SECRET,
            'scope': 'partner:create-citizen'
        }
    )
    
    token = token_response.json()['access_token']
    
    # Vérifier le statut
    response = requests.get(
        f"{settings.SAGAPASS_URL}/api/partner/v1/check-verification",
        params={'email': email},
        headers={'Authorization': f'Bearer {token}'}
    )
    
    return JsonResponse(response.json())
```

**urls.py :**
```python
from django.urls import path
from . import views

urlpatterns = [
    path('api/get-sagapass-token', views.get_sagapass_token, name='get_sagapass_token'),
    path('api/check-verification', views.check_verification_status, name='check_verification'),
]
```

**settings.py :**
```python
# SAGAPASS Configuration
SAGAPASS_URL = os.getenv('SAGAPASS_URL', 'https://sagapass.com')
SAGAPASS_CLIENT_ID = os.getenv('SAGAPASS_CLIENT_ID')
SAGAPASS_CLIENT_SECRET = os.getenv('SAGAPASS_CLIENT_SECRET')
```

**Template (HTML) :**
```html
{% load static %}
<!DOCTYPE html>
<html>
<head>
    <title>Vérification SAGAPASS</title>
    <script src="https://sagapass.com/js/widget.js"></script>
</head>
<body>
    <button onclick="startVerification()">Vérifier mon identité</button>

    <script>
        async function startVerification() {
            try {
                const response = await fetch('/api/get-sagapass-token');
                const data = await response.json();
                
                if (data.success) {
                    SagaPass.verify({
                        token: data.token,
                        email: '{{ user.email }}',
                        firstName: '{{ user.first_name }}',
                        lastName: '{{ user.last_name }}',
                        
                        onSuccess: function(result) {
                            window.location.href = '/verification-success/';
                        },
                        
                        onError: function(error) {
                            alert('Erreur: ' + error.error);
                        }
                    });
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
    </script>
</body>
</html>
```

---

#### 📱 Flutter / Dart (Mobile App)

**Backend Service (Dart) :**
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class SagaPassService {
  final String baseUrl = 'https://sagapass.com';
  final String clientId;
  final String clientSecret;
  
  SagaPassService({
    required this.clientId,
    required this.clientSecret,
  });
  
  // Obtenir le token OAuth
  Future<String> getToken() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/oauth/token'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {
          'grant_type': 'client_credentials',
          'client_id': clientId,
          'client_secret': clientSecret,
          'scope': 'partner:create-citizen',
        },
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['access_token'];
      } else {
        throw Exception('Failed to get token');
      }
    } catch (e) {
      throw Exception('Error: $e');
    }
  }
  
  // Vérifier le statut
  Future<Map<String, dynamic>> checkVerificationStatus(String email) async {
    final token = await getToken();
    
    final response = await http.get(
      Uri.parse('$baseUrl/api/partner/v1/check-verification?email=$email'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    return json.decode(response.body);
  }
}
```

**WebView Widget :**
```dart
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class SagaPassVerificationPage extends StatefulWidget {
  final String email;
  final String firstName;
  final String lastName;
  
  const SagaPassVerificationPage({
    Key? key,
    required this.email,
    required this.firstName,
    required this.lastName,
  }) : super(key: key);

  @override
  State<SagaPassVerificationPage> createState() => _SagaPassVerificationPageState();
}

class _SagaPassVerificationPageState extends State<SagaPassVerificationPage> {
  late WebViewController _controller;
  final SagaPassService _sagaPassService = SagaPassService(
    clientId: 'VOTRE_CLIENT_ID',
    clientSecret: 'VOTRE_CLIENT_SECRET',
  );
  
  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }
  
  Future<void> _initializeWebView() async {
    final token = await _sagaPassService.getToken();
    
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (String url) {
            // Injecter le script widget
            _controller.runJavaScript('''
              var script = document.createElement('script');
              script.src = 'https://sagapass.com/js/widget.js';
              document.head.appendChild(script);
              
              script.onload = function() {
                SagaPass.verify({
                  token: '$token',
                  email: '${widget.email}',
                  firstName: '${widget.firstName}',
                  lastName: '${widget.lastName}',
                  
                  onSuccess: function(data) {
                    window.flutter_inappwebview.callHandler('verificationSuccess', data);
                  },
                  
                  onError: function(error) {
                    window.flutter_inappwebview.callHandler('verificationError', error);
                  }
                });
              };
            ''');
          },
        ),
      )
      ..addJavaScriptChannel(
        'flutter_inappwebview',
        onMessageReceived: (JavaScriptMessage message) {
          // Gérer les callbacks
          final data = json.decode(message.message);
          if (data['handler'] == 'verificationSuccess') {
            _onVerificationSuccess(data);
          } else if (data['handler'] == 'verificationError') {
            _onVerificationError(data);
          }
        },
      )
      ..loadRequest(Uri.parse('about:blank'));
  }
  
  void _onVerificationSuccess(Map<String, dynamic> data) {
    Navigator.pop(context, data);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Vérification réussie !')),
    );
  }
  
  void _onVerificationError(Map<String, dynamic> error) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Erreur: ${error['error']}')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Vérification SAGAPASS'),
      ),
      body: WebViewWidget(controller: _controller),
    );
  }
}
```

**pubspec.yaml :**
```yaml
dependencies:
  flutter:
    sdk: flutter
  webview_flutter: ^4.0.0
  http: ^1.0.0
```

---

#### 📱 Android (Java/Kotlin - WebView)

**AndroidManifest.xml :**
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-feature android:name="android.hardware.camera" />
```

**MainActivity.java :**
```java
import android.webkit.WebView;
import android.webkit.WebSettings;
import android.webkit.WebChromeClient;
import android.webkit.PermissionRequest;
import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {
    private WebView webView;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        
        webView = findViewById(R.id.webview);
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setMediaPlaybackRequiresUserGesture(false);
        webSettings.setDomStorageEnabled(true);
        
        // Autoriser les permissions caméra/micro
        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onPermissionRequest(PermissionRequest request) {
                request.grant(request.getResources());
            }
        });
        
        // Charger la page avec le widget
        webView.loadUrl("https://votre-site.com/verification");
    }
}
```

---

#### 📱 iOS (Swift - WKWebView)

**Info.plist :**
```xml
<key>NSCameraUsageDescription</key>
<string>Accès caméra pour vérification d'identité SAGAPASS</string>
<key>NSMicrophoneUsageDescription</key>
<string>Accès microphone pour vidéo de vérification</string>
```

**ViewController.swift :**
```swift
import UIKit
import WebKit

class ViewController: UIViewController, WKNavigationDelegate, WKUIDelegate {
    var webView: WKWebView!
    
    override func loadView() {
        let configuration = WKWebViewConfiguration()
        configuration.allowsInlineMediaPlayback = true
        configuration.mediaTypesRequiringUserActionForPlayback = []
        
        webView = WKWebView(frame: .zero, configuration: configuration)
        webView.navigationDelegate = self
        webView.uiDelegate = self
        view = webView
    }
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Charger la page avec le widget
        let url = URL(string: "https://votre-site.com/verification")!
        webView.load(URLRequest(url: url))
    }
    
    // Gérer les permissions caméra/micro
    func webView(_ webView: WKWebView, 
                 decideMediaCapturePermissionsFor origin: WKSecurityOrigin,
                 initiatedBy frame: WKFrameInfo,
                 type: WKMediaCaptureType) async -> WKPermissionDecision {
        return .grant
    }
}
```

---

### ✅ Vérifier le Statut de Vérification

Après la vérification, vous pouvez interroger l'API pour obtenir le statut :

```javascript
// Vérifier le statut
async function checkVerificationStatus(email, token) {
    const response = await fetch(
        `https://votre-domaine.com/api/partner/v1/check-verification?email=${email}`,
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        }
    );
    
    const data = await response.json();
    console.log('Statut:', data.status);
    // Statuts possibles: pending, approved, rejected
}
```

### 🎨 Personnalisation

Le widget utilise les couleurs de SAGAPASS, mais vous pouvez adapter votre interface autour du widget.

**Exemple d'intégration stylisée :**
```html
<style>
    .verification-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .verify-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 50px;
        font-size: 18px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .verify-btn:hover {
        transform: scale(1.05);
    }
</style>

<div class="verification-container">
    <h2>Vérifiez votre identité avec SAGAPASS</h2>
    <p>Pour accéder à nos services premium, nous devons vérifier votre identité.</p>
    <button class="verify-btn" onclick="startVerification()">
        <i class="fas fa-shield-check"></i> Commencer la vérification
    </button>
</div>
```

### 🐛 Gestion des Erreurs

```javascript
SagaPass.verify({
    token: accessToken,
    email: 'user@example.com',
    firstName: 'Jean',
    lastName: 'Dupont',
    
    onError: function(error) {
        // Gérer les différents types d'erreurs
        switch(error.code) {
            case 'DUPLICATE_EMAIL':
                alert('Cet email est déjà enregistré.');
                break;
            case 'DUPLICATE_DOCUMENT':
                alert('Ce document a déjà été utilisé.');
                break;
            case 'INVALID_TOKEN':
                alert('Session expirée. Veuillez réessayer.');
                // Obtenir un nouveau token
                refreshTokenAndRetry();
                break;
            case 'CAMERA_PERMISSION_DENIED':
                alert('Veuillez autoriser l\'accès à la caméra.');
                break;
            default:
                alert('Une erreur est survenue: ' + error.message);
        }
    }
});
```

### 📊 Validation des Données

Le widget valide automatiquement :

| Champ | Validation |
|-------|-----------|
| **Email** | Format email valide + unicité |
| **Date de naissance** | 18 ans minimum |
| **NINU** | Exactement 10 chiffres + unicité |
| **Numéro de carte** | 9 caractères alphanumériques + unicité |
| **Photos** | Recto ET verso obligatoires pour CNI |
| **Vidéo** | 15 secondes exactement |

### 🔒 Sécurité

- ✅ **HTTPS obligatoire** en production
- ✅ **Tokens à courte durée de vie** (1 heure)
- ✅ **Validation côté serveur** de toutes les données
- ✅ **Protection anti-duplication** (email, NINU, numéro de carte)
- ✅ **CSP (Content Security Policy)** configuré
- ✅ **Permissions caméra/micro** gérées

### 📞 Support Widget

Pour toute question sur le widget :
- **Documentation** : Cette section
- **Email** : support@sagapass.com
- **Exemples de code** : Disponibles dans le dashboard développeur

---

## 📊 Limites et quotas

| Limite | Valeur | Description |
|--------|--------|-------------|
| **Requêtes par minute** | 60 | Par token |
| **Requêtes par heure** | 1000 | Par token |
| **Durée de vie du code d'autorisation** | 10 minutes | Le code expire après 10 minutes |
| **Durée de vie de l'access token** | 1 heure | Le token expire après 1 heure |
| **Taille maximale des requêtes** | 1 MB | Pour les requêtes POST |
| **Applications par développeur** | 10 | Maximum d'applications par compte développeur |

**En-têtes de limite de taux** :
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1700000000
```

**Réponse en cas de dépassement (429)** :
```json
{
  "error": "rate_limit_exceeded",
  "error_description": "Trop de requêtes. Veuillez réessayer dans 60 secondes.",
  "retry_after": 60
}
```

---

## 🔄 Changelog

### Version 1.1 (3 décembre 2025)

**Mises à jour API** :
- ✅ **Endpoint `/api/v1/user`** : Ajout de `video_status` et `video_verified_at`
- ✅ **Endpoint `/api/v1/user/documents`** : Restructuration complète avec schéma unifié
  - Nouvelle structure `account` / `document` / `upgrade_required`
  - Support du champ `card_number` pour les CNI
  - Format ISO 8601 pour les dates (`verified_at`)
  - Réponses cohérentes pour tous les niveaux de compte (pending/basic/verified)
- ✅ Amélioration de la guidance avec `upgrade_required` pour les comptes non-Verified

**Nouveaux champs** :
- `video_status` : Statut de la vidéo de vérification (none/pending/approved/rejected)
- `video_verified_at` : Date d'approbation de la vidéo
- `account.level` : Niveau du compte (pending/basic/verified)
- `account.verification_level` : Progression de vérification (email/video/document)
- `account.can_access_documents` : Booléen indiquant l'accès aux documents
- `document.numbers.card_number` : Numéro de carte masqué (CNI uniquement)

**Améliorations structurelles** :
- Schéma de réponse unifié pour tous les niveaux de compte
- Section `upgrade_required` guidant les utilisateurs vers le niveau supérieur
- Distinction claire entre NIU (`numbers.niu`) et numéro de carte (`numbers.card_number`)

---

### Version 1.0 (20 novembre 2025)

**Nouveautés** :
- ✅ Endpoint `/api/v1/user` - Récupération du profil utilisateur
- ✅ Endpoint `/api/v1/user/documents` - Récupération des documents vérifiés
- ✅ Support OAuth 2.0 Authorization Code Flow avec PKCE
- ✅ Gestion des scopes : `profile`, `email`, `phone`, `address`, `documents`
- ✅ Révocation de tokens via `/oauth/revoke`
- ✅ Introspection de tokens via `/oauth/introspect`
- ✅ Masquage automatique des numéros de documents sensibles
- ✅ Ajout du champ `card_number` pour les cartes nationales d'identité
- ✅ Validation stricte : 9 caractères alphanumériques pour card_number, 10 chiffres pour NIU

**Sécurité** :
- ✅ PKCE obligatoire pour toutes les applications
- ✅ Validation des redirect URIs
- ✅ Protection CSRF avec le paramètre `state`
- ✅ Tokens JWT signés
- ✅ HTTPS requis en production
- ✅ Rate limiting par défaut

**Améliorations** :
- ✅ Documentation complète avec exemples de code
- ✅ Messages d'erreur détaillés
- ✅ Support multilingue (FR)
- ✅ Interface d'administration complète
- ✅ Gestion des consentements utilisateurs
- ✅ Logs de connexion OAuth détaillés

---

## 📞 Support

### Ressources

- **Documentation complète** : `OAUTH_COMPLETE_GUIDE.md`
- **Guide admin** : `ADMIN_OAUTH_GUIDE.md`
- **Guide de tests** : `GUIDE_TEST_API_LOCAL.md`
- **Gestion des rôles** : `ROLES_PERMISSIONS_GUIDE.md`

### Contact

- **Email support** : support@sagapass.com
- **Documentation en ligne** : https://docs.sagapass.com
- **Statut du service** : https://status.sagapass.com
- **Dashboard développeur** : https://sagapass.com/developers

### Signaler un problème

Pour signaler un bug ou une vulnérabilité de sécurité :
- **Bugs** : Créer un ticket sur le dashboard développeur
- **Sécurité** : security@sagapass.com (réponse sous 24h)

---

## ⚖️ Conditions d'utilisation

En utilisant l'API SAGAPASS, vous acceptez :

1. **Respect de la vie privée** : Ne collectez que les données nécessaires et respectez le RGPD
2. **Sécurité** : Protégez le `client_secret` et les tokens d'accès
3. **Limites** : Respectez les quotas et limites de taux
4. **Usage légitime** : N'utilisez l'API que pour des fins légales et éthiques
5. **Attribution** : Mentionnez clairement "Connexion avec SAGAPASS" sur votre interface

**Nous nous réservons le droit de** :
- Suspendre ou révoquer l'accès en cas d'abus
- Modifier les limites de taux
- Mettre à jour l'API avec un préavis de 30 jours

---

**© 2025 SAGAPASS - Tous droits réservés**


