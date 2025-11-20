# Guide de Gestion OAuth - Administration

## 📋 Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Accès à la gestion OAuth](#accès-à-la-gestion-oauth)
3. [Fonctionnalités disponibles](#fonctionnalités-disponibles)
4. [Processus d'approbation](#processus-dapprobation)
5. [Processus de rejet](#processus-de-rejet)
6. [Suspension d'applications](#suspension-dapplications)
7. [Gestion des utilisateurs](#gestion-des-utilisateurs)
8. [Notifications email](#notifications-email)
9. [Logs d'audit](#logs-daudit)

---

## 🎯 Vue d'ensemble

Le système de gestion OAuth admin permet aux administrateurs de :
- ✅ Approuver les applications OAuth des développeurs
- ❌ Rejeter les applications non conformes
- 🚫 Suspendre les applications en cas de problème
- 👥 Voir les utilisateurs ayant autorisé chaque application
- 📊 Obtenir des statistiques détaillées sur l'utilisation OAuth
- 📧 Envoyer des notifications automatiques aux développeurs

---

## 🔐 Accès à la gestion OAuth

### Navigation

Dans le panneau d'administration, cliquez sur **"Applications OAuth"** dans le menu latéral.

- **Badge de notification** : Un badge orange indique le nombre d'applications en attente d'approbation
- **URL** : `https://votre-domaine.com/admin/oauth`

---

## 🛠️ Fonctionnalités disponibles

### 1. Tableau de bord principal

**URL** : `/admin/oauth`

#### Statistiques affichées
- 📊 **Total** : Nombre total d'applications OAuth
- ⏳ **En attente** : Applications nécessitant une révision
- ✅ **Approuvées** : Applications actives et fonctionnelles
- ❌ **Rejetées** : Applications refusées
- 🚫 **Suspendues** : Applications temporairement désactivées

#### Filtres disponibles
- **Par statut** : Filtrer par pending, approved, rejected, suspended
- **Recherche** : Rechercher par nom d'application ou email développeur
- **Tri** : Par date de création, nom, ou nombre d'utilisateurs
- **Ordre** : Croissant ou décroissant

#### Liste des applications
Chaque ligne affiche :
- Nom et description de l'application
- Informations du développeur (nom, email)
- Statut avec badge coloré
- Nombre d'utilisateurs autorisés
- Nombre de codes OAuth générés
- Date de création
- Bouton "Voir détails"

---

### 2. Page de détails d'une application

**URL** : `/admin/oauth/{application}`

#### Informations affichées

**Statut et identifiants**
- Statut actuel (avec badge)
- Client ID (UUID)
- Date de création
- Date et auteur de l'approbation (si approuvée)

**Statistiques en temps réel**
- Total utilisateurs ayant autorisé l'app
- Utilisateurs actifs (autorisations non révoquées)
- Codes OAuth générés
- Codes OAuth utilisés

**Informations développeur**
- Nom et email
- Entreprise (si disponible)
- Site web (avec lien)

**Configuration OAuth**
- URLs de redirection (redirect URIs)
- Scopes demandés (profile, email, etc.)
- Site web de l'application

**Utilisateurs autorisés**
- Liste des 10 derniers utilisateurs
- Scopes accordés par utilisateur
- Date d'autorisation
- Dernière utilisation
- Statut (active/révoquée)
- Lien vers la liste complète

#### Actions disponibles

**Si l'application est "pending"** :
- ✅ **Approuver** : Activer l'application et envoyer email au développeur
- ❌ **Rejeter** : Refuser l'application avec justification

**Si l'application est "approved"** :
- 🚫 **Suspendre** : Désactiver temporairement l'application

**Si l'application est "suspended"** :
- ▶️ **Réactiver** : Remettre l'application en service

---

## ✅ Processus d'approbation

### Étapes

1. **Accéder aux détails** de l'application en attente
2. **Vérifier** :
   - Légitimité du développeur (email vérifié, entreprise)
   - Description de l'application claire
   - URLs de redirection sécurisées (HTTPS requis)
   - Scopes demandés justifiés
   - Site web fonctionnel

3. **Cliquer sur "Approuver"**
4. **Confirmer** dans la modal

### Résultat
- ✅ Statut passe à "approved"
- 📧 Email de confirmation envoyé au développeur avec le Client ID
- 📝 Log d'audit créé avec admin_id, action, IP, user agent
- 🔓 L'application peut maintenant authentifier des utilisateurs

### Email envoyé
```
Sujet : Application OAuth Approuvée - SAGAPASS

Contenu :
- Message de félicitations
- Client ID affiché
- Rappel : Client Secret dans le dashboard
- Lien vers le dashboard développeur
- Date d'approbation
```

---

## ❌ Processus de rejet

### Étapes

1. **Accéder aux détails** de l'application
2. **Cliquer sur "Rejeter"**
3. **Remplir le formulaire** :
   - **Raison du rejet** (minimum 10 caractères, obligatoire)
   - Exemples :
     - "Les URLs de redirection ne sont pas sécurisées (HTTPS requis)"
     - "Description insuffisante, veuillez détailler l'utilisation des données"
     - "Le site web n'est pas accessible"
     - "Nom d'entreprise non vérifiable"

4. **Confirmer le rejet**

### Résultat
- ❌ Statut passe à "rejected"
- 📧 Email envoyé au développeur avec la raison
- 📝 Log d'audit créé avec la raison du rejet
- 🔒 L'application ne peut pas être utilisée
- ✏️ Le développeur peut modifier et resoumettre

### Email envoyé
```
Sujet : Application OAuth Rejetée - SAGAPASS

Contenu :
- Message expliquant le rejet
- Raison détaillée affichée dans un encadré
- Instructions pour corriger
- Lien vers l'édition de l'application
- Conseils pour la conformité
```

---

## 🚫 Suspension d'applications

### Quand suspendre ?
- ⚠️ Utilisation abusive détectée
- 🔐 Violation des conditions d'utilisation
- 🐛 Faille de sécurité découverte
- 📊 Taux d'erreur anormalement élevé
- 🚨 Plaintes d'utilisateurs multiples

### Étapes

1. **Accéder aux détails** de l'application approuvée
2. **Cliquer sur "Suspendre"**
3. **Remplir le formulaire** :
   - **Raison de la suspension** (minimum 10 caractères, obligatoire)
   - Exemples :
     - "Utilisation abusive détectée : 10 000 requêtes en 1 minute"
     - "Violation des conditions : collecte de données non autorisées"
     - "Faille de sécurité découverte, suspension temporaire"

4. **Confirmer la suspension**

### Résultat
- 🚫 Statut passe à "suspended"
- 🔓 **Toutes les autorisations utilisateurs sont révoquées automatiquement**
- 📧 Email envoyé au développeur avec la raison et l'impact
- 📝 Log d'audit créé avec nombre d'autorisations révoquées
- 🔒 Toutes les tentatives d'authentification échouent
- ❌ Les codes OAuth existants sont invalidés

### Impact immédiat
```
- Les utilisateurs sont déconnectés
- Nouveaux tokens : impossible d'obtenir
- Tokens existants : invalidés
- Tentatives OAuth : échec avec erreur "application_suspended"
```

### Email envoyé
```
Sujet : Application OAuth Suspendue - SAGAPASS

Contenu :
- Notification de suspension
- Raison détaillée affichée
- Liste des impacts :
  * Autorisations révoquées
  * Utilisateurs ne peuvent plus se connecter
  * Tentatives échoueront
  * Tokens invalidés
- Contact support pour appel
```

### Réactivation

1. **Accéder aux détails** de l'application suspendue
2. **Cliquer sur "Réactiver"**
3. **Confirmer** (après vérification que le problème est résolu)

**Résultat** :
- ✅ Statut repasse à "approved"
- 📝 Log d'audit créé
- ⚠️ **Les utilisateurs doivent réautoriser l'application** (les anciennes autorisations restent révoquées)

---

## 👥 Gestion des utilisateurs

### Page des utilisateurs d'une application

**URL** : `/admin/oauth/{application}/users`

#### Statistiques affichées
- Total autorisations
- Autorisations actives
- Autorisations révoquées

#### Liste des autorisations

Chaque ligne affiche :
- **Utilisateur** : Nom complet et email
- **Scopes autorisés** : Badges des permissions accordées (profile, email, etc.)
- **Accordé le** : Date et heure d'autorisation
- **Dernière utilisation** : Temps relatif (ex: "il y a 2 heures") ou "Jamais utilisée"
- **Statut** : Badge vert (Active) ou rouge (Révoquée)
- **Actions** : Bouton "Révoquer" (si active)

### Révoquer une autorisation utilisateur

#### Quand révoquer ?
- 🔒 Activité suspecte détectée pour un utilisateur spécifique
- 🐛 Problème de sécurité concernant un compte
- 📞 Demande de l'utilisateur

#### Étapes
1. **Cliquer sur "Révoquer"** à côté de l'autorisation
2. **Remplir le formulaire** :
   - Raison de la révocation (obligatoire)
3. **Confirmer**

#### Résultat
- ❌ Autorisation marquée comme révoquée
- 📝 Log d'audit créé avec la raison
- 🔒 L'utilisateur devra réautoriser l'application pour l'utiliser à nouveau

---

## 📧 Notifications email

### Types d'emails envoyés

#### 1. Application Approuvée
**Destinataire** : Développeur  
**Déclencheur** : Admin clique sur "Approuver"  
**Contenu** :
- Message de félicitations
- Client ID affiché clairement
- Rappel du Client Secret (disponible dans dashboard)
- Instructions pour l'intégration
- Lien vers le dashboard
- Date d'approbation

#### 2. Application Rejetée
**Destinataire** : Développeur  
**Déclencheur** : Admin clique sur "Rejeter"  
**Contenu** :
- Message de rejet
- Raison détaillée (saisie par l'admin)
- Instructions pour correction
- Lien vers l'édition de l'application
- Conseils de conformité

#### 3. Application Suspendue
**Destinataire** : Développeur  
**Déclencheur** : Admin clique sur "Suspendre"  
**Contenu** :
- Notification de suspension
- Raison détaillée
- Impact sur les utilisateurs (autorisations révoquées)
- Contact support
- Procédure d'appel

### Configuration des emails

Les templates sont dans :
```
resources/views/emails/
├── application-approved.blade.php
├── application-rejected.blade.php
└── application-suspended.blade.php
```

Les classes Mailable :
```
app/Mail/
├── ApplicationApprovedMail.php
├── ApplicationRejectedMail.php
└── ApplicationSuspendedMail.php
```

---

## 📝 Logs d'audit

Toutes les actions sont enregistrées dans la table `audit_logs` :

### Actions trackées

| Action | Description | Données loguées |
|--------|-------------|-----------------|
| `oauth_app_approved` | Application approuvée | admin_id, application_id, IP, user_agent |
| `oauth_app_rejected` | Application rejetée | admin_id, application_id, raison, IP, user_agent |
| `oauth_app_suspended` | Application suspendue | admin_id, application_id, raison, nb autorisations révoquées |
| `oauth_app_reactivated` | Application réactivée | admin_id, application_id, IP, user_agent |
| `oauth_authorization_revoked_by_admin` | Autorisation révoquée | admin_id, user_id, application_id, raison |

### Accéder aux logs

**URL** : `/admin/audit-logs`  
**Permission requise** : `view-audit-logs`

Les logs permettent de :
- 🔍 Tracer toutes les actions administratives
- 📊 Auditer la conformité
- 🐛 Déboguer les problèmes
- 📈 Analyser les tendances de modération

---

## 🔒 Bonnes pratiques

### Approbation d'applications

✅ **À vérifier** :
- [ ] Email du développeur vérifié (compte SAGAPASS actif)
- [ ] Nom d'entreprise légitime (vérifiable)
- [ ] Description claire et complète
- [ ] URLs de redirection en HTTPS uniquement
- [ ] Scopes demandés justifiés par l'usage
- [ ] Site web accessible et fonctionnel
- [ ] Pas de typosquatting (nom ressemblant à une marque connue)

❌ **Raisons de rejet courantes** :
- URLs de redirection HTTP (non sécurisées)
- Description vague ou absente
- Scopes excessifs (demande email + documents sans justification)
- Site web inaccessible ou suspect
- Développeur non vérifié

### Suspension d'applications

⚠️ **Critères de suspension** :
- Utilisation abusive (taux de requêtes excessif)
- Violation des conditions d'utilisation
- Faille de sécurité découverte
- Plaintes utilisateurs multiples
- Collecte de données non autorisées

🔍 **Avant de suspendre** :
- Vérifier les logs d'utilisation
- Contacter le développeur si possible (sauf urgence sécurité)
- Documenter la raison clairement
- Informer l'équipe support

### Révocation d'autorisations

🎯 **Utiliser avec parcimonie** :
- Privilégier la suspension globale si problème généralisé
- Révoquer individuellement seulement pour cas spécifiques
- Toujours justifier la raison

---

## 📊 Statistiques et monitoring

### Métriques clés à surveiller

**Tableau de bord OAuth** :
- Nombre d'applications en attente (action requise)
- Taux d'approbation vs rejet
- Applications suspendues (investigation nécessaire)

**Par application** :
- Nombre d'utilisateurs actifs
- Taux d'utilisation des codes OAuth
- Croissance des autorisations

**Signes d'alerte** 🚨 :
- Application avec 0 utilisateurs depuis >30 jours
- Taux de codes non utilisés >50% (possible spam)
- Croissance utilisateurs >1000/jour (vérifier légitimité)
- Aucune utilisation depuis l'approbation (application abandonnée)

---

## 🆘 Résolution de problèmes

### Problème : Email non reçu par le développeur

**Solutions** :
1. Vérifier la configuration SMTP dans `.env`
2. Consulter `storage/logs/laravel.log`
3. Vérifier que l'email du développeur est valide
4. Tester l'envoi avec `php artisan tinker` :
   ```php
   Mail::raw('Test', function($m) { $m->to('email@example.com')->subject('Test'); });
   ```

### Problème : Application suspendue mais toujours fonctionnelle

**Cause possible** : Tokens en cache  
**Solution** :
1. Vérifier le statut en BDD : `SELECT status FROM developer_applications WHERE id = ?`
2. Vérifier les autorisations : `SELECT * FROM user_authorizations WHERE application_id = ? AND revoked_at IS NULL`
3. Révoquer manuellement si nécessaire

### Problème : Développeur veut faire appel d'une suspension

**Processus** :
1. Développeur contacte support@saga-id.com
2. Support analyse la raison de suspension
3. Si justifié, admin réactive via "Réactiver"
4. Informer développeur des mesures prises

---

## 📞 Support

Pour toute question ou problème :

- **Email support** : support@saga-id.com
- **Documentation technique** : Voir `OAUTH_COMPLETE_GUIDE.md`
- **Logs d'audit** : `/admin/audit-logs`
- **Dashboard statistiques** : `/admin/statistics`

---

*Dernière mise à jour : Décembre 2025*  
*Version : 1.0*
