# 🧪 Guide de Test API OAuth en Local

## 🚀 Démarrage rapide

### 1. Démarrer le serveur Laravel

```bash
cd "c:\laravelProject\SAGAPASS\saga-id"
php artisan serve
```

Le serveur démarre sur : `http://127.0.0.1:8000`

---

## 📝 Préparation : Créer une application OAuth

### Étape 1 : Se connecter comme développeur

1. Ouvrir le navigateur : `http://127.0.0.1:8000/developers/login`
2. Se connecter avec un compte SAGAPASS vérifié
3. Si vous n'avez pas de compte développeur, créer un d'abord : `http://127.0.0.1:8000/developers/register`

### Étape 2 : Créer une application

1. Aller sur : `http://127.0.0.1:8000/developers/applications/create`
2. Remplir le formulaire :
   - **Nom** : "Test App Local"
   - **Description** : "Application de test pour développement local"
   - **Site web** : "http://localhost:3000"
   - **URL de redirection** : "http://localhost:3000/callback"
   - **Scopes** : Sélectionner "profile", "email", "phone"
3. Cliquer sur "Créer l'application"
4. **IMPORTANT** : Noter le **Client ID** et **Client Secret** affichés

### Étape 3 : Approuver l'application (Admin)

1. Ouvrir : `http://127.0.0.1:8000/admin/login`
2. Se connecter comme admin
3. Aller sur : `http://127.0.0.1:8000/admin/oauth`
4. Cliquer sur "Voir détails" pour votre application
5. Cliquer sur "Approuver"
6. L'application est maintenant active !

---

## 🔧 Méthode 1 : Test avec CURL (Terminal)

### A. Générer le code PKCE

```bash
# Générer le code_verifier (random string)
$verifier = "test_verifier_1234567890_abcdefghijklmnopqrstuvwxyz"

# Générer le code_challenge (SHA256 du verifier en base64url)
# Pour simplifier le test, on peut utiliser un challenge pré-calculé
$challenge = "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM"
```

### B. Obtenir le code d'autorisation

**Option 1 : Via le navigateur (plus simple)**

1. Remplacer les valeurs et coller dans le navigateur :
```
http://127.0.0.1:8000/oauth/authorize?client_id=VOTRE_CLIENT_ID&redirect_uri=http://localhost:3000/callback&response_type=code&scope=profile email phone&state=random123&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&code_challenge_method=S256
```

