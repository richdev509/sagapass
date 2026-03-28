# 🚀 Démarrage Rapide - WhatsApp Bot MVP

## Installation en 5 minutes

### 1. Configuration `.env`

Ajoutez ces lignes dans votre `.env` :

```env
WHATSAPP_API_TOKEN=votre_token_ici
WHATSAPP_PHONE_NUMBER_ID=votre_phone_id
WHATSAPP_BUSINESS_ACCOUNT_ID=votre_business_id
WHATSAPP_APP_SECRET=votre_app_secret
WHATSAPP_VERIFY_TOKEN=sagaloto_secure_token_2026
WHATSAPP_AUTHORIZED_NUMBERS=+50938123456
```

### 2. Migrations

```bash
php artisan migrate
```

### 3. Test Rapide

```bash
# Démarrer le serveur
php artisan serve

# Test du webhook (dans un autre terminal)
curl -X GET "http://localhost:8000/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=sagaloto_secure_token_2026&hub.challenge=test123"
```

**Résultat attendu** : `test123`

✅ Si vous voyez `test123`, tout fonctionne!

### 4. Tester avec WhatsApp (optionnel)

```bash
# Installer ngrok
ngrok http 8000

# Copier l'URL ngrok et configurer dans Meta:
# https://abc123.ngrok.io/api/whatsapp/webhook
```

## 🎯 Test du Flow Complet

Envoyez ces messages depuis WhatsApp :

1. **"salut"** → Vous recevez le menu principal
2. **Cliquer "📊 Rapports"** → Menu rapports s'affiche
3. **Sélectionner "Rapport journalier"** → Rapport mocké reçu

## 📂 Fichiers Créés

```
saga-id/
├── app/
│   ├── Http/
│   │   ├── Controllers/WhatsApp/
│   │   │   └── WebhookController.php          ✅ Gère les webhooks
│   │   └── Middleware/
│   │       └── WhatsAppSecurityMiddleware.php  ✅ Sécurité
│   └── Services/WhatsApp/
│       ├── WhatsAppService.php                 ✅ Envoi messages
│       ├── MenuService.php                     ✅ Gestion menus
│       └── SessionService.php                  ✅ Gestion sessions
├── config/
│   └── whatsapp.php                            ✅ Configuration
├── database/migrations/
│   ├── 2026_02_05_000001_create_whatsapp_sessions_table.php
│   ├── 2026_02_05_000002_create_whatsapp_logs_table.php
│   └── 2026_02_05_000003_create_whatsapp_authorized_numbers_table.php
├── routes/
│   └── whatsapp.php                            ✅ Routes API
├── WHATSAPP_MVP_ARCHITECTURE.md                ✅ Documentation architecture
└── WHATSAPP_SETUP_GUIDE.md                     ✅ Guide complet
```

## 🔧 Routes Disponibles

### Production
- `GET /api/whatsapp/webhook` - Vérification webhook Meta
- `POST /api/whatsapp/webhook` - Réception messages WhatsApp

### Test (dev uniquement)
- `GET /api/whatsapp/test/send?to=+509...&message=test`
- `GET /api/whatsapp/test/menu?to=+509...`
- `GET /api/whatsapp/test/session?phone=+509...`
- `GET /api/whatsapp/test/verify-token`

## 📋 Prochaines Étapes

### Phase 1 : MVP (Actuel) ✅
- [x] Architecture API
- [x] Webhook WhatsApp
- [x] Services d'envoi
- [x] Menus interactifs
- [x] Réponses mockées
- [x] Sécurité basique

### Phase 2 : Intégration Sagaloto (À venir)
- [ ] Créer `SagalotoApiService.php`
- [ ] Connecter aux endpoints Sagaloto
- [ ] Remplacer réponses mockées par données réelles
- [ ] Génération de PDF
- [ ] Gestion erreurs API

### Phase 3 : Production (À venir)
- [ ] Authentification robuste
- [ ] Monitoring avancé
- [ ] Notifications push
- [ ] Multi-langue
- [ ] Dashboard admin

## 🆘 Problèmes Courants

**Le webhook ne se vérifie pas**
→ Vérifiez que `WHATSAPP_VERIFY_TOKEN` est identique dans `.env` et Meta

**Messages non envoyés**
→ Vérifiez `WHATSAPP_API_TOKEN` avec `/test/verify-token`

**Numéro non autorisé**
→ Ajoutez le numéro dans `WHATSAPP_AUTHORIZED_NUMBERS`

**Voir les logs**
```bash
tail -f storage/logs/laravel.log
```

## 📚 Documentation Complète

Pour plus de détails, consultez :
- [WHATSAPP_MVP_ARCHITECTURE.md](WHATSAPP_MVP_ARCHITECTURE.md) - Architecture détaillée
- [WHATSAPP_SETUP_GUIDE.md](WHATSAPP_SETUP_GUIDE.md) - Guide complet

---

✅ **Votre MVP WhatsApp est prêt à être testé!**
