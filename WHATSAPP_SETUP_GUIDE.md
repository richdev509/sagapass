# Guide de Configuration et Tests - WhatsApp Bot MVP

## 🚀 Installation et Configuration

### 1. Variables d'Environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# WhatsApp Cloud API Configuration
WHATSAPP_API_TOKEN=your_permanent_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_account_id
WHATSAPP_APP_SECRET=your_app_secret
WHATSAPP_VERIFY_TOKEN=sagaloto_secure_token_2026

# Sécurité
WHATSAPP_VERIFY_SIGNATURE=true
WHATSAPP_AUTHORIZED_NUMBERS=+50938123456,+50937654321

# Session
WHATSAPP_SESSION_LIFETIME=15

# Logging
WHATSAPP_ENABLE_LOGGING=true
WHATSAPP_LOG_INCOMING=true
WHATSAPP_LOG_OUTGOING=true

# Rate Limiting
WHATSAPP_RATE_LIMIT=10
WHATSAPP_ENABLE_RATE_LIMITING=true
```

### 2. Migrations de Base de Données

Exécutez les migrations :

```bash
php artisan migrate
```

Cela créera 3 tables :
- `whatsapp_sessions` - Gestion des sessions utilisateur
- `whatsapp_logs` - Logs d'audit
- `whatsapp_authorized_numbers` - Numéros autorisés

### 3. Ajouter des Numéros Autorisés

Vous pouvez ajouter des numéros autorisés via :

**a) Variables d'environnement** (simple pour MVP)
```env
WHATSAPP_AUTHORIZED_NUMBERS=+50938123456,+50937654321
```

**b) Base de données** (recommandé pour production)
```sql
INSERT INTO whatsapp_authorized_numbers (phone_number, name, role, is_active, created_at, updated_at)
VALUES ('+50938123456', 'Admin Principal', 'admin', 1, NOW(), NOW());
```

**c) Tinker** (pour tests rapides)
```bash
php artisan tinker
```
```php
DB::table('whatsapp_authorized_numbers')->insert([
    'phone_number' => '+50938123456',
    'name' => 'Admin Test',
    'role' => 'admin',
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

## 📱 Configuration WhatsApp Cloud API (Meta)

### 1. Créer une App WhatsApp Business

1. Allez sur https://developers.facebook.com/apps
2. Créez une nouvelle app → Type "Business"
3. Ajoutez le produit "WhatsApp"
4. Configurez votre numéro de téléphone WhatsApp Business

### 2. Obtenir les Credentials

**API Token** (Permanent) :
1. Allez dans WhatsApp → Configuration
2. Générez un token permanent (pas un token temporaire!)
3. Copiez dans `WHATSAPP_API_TOKEN`

**Phone Number ID** :
1. WhatsApp → Numéros de téléphone
2. Copiez l'ID du numéro
3. Mettez dans `WHATSAPP_PHONE_NUMBER_ID`

**App Secret** :
1. Paramètres → Basique
2. Copiez le "App Secret"
3. Mettez dans `WHATSAPP_APP_SECRET`

### 3. Configurer le Webhook

1. WhatsApp → Configuration → Webhook
2. URL du callback : `https://votre-domaine.com/api/whatsapp/webhook`
3. Token de vérification : `sagaloto_secure_token_2026` (ou votre valeur `WHATSAPP_VERIFY_TOKEN`)
4. Cochez ces champs :
   - ✅ messages
   - ✅ message_status
5. Cliquez sur "Vérifier et enregistrer"

## 🧪 Tests Locaux (Sans WhatsApp)

### 1. Test du Système de Configuration

```bash
php artisan tinker
```

```php
// Vérifier la config
config('whatsapp.api_token');
config('whatsapp.phone_number_id');
config('whatsapp.authorized_numbers');

// Tester le service WhatsApp
$whatsapp = app(\App\Services\WhatsApp\WhatsAppService::class);
$info = $whatsapp->getPhoneNumberInfo();
print_r($info);
```

### 2. Test via les Routes de Test

**a) Vérifier le Token API**
```bash
curl http://localhost:8000/api/whatsapp/test/verify-token
```

**b) Test de Session**
```bash
curl http://localhost:8000/api/whatsapp/test/session?phone=+50938123456
```

**c) Envoyer un Message de Test**
```bash
curl "http://localhost:8000/api/whatsapp/test/send?to=+50938123456&message=Test+depuis+API"
```

**d) Envoyer le Menu Principal**
```bash
curl "http://localhost:8000/api/whatsapp/test/menu?to=+50938123456"
```

### 3. Simuler un Webhook WhatsApp

**Vérification du Webhook (GET)**
```bash
curl -X GET "http://localhost:8000/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=sagaloto_secure_token_2026&hub.challenge=test123"
```

Devrait retourner : `test123`

**Message Entrant (POST)**
```bash
curl -X POST http://localhost:8000/api/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "object": "whatsapp_business_account",
    "entry": [{
      "changes": [{
        "value": {
          "messages": [{
            "from": "50938123456",
            "id": "wamid.test123",
            "type": "text",
            "text": {"body": "salut"}
          }],
          "metadata": {
            "phone_number_id": "123456789"
          }
        }
      }]
    }]
  }'
```

