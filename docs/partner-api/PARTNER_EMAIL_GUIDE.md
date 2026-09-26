# 📧 Guide des Notifications Email - Partner API

## Vue d'ensemble

Le système de Partner API envoie automatiquement des emails de notification aux citoyens lorsqu'une vérification d'identité est demandée par un service partenaire.

---

## 🎯 Quand les emails sont envoyés

### ✅ CAS 2 : Compte existant non vérifié
**Condition :** L'utilisateur a un compte mais `account_level != "verified"`

**Email envoyé :** OUI ✅  
**Destinataire :** Email de l'utilisateur  
**Objectif :** Informer qu'une vérification a été demandée et inviter à compléter la vérification

**Contenu :**
- Notification de la demande de vérification
- Nom du service partenaire
- Appel à l'action pour compléter la vérification
- Liens de téléchargement de l'app (Android + iOS)

---

### ✅ CAS 3 : Nouveau compte créé
**Condition :** Aucun compte n'existe, création automatique

**Email envoyé :** OUI ✅  
**Destinataire :** Email fourni dans le payload  
**Objectif :** Informer de la création du compte et inviter à l'activer

**Contenu :**
- Bienvenue + notification de création de compte
- Nom du service partenaire qui a initié la création
- Instructions pour télécharger l'app et activer le compte
- Liens de téléchargement de l'app (Android + iOS)

---

### ⚪ CAS 1 : Compte vérifié
**Condition :** `account_level = "verified"`

**Email envoyé :** NON ❌  
**Raison :** L'utilisateur est déjà vérifié, pas besoin de notification

---

## 📋 Classe Mailable

**Fichier :** `app/Mail/PartnerVerificationRequestedMail.php`

### Variables disponibles dans le template

```php
$userName           // "Prénom Nom" de l'utilisateur
$partnerName        // Nom du service partenaire (ex: "Banque XYZ")
$verificationDate   // Date formatée "dd/mm/YYYY à HH:ii"
$accountLevel       // "none" | "basic" | "verified"
$androidLink        // Lien Google Play Store
$iosLink            // Lien Apple App Store
```

### Subject
```
🔍 Vérification d'identité en cours - SAGAPASS
```

---

## 🎨 Template Email

**Fichier :** `resources/views/emails/partner/verification-requested.blade.php`

### Structure
```
┌─────────────────────────────────────┐
│ 🔍 Vérification d'identité en cours │ Header
├─────────────────────────────────────┤
│ Bonjour [userName]                  │
│                                     │
│ Demande via [partnerName]          │
│ le [verificationDate]               │
├─────────────────────────────────────┤
│ 📋 Statut de votre compte           │
│                                     │
│ [Message dynamique selon            │ Body
│  accountLevel]                      │
│                                     │
│ - none: ⚠️ Compte incomplet         │
│ - basic: ✅ Compte actif - Basic    │
│ - verified: ✅ Compte vérifié       │
├─────────────────────────────────────┤
│ [Boutons téléchargement app]        │
├─────────────────────────────────────┤
│ 🔒 Sécurité                         │
│                                     │
│ - Service partenaire autorisé      │
│ - Contact si non demandé            │
│ - Protection des données            │
├─────────────────────────────────────┤
│ 📞 Besoin d'aide ?                  │
│ [Bouton Contact Support]            │ Footer
└─────────────────────────────────────┘
```

---

## 🔧 Intégration dans PartnerVerifyController

### Code d'envoi (avec gestion d'erreurs)

```php
// CAS 2 : Compte existe mais pas vérifié
if ($user->email) {
    try {
        Mail::to($user->email)->send(
            new PartnerVerificationRequestedMail($user, $app->name)
        );
        Log::info('Partner verify - Email envoyé (CAS 2)', [
            'user_id' => $user->id,
            'partner' => $app->name,
        ]);
    } catch (\Exception $e) {
        Log::error('Partner verify - Échec envoi email (CAS 2)', [
            'user_id' => $user->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
```

### Points clés

1. **Vérification email** : `if ($user->email)` évite les erreurs si pas d'email
2. **Try/Catch** : Les erreurs d'email ne bloquent pas l'API
3. **Logging** : Chaque envoi (succès/échec) est loggé
4. **Non-bloquant** : L'API répond même si l'email échoue

---

## 🌍 Liens App Stores Dynamiques

Les liens de téléchargement sont récupérés depuis la table `system_settings` :

```php
'androidLink' => SystemSetting::get('app_store_android', 'https://play.google.com/...'),
'iosLink'     => SystemSetting::get('app_store_ios', 'https://apps.apple.com/...'),
```

