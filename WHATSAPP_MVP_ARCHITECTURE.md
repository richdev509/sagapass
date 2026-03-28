# Architecture MVP WhatsApp Bot - Sagaloto

## 📋 Vue d'ensemble

Architecture API pour l'intégration WhatsApp Cloud API avec Laravel, permettant une gestion interactive du système Sagaloto via WhatsApp.

## 🏗️ Architecture Flux de Données

```
┌─────────────────┐
│  Utilisateur    │
│   WhatsApp      │
└────────┬────────┘
         │ Message/Interaction
         ↓
┌─────────────────────────────┐
│  WhatsApp Cloud API (Meta)  │
└────────┬────────────────────┘
         │ Webhook POST
         ↓
┌─────────────────────────────┐
│  Notre API Laravel          │
│  ┌─────────────────────┐   │
│  │ Webhook Controller  │   │
│  └──────────┬──────────┘   │
│             │               │
│  ┌──────────▼──────────┐   │
│  │ WhatsApp Service    │   │
│  └──────────┬──────────┘   │
│             │               │
│  ┌──────────▼──────────┐   │
│  │  Menu Service       │   │
│  └──────────┬──────────┘   │
│             │               │
│  ┌──────────▼──────────┐   │
│  │ (FUTUR: Sagaloto API)│  │
│  └─────────────────────┘   │
└────────┬────────────────────┘
         │ Réponse API (JSON)
         ↓
┌─────────────────────────────┐
│  WhatsApp Cloud API (Meta)  │
└────────┬────────────────────┘
         │ Message/Boutons/PDF
         ↓
┌─────────────────┐
│  Utilisateur    │
│   WhatsApp      │
└─────────────────┘
```

## 🔧 Composants MVP (Phase 1 - Sans Sagaloto)

### 1. **Webhook Controller**
- Réception des messages WhatsApp
- Validation signature Meta
- Routing des actions

### 2. **WhatsApp Service**
- Envoi de messages texte
- Envoi de messages interactifs (boutons)
- Envoi de listes
- Envoi de documents (PDF)
- Gestion API WhatsApp Cloud

### 3. **Menu Service**
- Gestion des menus interactifs
- Navigation entre menus
- Gestion des sessions utilisateur
- Réponses mockées (MVP)

### 4. **Security Middleware**
- Vérification numéros autorisés
- Validation webhook Meta
- Rate limiting

### 5. **Database**
- `whatsapp_sessions` : Sessions utilisateur
- `whatsapp_logs` : Audit trail
- `whatsapp_authorized_numbers` : Numéros autorisés

## 📁 Structure des fichiers

```
app/
├── Http/
│   ├── Controllers/
│   │   └── WhatsApp/
│   │       └── WebhookController.php
│   └── Middleware/
│       └── WhatsAppSecurityMiddleware.php
├── Services/
│   ├── WhatsApp/
│   │   ├── WhatsAppService.php
│   │   ├── MenuService.php
│   │   └── SessionService.php
│   └── Sagaloto/
│       └── SagalotoApiService.php (Phase 2)
└── Models/
    ├── WhatsAppSession.php
    ├── WhatsAppLog.php
    └── WhatsAppAuthorizedNumber.php

config/
└── whatsapp.php

routes/
└── whatsapp.php

database/
└── migrations/
    ├── xxxx_create_whatsapp_sessions_table.php
    ├── xxxx_create_whatsapp_logs_table.php
    └── xxxx_create_whatsapp_authorized_numbers_table.php
```

## 🔐 Sécurité

### Validation Webhook
```php
// Vérification signature Meta
$signature = hash_hmac('sha256', $payload, config('whatsapp.app_secret'));
if ($signature !== $request->header('X-Hub-Signature-256')) {
    abort(401);
}
```

### Numéros Autorisés
- Whitelist des numéros admins
- Vérification à chaque message
- Rejet automatique des non-autorisés

### Session Management
- Expiration après 15 minutes d'inactivité
- Nettoyage automatique des sessions expirées

## 📝 Format des Messages WhatsApp

### Message Texte Simple
```json
{
  "messaging_product": "whatsapp",
  "to": "50938123456",
  "type": "text",
  "text": {
    "body": "Bonjour! Comment puis-je vous aider?"
  }
}
```

### Message avec Boutons
```json
{
  "messaging_product": "whatsapp",
  "to": "50938123456",
  "type": "interactive",
  "interactive": {
    "type": "button",
    "body": {
      "text": "Choisissez une option:"
    },
    "action": {
      "buttons": [
        {
          "type": "reply",
          "reply": {
            "id": "rapports",
            "title": "📊 Rapports"
          }
        },
        {
          "type": "reply",
          "reply": {
            "id": "ventes",
            "title": "💰 Ventes"
          }
        }
      ]
    }
  }
}
```

