# 📚 Index de la Documentation SAGAPASS

**Dernière mise à jour** : 20 novembre 2025

Ce document répertorie toute la documentation disponible pour le projet SAGAPASS.

---

## 🎯 Documentation Principale

### Pour les Développeurs d'Applications

| Document | Description | Lignes | Statut |
|----------|-------------|--------|--------|
| **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** | 📘 **Documentation API complète** - Guide complet pour intégrer SAGAPASS | 800+ | ✅ |

**Contenu** :
- Guide OAuth 2.0 Authorization Code Flow + PKCE
- 2 endpoints API détaillés (`/api/v1/user`, `/api/v1/user/documents`)
- Tous les scopes et permissions
- Exemples de code en JavaScript/Node.js, PHP/Laravel, Python/Flask
- Codes d'erreur et gestion des erreurs
- Limites et quotas
- Flow d'authentification étape par étape

### Pour les Administrateurs

| Document | Description | Lignes | Statut |
|----------|-------------|--------|--------|
| **[ADMIN_OAUTH_GUIDE.md](ADMIN_OAUTH_GUIDE.md)** | Guide complet pour gérer les applications OAuth | 300+ | ✅ |
| **[ROLES_PERMISSIONS_GUIDE.md](ROLES_PERMISSIONS_GUIDE.md)** | Documentation du système de rôles et permissions | 400+ | ✅ |

**Contenu ADMIN_OAUTH_GUIDE** :
- Approuver/Rejeter des applications
- Gérer les scopes
- Suspendre des applications
- Consulter les statistiques
- Gérer les utilisateurs connectés

**Contenu ROLES_PERMISSIONS_GUIDE** :
- 5 rôles prédéfinis (super-admin, admin, moderator, support, oauth-manager)
- 50 permissions organisées en 11 catégories
- Guide d'utilisation des commandes Artisan
- Exemples d'utilisation dans le code

---

## 🧪 Documentation Technique

### Guides de Développement OAuth

| Document | Description | Statut |
|----------|-------------|--------|
| **[OAUTH_COMPLETE_GUIDE.md](OAUTH_COMPLETE_GUIDE.md)** | Guide OAuth complet pour développeurs | ✅ |
| **[OAUTH_IMPLEMENTATION.md](OAUTH_IMPLEMENTATION.md)** | Détails d'implémentation technique | ✅ |
| **[OAUTH_SUMMARY.md](OAUTH_SUMMARY.md)** | Résumé des fonctionnalités OAuth | ✅ |

### Guides de Tests

| Document | Description | Statut |
|----------|-------------|--------|
| **[GUIDE_TEST_API_LOCAL.md](GUIDE_TEST_API_LOCAL.md)** | Guide pour tester l'API en local | ✅ |
| **[TESTS_API_OAUTH.md](TESTS_API_OAUTH.md)** | Procédures de tests OAuth | ✅ |
| **[QUICK_TEST_GUIDE.md](QUICK_TEST_GUIDE.md)** | Guide de tests rapides | ✅ |
| **[TEST_OAUTH_LOGIN.md](TEST_OAUTH_LOGIN.md)** | Tests de connexion OAuth | ✅ |
| **[STATISTIQUES_TEST.md](STATISTIQUES_TEST.md)** | Tests des statistiques | ✅ |

### Statut du Projet

| Document | Description | Statut |
|----------|-------------|--------|
| **[STATUT_FINAL_API_OAUTH.md](STATUT_FINAL_API_OAUTH.md)** | Statut global du système OAuth & API | ✅ |
| **[IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)** | Statut d'implémentation des fonctionnalités | ✅ |
| **[CHANGELOG.md](CHANGELOG.md)** | Historique des changements | ✅ |

---

## 🚀 Démarrage Rapide

### Pour Développeur d'Application (5 minutes)

