# 🤖 Guide Rapide - Telegram Bot

## 📋 Vue d'ensemble

Ce guide vous aide à configurer rapidement le bot Telegram pour accéder aux rapports Saga ID.

**Telegram Bot API = 100% GRATUIT** ✅
- Aucune limite de messages
- Aucun coût caché
- API officielle de Telegram
- Alternative complète à WhatsApp

---

## 🚀 Étape 1: Créer votre bot avec @BotFather

1. **Ouvrez Telegram** sur votre téléphone ou ordinateur

2. **Cherchez @BotFather** (le bot officiel de Telegram avec badge vérifié)

3. **Démarrez une conversation** et tapez:
   ```
   /newbot
   ```

4. **Suivez les instructions**:
   - Choisissez un nom pour votre bot: `Saga ID Bot`
   - Choisissez un username (doit finir par `bot`): `SagaIDBot` ou `SagaPassBot`

5. **Copiez le token** fourni par BotFather:
   ```
   Done! Congratulations on your new bot. You will find it at t.me/SagaIDBot.
   Use this token to access the HTTP API:
   1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
   Keep your token secure and store it safely...
   ```

---

## 🔧 Étape 2: Configuration du fichier .env

1. **Ouvrez** le fichier `.env` de votre projet Laravel

2. **Remplissez** les variables Telegram:

```env
# Token fourni par @BotFather
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz

# Username du bot (sans @)
TELEGRAM_BOT_USERNAME=SagaIDBot

# URL du webhook (votre domaine)
TELEGRAM_WEBHOOK_URL=https://sagapass.com/api/telegram/webhook

# Token secret (gardez celui-ci ou générez-en un nouveau)
TELEGRAM_WEBHOOK_SECRET=SaGa_TeLegr4m_SecUre_T0k3n_2026_Pr0d_xyz789

# Votre User ID Telegram (voir étape 3)
TELEGRAM_AUTHORIZED_USERS=123456789
```

---

## 🆔 Étape 3: Obtenir votre User ID Telegram

1. **Cherchez** le bot `@userinfobot` sur Telegram

2. **Démarrez** une conversation avec `/start`

3. **Copiez** votre User ID (un nombre comme `123456789`)

4. **Ajoutez-le** dans `.env`:
   ```env
   TELEGRAM_AUTHORIZED_USERS=123456789
   ```

   Pour plusieurs utilisateurs:
   ```env
   TELEGRAM_AUTHORIZED_USERS=123456789,987654321,555666777
   ```

---

## 🌐 Étape 4: Configurer le webhook

**Option A - Via navigateur** (simple):

1. Visitez dans votre navigateur:
   ```
   https://sagapass.com/api/telegram/set-webhook
   ```

2. Vous verrez:
   ```json
   {
     "webhook_url": "https://sagapass.com/api/telegram/webhook",
     "result": {
       "ok": true,
       "result": true,
       "description": "Webhook was set"
     }
   }
   ```

**Option B - Via terminal** (avancé):

```bash
curl -X POST "https://api.telegram.org/bot<VOTRE_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://sagapass.com/api/telegram/webhook", "secret_token": "SaGa_TeLegr4m_SecUre_T0k3n_2026_Pr0d_xyz789"}'
```

---

## ✅ Étape 5: Tester votre bot

### Test 1: Message de bienvenue

1. **Ouvrez** Telegram
2. **Cherchez** votre bot par son username: `@SagaIDBot`
3. **Cliquez** sur "Démarrer" ou tapez `/start`
4. **Vous devriez voir**:
   ```
   👋 Bienvenue sur Saga ID Bot!

   🎯 Je vous aide à accéder aux rapports et statistiques Saga ID.

   Utilisez les boutons ci-dessous pour naviguer.
   ```

### Test 2: Menu principal

Les boutons devraient apparaître:
- 📊 Rapports
- 💰 Ventes
- 🎲 Tirages
- ❓ Aide

### Test 3: Navigation

1. **Cliquez** sur "📊 Rapports"
2. **Vous verrez** les sous-options:
   - Rapport Journalier
   - Rapport Hebdomadaire
   - Rapport Mensuel
   - Retour au menu

3. **Cliquez** sur "Rapport Journalier"
4. **Vous recevrez** un message avec des données mockées

---

## 🛠️ Commandes disponibles

| Commande | Description |
|----------|-------------|
| `/start` | Démarrer le bot et afficher le menu principal |
| `/menu` | Afficher le menu principal |
| `/aide` | Afficher l'aide |
| `/help` | Afficher l'aide (anglais) |

---

## 🔍 Vérifier la configuration du webhook