### Message avec Liste
```json
{
  "messaging_product": "whatsapp",
  "to": "50938123456",
  "type": "interactive",
  "interactive": {
    "type": "list",
    "body": {
      "text": "Sélectionnez un rapport:"
    },
    "action": {
      "button": "Voir options",
      "sections": [
        {
          "title": "Rapports",
          "rows": [
            {
              "id": "rapport_ventes",
              "title": "Rapport ventes",
              "description": "Ventes du jour"
            },
            {
              "id": "rapport_tirages",
              "title": "Rapport tirages",
              "description": "Tirages récents"
            }
          ]
        }
      ]
    }
  }
}
```

## 🎯 Menus MVP (Phase 1)

### Menu Principal
```
👋 Bonjour Admin Sagaloto!

Choisissez une option:

📊 Rapports
💰 Résumé ventes
🧾 Historique tirages
ℹ️ Aide
```

### Sous-menus

**Rapports:**
- Rapport journalier
- Rapport hebdomadaire
- Rapport mensuel
- ⬅️ Retour

**Résumé ventes:**
- Ventes aujourd'hui
- Ventes cette semaine
- Ventes ce mois
- ⬅️ Retour

## 📊 Réponses Mockées (MVP)

Exemples de réponses simulées pour tester:

### Rapport Journalier
```
📊 Rapport Journalier - 05/02/2026

💰 Ventes: 125,000 HTG
🎫 Tickets vendus: 45
🏆 Gains distribués: 35,000 HTG
📈 Bénéfice net: 90,000 HTG

✅ Tout fonctionne normalement
```

### Résumé Ventes
```
💰 Résumé Ventes - Aujourd'hui

Matin (6h-12h): 45,000 HTG
Après-midi (12h-18h): 50,000 HTG
Soir (18h-23h): 30,000 HTG

Total: 125,000 HTG
```

## 🔄 Gestion des Sessions

### Structure Session
```php
[
    'phone_number' => '50938123456',
    'current_menu' => 'main',
    'context' => [],
    'last_activity' => '2026-02-05 10:30:00',
    'expires_at' => '2026-02-05 10:45:00'
]
```

### États de Navigation
- `main` : Menu principal
- `rapports` : Menu rapports
- `ventes` : Menu ventes
- `tirages` : Menu tirages

## 🚀 Configuration WhatsApp Cloud API

### Variables d'environnement (.env)
```env
WHATSAPP_API_TOKEN=your_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_id
WHATSAPP_APP_SECRET=your_app_secret
WHATSAPP_VERIFY_TOKEN=your_verify_token
WHATSAPP_WEBHOOK_URL=https://yourdomain.com/api/whatsapp/webhook
```

## 📌 Étapes d'Implémentation

### Phase 1: MVP (Sans Sagaloto) ✅
1. ✅ Configuration WhatsApp Cloud API
2. ✅ Création des migrations
3. ✅ Création des modèles
4. ✅ Création du WebhookController
5. ✅ Création du WhatsAppService
6. ✅ Création du MenuService
7. ✅ Création du middleware sécurité
8. ✅ Tests avec réponses mockées

### Phase 2: Intégration Sagaloto (Futur)
1. ⏳ Création SagalotoApiService
2. ⏳ Intégration endpoints Sagaloto
3. ⏳ Gestion authentification Sagaloto
4. ⏳ Remplacement réponses mockées
5. ⏳ Gestion erreurs API Sagaloto

### Phase 3: Fonctionnalités Avancées (Futur)
1. ⏳ Génération PDF dynamique
2. ⏳ Envoi de médias (graphiques)
3. ⏳ Notifications push
4. ⏳ Multi-langue

## 🧪 Tests

### Test Webhook Verification
```bash
curl -X GET "http://localhost/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=your_verify_token&hub.challenge=challenge_string"
```

### Test Message Reception
```bash
curl -X POST http://localhost/api/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "object": "whatsapp_business_account",
    "entry": [{
      "changes": [{
        "value": {
          "messages": [{
            "from": "50938123456",
            "text": {"body": "salut"}
          }]
        }
      }]
    }]
  }'
```

## 📚 Ressources

- [WhatsApp Cloud API Documentation](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Interactive Messages Guide](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages#interactive-messages)
- [Webhook Setup](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/set-up-webhooks)

## 💡 Notes Importantes

1. **Fenêtre 24h**: L'utilisateur doit initier la conversation
2. **Rate Limits**: 80 messages/seconde (mode business)
3. **Coûts**: Gratuit dans la fenêtre 24h après message utilisateur
4. **Templates**: Nécessaires pour initier conversation (hors fenêtre 24h)
5. **Médias**: Max 100MB, formats: PDF, images, vidéos, audio

## 🔍 Monitoring & Logs

Tous les événements sont loggés:
- Messages reçus
- Messages envoyés
- Erreurs API
- Accès non autorisés
- Actions utilisateur

---

**Version**: 1.0.0 (MVP)  
**Date**: 05/02/2026  
**Status**: 🚧 En développement