1. **Lire** : [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Section "Authentification OAuth 2.0"
2. **Créer** : Compte développeur sur `/developers/register`
3. **Créer** : Application OAuth sur `/developers/applications/create`
4. **Attendre** : Approbation par l'administrateur
5. **Intégrer** : Suivre les exemples de code dans la documentation API

### Pour Administrateur (2 minutes)

1. **Lire** : [ADMIN_OAUTH_GUIDE.md](ADMIN_OAUTH_GUIDE.md)
2. **Connecter** : `/admin/login` avec compte super-admin
3. **Gérer** : `/admin/oauth` pour approuver/rejeter applications
4. **Configurer** : Rôles et permissions via `/admin/roles`

### Pour Testeur (10 minutes)

1. **Lire** : [QUICK_TEST_GUIDE.md](QUICK_TEST_GUIDE.md)
2. **Suivre** : [GUIDE_TEST_API_LOCAL.md](GUIDE_TEST_API_LOCAL.md)
3. **Tester** : Flow complet avec les exemples fournis

---

## 📊 Vue d'ensemble du Système

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     SAGAPASS Platform                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐      ┌──────────────┐      ┌───────────┐ │
│  │   Citoyens   │      │ Développeurs │      │   Admins  │ │
│  │  (Utilisateurs)      │ (Applications)      │ (Gestion) │ │
│  └──────┬───────┘      └──────┬───────┘      └─────┬─────┘ │
│         │                     │                     │        │
│         │                     │                     │        │
│  ┌──────▼──────────────────────▼─────────────────▼──────┐  │
│  │           Routes Web & API                            │  │
│  │  /register  /login  /documents  /oauth  /api/v1      │  │
│  └───────────────────────────┬───────────────────────────┘  │
│                              │                               │
│  ┌───────────────────────────▼───────────────────────────┐  │
│  │              Controllers                               │  │
│  │  Auth • Document • OAuth • API • Admin               │  │
│  └───────────────────────────┬───────────────────────────┘  │
│                              │                               │
│  ┌───────────────────────────▼───────────────────────────┐  │
│  │               Modèles & Base de données               │  │
│  │  Users • Documents • DeveloperApplications           │  │
│  │  OAuthAuthorizationCodes • UserAuthorizations        │  │
│  │  Admins • Roles • Permissions • AuditLogs           │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Fonctionnalités Principales

#### 🆔 Gestion d'Identité
- ✅ Inscription/Connexion citoyens
- ✅ Vérification email
- ✅ Upload et vérification de documents (CNI, Passeport)
- ✅ Champ `card_number` pour cartes nationales (9 caractères alphanumériques)
- ✅ Profils utilisateurs complets

#### 🔐 OAuth 2.0 & API
- ✅ Authorization Code Flow avec PKCE
- ✅ Gestion des scopes (profile, email, phone, address, documents)
- ✅ Tokens sécurisés (Sanctum)
- ✅ 2 endpoints API protégés
- ✅ Révocation et introspection de tokens

#### 👨‍💼 Administration
- ✅ Système de rôles et permissions (Spatie)
- ✅ 5 rôles prédéfinis (50 permissions)
- ✅ Gestion des applications OAuth
- ✅ Approbation/Rejet/Suspension
- ✅ Statistiques détaillées
- ✅ Logs d'audit complets
- ✅ Gestion des demandes de scopes

#### 👨‍💻 Espace Développeur
- ✅ Création d'applications OAuth
- ✅ Gestion des clés API (Client ID/Secret)
- ✅ Demande de scopes supplémentaires
- ✅ Documentation intégrée
- ✅ Logs de connexion

#### 👤 Espace Utilisateur
- ✅ Gestion des documents
- ✅ Services connectés (applications autorisées)
- ✅ Révocation d'accès
- ✅ Historique des connexions OAuth

---

## 🔑 Concepts Clés

### Scopes (Permissions)

| Scope | Données | Sensibilité |
|-------|---------|-------------|
| `profile` | Nom, prénom, statut vérification | 🟢 Faible |
| `email` | Email, date vérification | 🟡 Moyenne |
| `phone` | Numéro de téléphone | 🟡 Moyenne |
| `address` | Adresse postale | 🟡 Moyenne |
| `documents` | Documents vérifiés (masqués) | 🔴 Élevée |

### Rôles Administrateurs

| Rôle | Permissions | Usage |
|------|-------------|-------|
| **super-admin** | 50 permissions | Administration complète |
| **admin** | 24 permissions | Gestion utilisateurs et documents |
| **moderator** | 13 permissions | Vérification documents |
| **support** | 8 permissions | Support utilisateurs |
| **oauth-manager** | 12 permissions | Gestion applications OAuth |

### Types de Documents

| Type | Code | Champs spécifiques | Validation |
|------|------|-------------------|------------|
| **Carte Nationale** | `cni` | `card_number` (9 caractères alphanumériques), NIU (10 chiffres) | Recto + Verso obligatoires |
| **Passeport** | `passport` | Numéro alphanumérique (6-20 caractères) | Recto obligatoire, Verso optionnel |

---

## 🛠️ Configuration Technique

### Prérequis

- PHP 8.2+
- Laravel 11.x
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (pour assets)

### Installation

```bash
# Cloner le projet
git clone [repository-url]
cd saga-id

# Installer dépendances
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate
php artisan db:seed

# Créer le super-admin
php artisan admin:make-super admin@sagapass.com

# Compiler assets
npm run build

# Démarrer serveur
php artisan serve
```

### Commandes Utiles

```bash
# Gestion des permissions
php artisan admin:list-permissions
php artisan admin:reset-permissions
php artisan admin:make-super {email}

# Cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Routes
php artisan route:list --path=api
php artisan route:list --path=oauth
```

---

## 📧 Support & Contribution

### Obtenir de l'aide

- **Documentation API** : Commencer par [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Questions Admin** : Consulter [ADMIN_OAUTH_GUIDE.md](ADMIN_OAUTH_GUIDE.md)
- **Problèmes techniques** : Voir les guides de tests
- **Email support** : support@sagapass.com

### Signaler un bug

1. Vérifier la documentation pertinente
2. Consulter [STATUT_FINAL_API_OAUTH.md](STATUT_FINAL_API_OAUTH.md)
3. Créer un ticket avec :
   - Description du problème
   - Étapes pour reproduire
   - Logs pertinents (`storage/logs/laravel.log`)
   - Environnement (dev/prod)

### Sécurité

**Vulnérabilités de sécurité** : Envoyer à `security@sagapass.com`
- ⚠️ Ne pas publier publiquement
- Réponse garantie sous 24h
- Confidentialité assurée

---

## 📝 Licence & Crédits

**Projet** : SAGAPASS - Système d'Authentification et Gestion d'Accès pour l'Identification Digitale

**Développement** : 2025

**Technologies principales** :
- Laravel 11.x (Framework PHP)
- Sanctum (Authentification API)
- Spatie Laravel Permission (Gestion des rôles)
- OAuth 2.0 Protocol
- MySQL (Base de données)
- Bootstrap 5 (UI Framework)

**Documentation** : 
- GitHub Copilot (AI Assistant)
- Équipe SAGAPASS

---

## 🎯 Roadmap

### Prochaines fonctionnalités

- [ ] Refresh tokens OAuth
- [ ] Webhooks pour événements
- [ ] Rate limiting avancé
- [ ] API v2 avec GraphQL
- [ ] SDK officiels (JavaScript, PHP, Python)
- [ ] Authentification à deux facteurs (2FA)
- [ ] Reconnaissance faciale pour vérification
- [ ] Support multi-langues (EN, ES)

### Améliorations prévues

- [ ] Dashboard analytics pour développeurs
- [ ] Tests automatisés (PHPUnit)
- [ ] CI/CD Pipeline
- [ ] Docker configuration
- [ ] API monitoring et alertes
- [ ] Documentation interactive (Swagger/OpenAPI)

---

**Dernière mise à jour** : 20 novembre 2025  
**Version documentation** : 1.0  
**Statut** : ✅ Production Ready

*Cette documentation évolue avec le projet. Consultez régulièrement pour les mises à jour.*