**Avantages :**
- Modifiables sans redéploiement
- Centralisés dans la BDD
- Cache automatique (1 heure)
- Fallback values si non configurés

**Gestion :** Voir [APP_STORES_MANAGEMENT.md](./APP_STORES_MANAGEMENT.md)

---

## 📊 Logs

### Succès
```
[info] Partner verify - Email envoyé (CAS 2)
{
    "user_id": 123,
    "partner": "Banque XYZ"
}
```

### Échec
```
[error] Partner verify - Échec envoi email (CAS 3)
{
    "user_id": 456,
    "error": "Connection timeout"
}
```

---

## 🧪 Tests

### Script de test
```bash
php test-partner-email.php
```

**Tests effectués :**
1. ✅ Email CAS 2 (compte existant)
2. ✅ Email CAS 3 (nouveau compte)
3. ✅ Contenu du Mailable
4. ✅ Nombre d'emails envoyés

---

## ⚙️ Configuration SMTP

### Laravel `.env`

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io         # Exemple
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sagapass.ht
MAIL_FROM_NAME="SAGAPASS"
```

### Services recommandés

| Service | Usage | Prix |
|---------|-------|------|
| Mailtrap | Dev/Tests | Gratuit |
| SendGrid | Production | 100 emails/jour gratuit |
| Amazon SES | Production | $0.10 / 1000 emails |
| Mailgun | Production | 5000 emails/mois gratuit |

---

## 🚀 Mode Queue (Recommandé)

Pour éviter les ralentissements API, utilisez les queues :

### 1. Modifier le code

```php
Mail::to($user->email)->queue(
    new PartnerVerificationRequestedMail($user, $app->name)
);
```

### 2. Configuration

```ini
QUEUE_CONNECTION=database  # ou redis
```

### 3. Lancer le worker

```bash
php artisan queue:work
```

**Avantages :**
- API ultra-rapide (pas d'attente SMTP)
- Retry automatique en cas d'échec
- Gestion de charge

---

## 📝 Personnalisation

### Changer le subject

**Fichier :** `app/Mail/PartnerVerificationRequestedMail.php`

```php
public function build()
{
    return $this->subject('Votre nouveau subject')
                ->markdown('emails.partner.verification-requested')
                ->with([...]);
}
```

### Modifier le design

**Fichier :** `resources/views/emails/partner/verification-requested.blade.php`

Le template utilise les **composants Markdown** de Laravel :

```blade
@component('mail::message')
    # Titre

    Contenu...

    @component('mail::button', ['url' => $url])
    Texte du bouton
    @endcomponent
@endcomponent
```

**Personnaliser les couleurs :** `config/mail.php`

---

## 🔍 Debugging

### Mode log (pas d'envoi réel)

```ini
MAIL_MAILER=log
```

Les emails sont écrits dans `storage/logs/laravel.log`

### Tester un email manuellement

```bash
php artisan tinker
```

```php
$user = User::find(1);
Mail::to($user->email)->send(
    new \App\Mail\PartnerVerificationRequestedMail($user, 'Test Partner')
);
```

---

## 🛡️ Sécurité

### Points importants

1. **Pas de données sensibles** : Ne jamais inclure mots de passe, tokens dans l'email
2. **Validation email** : Laravel valide automatiquement le format
3. **Rate limiting** : Configurer dans middleware si nécessaire
4. **Logs sensibles** : Ne pas logger les emails complets

### Anti-spam

- **SPF/DKIM/DMARC** : Configurer sur votre domaine
- **Unsubscribe header** : Ajouter pour conformité
- **Throttling** : Limiter envois par utilisateur

---

## 📚 Ressources

- [Laravel Mail Documentation](https://laravel.com/docs/11.x/mail)
- [Markdown Mailables](https://laravel.com/docs/11.x/mail#markdown-mailables)
- [Queue Configuration](https://laravel.com/docs/11.x/queues)

---

## ✅ Checklist de Production

- [ ] SMTP configuré et testé
- [ ] SPF/DKIM configurés sur le domaine
- [ ] Queue worker actif (`php artisan queue:work`)
- [ ] Monitoring des logs d'emails
- [ ] Fallback values pour app stores configurés
- [ ] Rate limiting activé si nécessaire
- [ ] Tests d'envoi validés
- [ ] Template responsive vérifié

---

**Dernière mise à jour :** 30 mars 2026  
**Version API :** v1  
**Auteur :** Équipe SAGAPASS
