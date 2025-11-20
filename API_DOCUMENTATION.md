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
7. [Limites et quotas](#limites-et-quotas)
8. [Changelog](#changelog)

---

## 🎯 Introduction

SAGAPASS est un service d'identité numérique sécurisé qui permet aux citoyens de s'authentifier et de partager leurs informations vérifiées avec des applications tierces.

### Cas d'usage

- **Authentification unique (SSO)** : Permettre aux utilisateurs de se connecter avec leur compte SAGAPASS
- **Vérification d'identité** : Confirmer l'identité d'un utilisateur avec des documents officiels vérifiés
- **Partage de données** : Accéder aux informations de profil avec le consentement de l'utilisateur
- **KYC (Know Your Customer)** : Récupérer des informations de documents vérifiés

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
| `profile` | Informations de profil de base | `first_name`, `last_name`, `verification_status`, `is_verified` |
| `email` | Adresse email | `email`, `email_verified_at` |
| `phone` | Numéro de téléphone | `phone` |
| `address` | Adresse postale | `address` |
| `documents` | Documents d'identité vérifiés | `document_type`, `document_number` (masqué), dates, statut de vérification |

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

**Scopes requis** : Au moins un parmi `profile`, `email`, `phone`, `address`

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
  "email": "jean.dupont@example.com",
  "email_verified_at": "2025-10-15",
  "phone": "+33612345678",
  "address": "123 Rue de la Paix, 75001 Paris",
  "verification_status": "verified",
  "is_verified": true
}
```

**Champs retournés selon les scopes** :

| Champ | Scope requis | Type | Description |
|-------|--------------|------|-------------|
| `first_name` | `profile` | string | Prénom |
| `last_name` | `profile` | string | Nom de famille |
| `verification_status` | `profile` | string | Statut : `pending`, `verified`, `rejected` |
| `is_verified` | `profile` | boolean | `true` si identité vérifiée |
| `email` | `email` | string | Adresse email |
| `email_verified_at` | `email` | date | Date de vérification email |
| `phone` | `phone` | string | Numéro de téléphone |
| `address` | `address` | string | Adresse postale complète |

**Erreurs** :
- `401 Unauthorized` : Token manquant ou invalide
- `403 Forbidden` : Aucun scope accordé

---

### 2. Obtenir les documents vérifiés

Récupère les informations sur les documents d'identité vérifiés de l'utilisateur.

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

**Réponse (200 OK) - Utilisateur vérifié** :
```json
{
  "verified": true,
  "document_type": "cni",
  "card_number": "****3DEF",
  "document_number": "****567890",
  "issue_date": "2020-01-15",
  "expiry_date": "2030-01-15",
  "verified_at": "2025-10-20 14:30:00"
}
```

**Réponse (200 OK) - Utilisateur non vérifié** :
```json
{
  "verified": false,
  "message": "L'utilisateur n'a pas de documents vérifiés."
}
```

**Champs retournés** :

| Champ | Type | Description |
|-------|------|-------------|
| `verified` | boolean | `true` si l'utilisateur a un document vérifié |
| `document_type` | string | Type : `cni` (Carte Nationale) ou `passport` |
| `card_number` | string | Numéro de carte (masqué, 4 derniers caractères) |
| `document_number` | string | Numéro du document (masqué, 4 derniers chiffres) |
| `issue_date` | date | Date de délivrance du document |
| `expiry_date` | date | Date d'expiration du document |
| `verified_at` | datetime | Date et heure de vérification par l'administrateur |
| `message` | string | Message informatif si non vérifié |

**Notes de sécurité** :
- Les numéros de documents sont toujours masqués (seuls les 4 derniers caractères sont visibles)
- Seul le dernier document vérifié est retourné
- Les photos des documents ne sont jamais accessibles via l'API

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

*Cette documentation est mise à jour régulièrement. Consultez le changelog pour les dernières modifications.*
