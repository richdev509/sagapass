# 🚀 TEST API OAUTH - GUIDE RAPIDE

## Méthode la plus simple (Recommandée)

### 1. Démarrer le serveur
```bash
cd "c:\laravelProject\SAGAPASS\saga-id"
php artisan serve
```

### 2. Ouvrir l'interface de test
Dans votre navigateur, allez sur :
```
http://127.0.0.1:8000/test-oauth.html
```

### 3. Suivre les étapes dans l'interface
1. **Configuration** : Entrer Client ID et Client Secret
2. **Autorisation** : Cliquer sur "Démarrer OAuth Flow"
3. **Consentement** : Autoriser l'accès sur la page SAGAPASS
4. **Token** : Cliquer sur "Échanger contre Access Token"
5. **API** : Tester avec les boutons "GET /api/v1/user" et "GET /api/v1/user/documents"

---

## Alternative : CURL (Terminal)

### Étape 1 : Obtenir le code
Dans le navigateur, aller sur (remplacer CLIENT_ID) :
```
http://127.0.0.1:8000/oauth/authorize?client_id=VOTRE_CLIENT_ID&redirect_uri=http://localhost:3000/callback&response_type=code&scope=profile email&state=test&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&code_challenge_method=S256
```

Autoriser → Copier le `code` de l'URL

### Étape 2 : Échanger le code contre un token
```bash
curl -X POST http://127.0.0.1:8000/oauth/token ^
  -H "Content-Type: application/x-www-form-urlencoded" ^
  -d "grant_type=authorization_code&code=VOTRE_CODE&redirect_uri=http://localhost:3000/callback&client_id=VOTRE_CLIENT_ID&client_secret=VOTRE_CLIENT_SECRET&code_verifier=test_verifier_1234567890_abcdefghijklmnopqrstuvwxyz"
```

### Étape 3 : Tester l'API
```bash
curl http://127.0.0.1:8000/api/v1/user ^
  -H "Authorization: Bearer VOTRE_TOKEN" ^
  -H "Accept: application/json"
```

---

## Alternative : Postman

1. **Installer Postman** : https://www.postman.com/downloads/
2. **Créer une requête** avec OAuth 2.0
3. **Configuration** :
   - Grant Type: Authorization Code (With PKCE)
   - Auth URL: `http://127.0.0.1:8000/oauth/authorize`
   - Token URL: `http://127.0.0.1:8000/oauth/token`
   - Client ID: Votre Client ID
   - Client Secret: Votre Client Secret
4. **Get New Access Token**
5. **Tester** : GET `http://127.0.0.1:8000/api/v1/user`

---

## Avant de commencer

### ✅ Checklist
- [ ] Serveur Laravel démarré (`php artisan serve`)
- [ ] Application OAuth créée (via `/developers/applications/create`)
- [ ] Application approuvée par admin (via `/admin/oauth`)
- [ ] Client ID et Client Secret notés

### 🔑 Où trouver Client ID et Secret ?
1. Se connecter comme développeur : `http://127.0.0.1:8000/developers/login`
2. Aller sur "Mes Applications" : `http://127.0.0.1:8000/developers/applications`
3. Cliquer sur votre application
4. **Client ID** est affiché
5. **Client Secret** était affiché à la création (le noter immédiatement)

---

## 🎯 Endpoints disponibles

| URL | Méthode | Description |
|-----|---------|-------------|
| `/oauth/authorize` | GET | Page de consentement |
| `/oauth/token` | POST | Obtenir un token |
| `/api/v1/user` | GET | Profil utilisateur |
| `/api/v1/user/documents` | GET | Documents vérifiés |

---

## 🐛 Problèmes courants

**❌ "Application not found"**
→ Vérifier le Client ID

**❌ "Invalid redirect URI"**
→ Utiliser exactement : `http://localhost:3000/callback`

**❌ "Unauthenticated"**
→ Vérifier le token dans l'en-tête `Authorization: Bearer XXX`

**❌ Page de consentement ne s'affiche pas**
→ Se connecter d'abord comme citoyen : `http://127.0.0.1:8000/login`

---

## 📞 Aide

- **Logs** : `storage/logs/laravel.log`
- **Documentation complète** : `GUIDE_TEST_API_LOCAL.md`
- **Interface de test** : `http://127.0.0.1:8000/test-oauth.html`

**Bon test ! 🚀**