Pour voir l'état actuel du webhook:

```bash
# Via navigateur
https://sagapass.com/api/telegram/get-webhook-info

# Via curl
curl "https://api.telegram.org/bot<VOTRE_TOKEN>/getWebhookInfo"
```

Réponse attendue:
```json
{
  "ok": true,
  "result": {
    "url": "https://sagapass.com/api/telegram/webhook",
    "has_custom_certificate": false,
    "pending_update_count": 0,
    "max_connections": 40
  }
}
```

---

## 🧪 Tests de développement (local)

Pour tester en local avec ngrok:

1. **Installez ngrok**: https://ngrok.com/download

2. **Démarrez votre serveur Laravel**:
   ```bash
   php artisan serve
   ```

3. **Créez un tunnel ngrok**:
   ```bash
   ngrok http 8000
   ```

4. **Copiez l'URL ngrok** (ex: `https://abc123.ngrok.io`)

5. **Configurez le webhook** avec l'URL ngrok:
   ```
   https://abc123.ngrok.io/api/telegram/set-webhook
   ```

6. **Testez** votre bot normalement

---

## 🔐 Sécurité

### Whitelist des utilisateurs

Seuls les User IDs dans `TELEGRAM_AUTHORIZED_USERS` peuvent utiliser le bot.

Pour ajouter/retirer des utilisateurs, modifiez `.env`:
```env
TELEGRAM_AUTHORIZED_USERS=123456789,987654321
```

### Secret token du webhook

Le secret token empêche les requêtes non autorisées vers votre webhook:
```env
TELEGRAM_WEBHOOK_SECRET=VotreSecretUnique
```

### Rate limiting

Par défaut: 20 messages par minute par utilisateur.

Pour modifier:
```env
TELEGRAM_RATE_LIMIT=30
```

---

## 📊 Monitoring et logs

Les logs Telegram sont enregistrés dans:
```
storage/logs/laravel.log
```

Pour voir les logs en temps réel:
```bash
tail -f storage/logs/laravel.log | grep Telegram
```

---

## ❌ Dépannage

### Le bot ne répond pas

1. **Vérifiez** que le webhook est configuré:
   ```
   https://sagapass.com/api/telegram/get-webhook-info
   ```

2. **Vérifiez** votre User ID dans `.env`:
   ```env
   TELEGRAM_AUTHORIZED_USERS=VotreUserID
   ```

3. **Vérifiez** les logs Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Erreur "Unauthorized"

Votre User ID n'est pas dans la whitelist. Ajoutez-le dans `.env`.

### Erreur "Invalid token"

Le `TELEGRAM_BOT_TOKEN` est incorrect. Revérifiez avec @BotFather.

### Webhook non configuré

Visitez:
```
https://sagapass.com/api/telegram/set-webhook
```

---

## 🆚 Comparaison WhatsApp vs Telegram

| Feature | WhatsApp Cloud API | Telegram Bot API |
|---------|-------------------|------------------|
| **Coût** | 1000 messages gratuits/mois | **100% Gratuit** ✅ |
| **Limite de messages** | Quotas variables | **Aucune limite** ✅ |
| **Setup** | Compte Meta Business requis | Juste @BotFather ✅ |
| **Vérification** | Business verification | Aucune vérification ✅ |
| **Délais d'approbation** | Plusieurs jours | **Immédiat** ✅ |
| **Webhooks** | HTTPS requis | HTTPS requis |
| **API** | Graph API | Telegram Bot API |

**Recommandation**: Telegram pour usage interne (0€) ✅

---

## 📁 Structure des fichiers Telegram

```
saga-id/
├── config/
│   └── telegram.php                      # Configuration centrale
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Telegram/
│   │           └── WebhookController.php # Gestion des webhooks
│   └── Services/
│       └── Telegram/
│           ├── TelegramService.php       # Communication API
│           └── TelegramMenuService.php   # Gestion des menus
└── routes/
    └── telegram.php                      # Routes webhook
```

---

## 🔄 Prochaines étapes

1. ✅ Bot configuré et fonctionnel
2. 🔄 Ajouter plus d'utilisateurs autorisés
3. 🔄 Intégrer l'API Sagaloto (remplacer les mock responses)
4. 🔄 Ajouter la génération de PDF
5. 🔄 Ajouter des notifications push

---

## 📞 Support

Pour toute question, consultez:
- Documentation officielle: https://core.telegram.org/bots/api
- Guide API: https://core.telegram.org/bots
- FAQ: https://core.telegram.org/bots/faq

---

**🎉 Votre bot Telegram est maintenant opérationnel!**

Utilisez `/start` pour commencer.