**Réponse Interactive (Bouton Cliqué)**
```bash
curl -X POST http://localhost:8000/api/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "object": "whatsapp_business_account",
    "entry": [{
      "changes": [{
        "value": {
          "messages": [{
            "from": "50938123456",
            "id": "wamid.test456",
            "type": "interactive",
            "interactive": {
              "type": "button_reply",
              "button_reply": {
                "id": "rapports",
                "title": "📊 Rapports"
              }
            }
          }]
        }
      }]
    }]
  }'
```

## 🌐 Tests avec WhatsApp Réel

### 1. Utiliser ngrok pour Tester Localement

```bash
ngrok http 8000
```

Copiez l'URL ngrok (ex: `https://abc123.ngrok.io`) et configurez-la dans Meta :
- Webhook URL : `https://abc123.ngrok.io/api/whatsapp/webhook`

### 2. Tester le Flow Complet

1. Envoyez "salut" depuis votre WhatsApp
2. Le bot devrait répondre avec le message de bienvenue + menu
3. Cliquez sur "📊 Rapports"
4. Une liste de rapports devrait apparaître
5. Sélectionnez "Rapport journalier"
6. Vous recevez un rapport mocké

### 3. Vérifier les Logs

```bash
tail -f storage/logs/laravel.log
```

Recherchez les lignes :
- `WhatsApp webhook received`
- `WhatsApp message sent`
- `WhatsApp activity`

## 🔍 Debugging

### Problème : Token invalide

**Vérifier :**
```bash
curl http://localhost:8000/api/whatsapp/test/verify-token
```

Si `valid: false`, votre token est expiré ou incorrect.

**Solution :** Générez un token permanent dans Meta Developer Console.

### Problème : Webhook non vérifié

**Vérifier :**
1. Le `WHATSAPP_VERIFY_TOKEN` est-il le même dans `.env` et Meta?
2. L'URL est-elle accessible publiquement?

**Test manuel :**
```bash
curl "https://votre-domaine.com/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=votre_token&hub.challenge=test"
```

### Problème : Messages non reçus

**Vérifier :**
1. Les logs Laravel : `tail -f storage/logs/laravel.log`
2. Le numéro est-il dans la whitelist?
3. Le webhook est-il bien configuré dans Meta?

**Debug :**
```php
// Dans WebhookController::handle()
Log::info('Webhook payload', $request->all());
```

### Problème : Signature invalide

**Désactiver temporairement (développement uniquement) :**
```env
WHATSAPP_VERIFY_SIGNATURE=false
```

**En production, vérifier :**
- Le `WHATSAPP_APP_SECRET` est correct
- Le payload n'est pas modifié en transit

## 📊 Monitoring

### Vérifier les Sessions Actives

```bash
php artisan tinker
```
```php
use Illuminate\Support\Facades\Cache;

// Voir toutes les clés de session
$keys = Cache::getRedis()->keys('whatsapp_session:*');
foreach ($keys as $key) {
    $session = Cache::get($key);
    print_r($session);
}
```

### Vérifier les Logs

```sql
-- Messages des dernières 24h
SELECT * FROM whatsapp_logs 
WHERE created_at >= NOW() - INTERVAL 1 DAY 
ORDER BY created_at DESC;

-- Activité par numéro
SELECT phone_number, COUNT(*) as total, 
       SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) as incoming,
       SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) as outgoing
FROM whatsapp_logs
GROUP BY phone_number;
```

## 🎯 Scénarios de Test Recommandés

### Test 1 : Navigation Complète
1. Envoyer "salut"
2. Cliquer sur "📊 Rapports"
3. Sélectionner "Rapport journalier"
4. Envoyer "menu"
5. Cliquer sur "💰 Ventes"
6. Sélectionner "Aujourd'hui"

### Test 2 : Session Expiration
1. Envoyer "salut"
2. Attendre 16 minutes (session expire)
3. Envoyer un autre message
4. Vérifier qu'une nouvelle session est créée

### Test 3 : Numéro Non Autorisé
1. Utiliser un numéro non dans la whitelist
2. Envoyer "salut"
3. Devrait recevoir le message d'erreur "non autorisé"

### Test 4 : Rate Limiting
1. Envoyer 11 messages en moins d'une minute
2. Le 11ème devrait être bloqué

## 📝 Checklist de Déploiement

Avant de déployer en production :

- [ ] Générer un token WhatsApp permanent
- [ ] Configurer toutes les variables `.env`
- [ ] Exécuter les migrations
- [ ] Ajouter les numéros autorisés
- [ ] Configurer le webhook dans Meta
- [ ] Activer `WHATSAPP_VERIFY_SIGNATURE=true`
- [ ] Retirer les routes de test (ou protéger par auth)
- [ ] Vérifier les logs de production
- [ ] Tester le flow complet
- [ ] Configurer un monitoring (ex: Sentry)
- [ ] Documenter les contacts d'escalade

## 🔗 Ressources Utiles

- [WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Postman Collection WhatsApp](https://www.postman.com/meta/workspace/whatsapp-business-platform)
- [WhatsApp Test Numbers](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started#test-number)

## 📞 Support

Pour toute question ou problème :
1. Vérifier les logs : `storage/logs/laravel.log`
2. Consulter cette documentation
3. Contacter l'équipe technique Sagaloto

---

**Version**: 1.0.0  
**Dernière mise à jour**: 05/02/2026