2. Se connecter si nécessaire
3. **Voir la page de consentement** (c'est votre page de consentement !)
4. Cliquer sur "Autoriser"
5. Vous serez redirigé vers : `http://localhost:3000/callback?code=XXX&state=random123`
6. **Copier le code** de l'URL

### C. Échanger le code contre un token

```bash
curl -X POST http://127.0.0.1:8000/oauth/token ^
  -H "Content-Type: application/x-www-form-urlencoded" ^
  -d "grant_type=authorization_code" ^
  -d "code=LE_CODE_RECU" ^
  -d "redirect_uri=http://localhost:3000/callback" ^
  -d "client_id=VOTRE_CLIENT_ID" ^
  -d "client_secret=VOTRE_CLIENT_SECRET" ^
  -d "code_verifier=test_verifier_1234567890_abcdefghijklmnopqrstuvwxyz"
```

**Réponse attendue :**
```json
{
  "access_token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "token_type": "Bearer",
  "expires_in": 31536000
}
```

### D. Utiliser l'API avec le token

**Test 1 : Obtenir le profil utilisateur**
```bash
curl http://127.0.0.1:8000/api/v1/user ^
  -H "Authorization: Bearer VOTRE_ACCESS_TOKEN" ^
  -H "Accept: application/json"
```

**Réponse :**
```json
{
  "first_name": "Jean",
  "last_name": "Dupont",
  "email": "jean@example.com",
  "verification_status": "verified",
  "is_verified": true,
  "phone": "+509 1234 5678"
}
```

**Test 2 : Obtenir les documents**
```bash
curl http://127.0.0.1:8000/api/v1/user/documents ^
  -H "Authorization: Bearer VOTRE_ACCESS_TOKEN" ^
  -H "Accept: application/json"
```

**Réponse :**
```json
{
  "verified": true,
  "document_type": "passport",
  "document_number": "****5678",
  "issue_date": "2020-01-15",
  "expiry_date": "2030-01-15",
  "verified_at": "2025-11-19 10:30:00"
}
```

---

## 🌐 Méthode 2 : Test avec Postman

### Installation

1. Télécharger Postman : https://www.postman.com/downloads/
2. Installer et ouvrir Postman

### Configuration OAuth2 dans Postman

1. **Créer une nouvelle requête**
   - Cliquer sur "New" → "HTTP Request"

2. **Configurer l'autorisation**
   - Onglet "Authorization"
   - Type : "OAuth 2.0"
   - Add auth data to : "Request Headers"

3. **Configuration** (cliquer sur "Configure New Token") :
   - **Token Name** : "SAGAPASS Local"
   - **Grant Type** : "Authorization Code (With PKCE)"
   - **Callback URL** : `http://localhost:3000/callback` (cocher "Authorize using browser")
   - **Auth URL** : `http://127.0.0.1:8000/oauth/authorize`
   - **Access Token URL** : `http://127.0.0.1:8000/oauth/token`
   - **Client ID** : VOTRE_CLIENT_ID
   - **Client Secret** : VOTRE_CLIENT_SECRET
   - **Scope** : `profile email phone`
   - **Code Challenge Method** : "SHA-256"

4. **Obtenir le token**
   - Cliquer sur "Get New Access Token"
   - Une fenêtre de navigateur s'ouvre
   - Se connecter et autoriser
   - Le token est automatiquement récupéré

5. **Tester l'API**
   - URL : `http://127.0.0.1:8000/api/v1/user`
   - Method : GET
   - Le token est automatiquement ajouté
   - Cliquer sur "Send"

### Collection Postman à importer

```json
{
  "info": {
    "name": "SAGAPASS API Local",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get User Profile",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "http://127.0.0.1:8000/api/v1/user",
          "host": ["127", "0", "0", "1"],
          "port": "8000",
          "path": ["api", "v1", "user"]
        }
      }
    },
    {
      "name": "Get User Documents",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          }
        ],
        "url": {
          "raw": "http://127.0.0.1:8000/api/v1/user/documents",
          "host": ["127", "0", "0", "1"],
          "port": "8000",
          "path": ["api", "v1", "user", "documents"]
        }
      }
    }
  ]
}
```

**Pour importer** :
1. Copier le JSON ci-dessus
2. Dans Postman : File → Import → Raw text
3. Coller et importer

---

## 💻 Méthode 3 : Test avec un script JavaScript

### Créer un fichier HTML de test

Créer un fichier `test-oauth.html` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test OAuth SAGAPASS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover {
            background: #5568d3;
        }
        .result {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .error {
            border-left-color: #e74c3c;
            color: #e74c3c;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>🧪 Test OAuth SAGAPASS - Local</h1>

    <div class="section">
        <h2>Configuration</h2>
        <input type="text" id="clientId" placeholder="Client ID">
        <input type="text" id="clientSecret" placeholder="Client Secret">
        <button onclick="saveConfig()">Sauvegarder</button>
    </div>

    <div class="section">
        <h2>1. Obtenir le code d'autorisation</h2>
        <button onclick="startOAuth()">Démarrer OAuth Flow</button>
        <p><small>Vous serez redirigé vers la page de consentement</small></p>
    </div>

    <div class="section">
        <h2>2. Après autorisation</h2>
        <p>Le code sera automatiquement détecté dans l'URL</p>
        <div id="codeResult"></div>
        <button onclick="exchangeToken()" id="exchangeBtn" style="display:none">Échanger contre Token</button>
    </div>

    <div class="section">
        <h2>3. Tester l'API</h2>
        <button onclick="testProfile()">GET /api/v1/user</button>
        <button onclick="testDocuments()">GET /api/v1/user/documents</button>
        <div id="apiResult"></div>
    </div>

    <script>
        const BASE_URL = 'http://127.0.0.1:8000';
        const REDIRECT_URI = window.location.href.split('?')[0];

        // Fonctions utilitaires
        function saveConfig() {
            const clientId = document.getElementById('clientId').value;
            const clientSecret = document.getElementById('clientSecret').value;
            localStorage.setItem('clientId', clientId);
            localStorage.setItem('clientSecret', clientSecret);
            alert('Configuration sauvegardée !');
        }

        function loadConfig() {
            document.getElementById('clientId').value = localStorage.getItem('clientId') || '';
            document.getElementById('clientSecret').value = localStorage.getItem('clientSecret') || '';
        }

        // Générer PKCE
        function generateCodeVerifier() {
            const array = new Uint8Array(32);
            crypto.getRandomValues(array);
            return base64URLEncode(array);
        }

        function base64URLEncode(buffer) {
            return btoa(String.fromCharCode(...new Uint8Array(buffer)))
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=/g, '');
        }

        async function generateCodeChallenge(verifier) {
            const encoder = new TextEncoder();
            const data = encoder.encode(verifier);
            const hash = await crypto.subtle.digest('SHA-256', data);
            return base64URLEncode(hash);
        }

        // 1. Démarrer OAuth
        async function startOAuth() {
            const clientId = localStorage.getItem('clientId');
            if (!clientId) {
                alert('Veuillez d\'abord sauvegarder votre configuration');
                return;
            }

            const verifier = generateCodeVerifier();
            const challenge = await generateCodeChallenge(verifier);
            
            localStorage.setItem('codeVerifier', verifier);
            
            const params = new URLSearchParams({
                client_id: clientId,
                redirect_uri: REDIRECT_URI,
                response_type: 'code',
                scope: 'profile email phone documents',
                state: Math.random().toString(36).substring(7),
                code_challenge: challenge,
                code_challenge_method: 'S256'
            });

            window.location.href = `${BASE_URL}/oauth/authorize?${params}`;
        }

        // 2. Détecter le code dans l'URL
        window.onload = function() {
            loadConfig();
            
            const params = new URLSearchParams(window.location.search);
            const code = params.get('code');
            
            if (code) {
                localStorage.setItem('authCode', code);
                document.getElementById('codeResult').innerHTML = 
                    '<div class="result">Code reçu : ' + code + '</div>';
                document.getElementById('exchangeBtn').style.display = 'inline-block';
            }
        };

        // 3. Échanger le code contre un token
        async function exchangeToken() {
            const code = localStorage.getItem('authCode');
            const verifier = localStorage.getItem('codeVerifier');
            const clientId = localStorage.getItem('clientId');
            const clientSecret = localStorage.getItem('clientSecret');

            try {
                const response = await fetch(`${BASE_URL}/oauth/token`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        grant_type: 'authorization_code',
                        code: code,
                        redirect_uri: REDIRECT_URI,
                        client_id: clientId,
                        client_secret: clientSecret,
                        code_verifier: verifier
                    })
                });

                const data = await response.json();
                
                if (data.access_token) {
                    localStorage.setItem('accessToken', data.access_token);
                    document.getElementById('codeResult').innerHTML += 
                        '<div class="result">✅ Token obtenu : ' + data.access_token.substring(0, 30) + '...</div>';
                    alert('Token obtenu avec succès ! Vous pouvez maintenant tester l\'API.');
                } else {
                    document.getElementById('codeResult').innerHTML += 
                        '<div class="result error">❌ Erreur : ' + JSON.stringify(data, null, 2) + '</div>';
                }
            } catch (error) {
                document.getElementById('codeResult').innerHTML += 
                    '<div class="result error">❌ Erreur : ' + error.message + '</div>';
            }
        }

        // 4. Tester l'API
        async function testProfile() {
            await testAPI('/api/v1/user', 'Profile');
        }

        async function testDocuments() {
            await testAPI('/api/v1/user/documents', 'Documents');
        }

        async function testAPI(endpoint, name) {
            const token = localStorage.getItem('accessToken');
            
            if (!token) {
                alert('Veuillez d\'abord obtenir un token');
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}${endpoint}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                document.getElementById('apiResult').innerHTML = 
                    '<h3>' + name + ' :</h3>' +
                    '<div class="result">' + JSON.stringify(data, null, 2) + '</div>';
            } catch (error) {
                document.getElementById('apiResult').innerHTML = 
                    '<div class="result error">❌ Erreur : ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>
```

### Utiliser le fichier HTML

1. **Sauvegarder** le code ci-dessus dans `test-oauth.html`
2. **Ouvrir** le fichier dans votre navigateur
3. **Saisir** votre Client ID et Client Secret
4. **Cliquer** sur "Démarrer OAuth Flow"
5. **Autoriser** sur la page de consentement
6. Vous serez redirigé et le **token sera automatiquement échangé**
7. **Tester** les APIs avec les boutons

---

## 🐛 Méthode 4 : Test avec Tinker (Laravel)

### Tester directement dans Laravel

```bash
cd "c:\laravelProject\SAGAPASS\saga-id"
php artisan tinker
```

### Créer un token manuellement

```php
// Obtenir un utilisateur
$user = App\Models\User::where('email', 'votre-email@example.com')->first();

// Créer un token avec des scopes
$token = $user->createToken('test-token', ['profile', 'email', 'phone', 'documents']);

// Afficher le token
echo $token->plainTextToken;
// Sortie : 1|abcdefghijklmnopqrstuvwxyz1234567890
```

### Tester l'API avec ce token

```bash
curl http://127.0.0.1:8000/api/v1/user ^
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890" ^
  -H "Accept: application/json"
```

---

## 🔍 Déboguer les problèmes

### Vérifier les logs

```bash
# Voir les logs en temps réel
cd "c:\laravelProject\SAGAPASS\saga-id"
tail -f storage/logs/laravel.log
```

### Erreurs courantes

**1. "Unauthenticated" (401)**
- ❌ Token invalide ou expiré
- ✅ Vérifier que le token est bien dans l'en-tête `Authorization: Bearer XXX`

**2. "Application not found" (404)**
- ❌ Client ID incorrect
- ✅ Vérifier le Client ID dans le dashboard développeur

**3. "Invalid redirect URI"**
- ❌ L'URI de redirection ne correspond pas
- ✅ Utiliser exactement la même URI que celle enregistrée

**4. "Invalid code_verifier" (PKCE)**
- ❌ Le verifier ne correspond pas au challenge
- ✅ Utiliser le même verifier que celui utilisé pour générer le challenge

**5. "Code has expired"**
- ❌ Le code a expiré (10 minutes)
- ✅ Recommencer le flow OAuth

### Commandes utiles

```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Voir les routes OAuth
php artisan route:list --path=oauth

# Voir les routes API
php artisan route:list --path=api

# Vérifier la connexion BDD
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## ✅ Checklist de test

Cochez au fur et à mesure :

### Configuration
- [ ] Serveur Laravel démarré (`php artisan serve`)
- [ ] Application OAuth créée
- [ ] Application approuvée par admin
- [ ] Client ID et Secret notés

### Flow OAuth
- [ ] URL d'autorisation générée
- [ ] Page de consentement affichée correctement
- [ ] Logo et informations de l'app visibles
- [ ] Scopes (permissions) listés clairement
- [ ] Boutons Autoriser/Refuser présents
- [ ] Autorisation accordée
- [ ] Code d'autorisation reçu
- [ ] Token obtenu avec succès

### API
- [ ] Endpoint `/api/v1/user` fonctionne
- [ ] Données utilisateur retournées
- [ ] Scopes respectés (profile, email, phone)
- [ ] Endpoint `/api/v1/user/documents` fonctionne
- [ ] Documents masqués correctement
- [ ] Erreur 401 si token invalide
- [ ] Erreur 403 si scope manquant

---

## 📊 Résumé des endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/oauth/authorize` | GET | Cookie | Page de consentement |
| `/oauth/authorize` | POST | Cookie | Approuver/Refuser |
| `/oauth/token` | POST | None | Échanger code → token |
| `/oauth/revoke` | POST | None | Révoquer un token |
| `/api/v1/user` | GET | Bearer | Profil utilisateur |
| `/api/v1/user/documents` | GET | Bearer | Documents vérifiés |

---

## 🎯 Prochaines étapes

Une fois les tests locaux réussis :

1. **Tester la révocation** : `/profile/connected-services`
2. **Tester la suspension** : Panel admin
3. **Tester avec différents scopes**
4. **Documenter les retours** pour votre équipe
5. **Préparer le déploiement** en production

---

**Bon test ! 🚀**

Si vous rencontrez des problèmes, vérifiez les logs et n'hésitez pas à me demander.
