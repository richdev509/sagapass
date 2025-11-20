# Système de Gestion des Rôles et Permissions - SAGAPASS

## ✅ Installation Complète

Le système de gestion des rôles et permissions a été mis en place avec succès en utilisant **Spatie Laravel Permission**.

---

## 📋 Rôles Créés

### 1. **Super Admin** 👑
- **50 permissions** - Accès total au système
- **Description**: Administrateur principal avec tous les droits
- **Capacités**: 
  - Gestion complète des utilisateurs, développeurs, documents
  - Gestion des applications OAuth et scopes
  - Création et modification des rôles et permissions
  - Accès aux audit logs et statistiques
  - Gestion des paramètres système

### 2. **Admin** 🔧
- **24 permissions** - Gestion complète sauf rôles/permissions
- **Description**: Administrateur avec droits de gestion
- **Capacités**:
  - Gestion des utilisateurs et développeurs
  - Validation des documents
  - Gestion des applications OAuth
  - Approbation/rejet des demandes de scopes
  - Accès aux logs et statistiques

### 3. **Moderator** ✅
- **13 permissions** - Validation documents et utilisateurs
- **Description**: Modérateur - Validation des documents et utilisateurs
- **Capacités**:
  - Approbation/suspension des utilisateurs
  - Vérification et validation des documents
  - Approbation des développeurs et applications
  - Consultation des logs d'audit
  - Accès aux statistiques

### 4. **Support** 👁️
- **8 permissions** - Consultation uniquement
- **Description**: Support - Consultation et assistance
- **Capacités**:
  - Consultation des utilisateurs et développeurs
  - Consultation des documents et applications
  - Consultation des demandes de scopes
  - Accès en lecture aux logs
  - Consultation des statistiques

### 5. **OAuth Manager** 🔌
- **12 permissions** - Gestion OAuth uniquement
- **Description**: Gestionnaire OAuth - Applications et scopes
- **Capacités**:
  - Gestion complète des développeurs
  - Gestion des applications OAuth
  - Gestion des demandes de scopes
  - Gestion des scopes des applications
  - Accès aux logs de connexion OAuth
  - Statistiques OAuth

---

## 🔐 Catégories de Permissions

### Gestion des Utilisateurs
- `view-users` - Voir les utilisateurs
- `create-users` - Créer des utilisateurs
- `edit-users` - Modifier les utilisateurs
- `delete-users` - Supprimer les utilisateurs
- `approve-users` - Approuver les utilisateurs
- `suspend-users` - Suspendre les utilisateurs

### Gestion des Documents
- `view-documents` - Voir les documents
- `verify-documents` - Vérifier les documents
- `approve-documents` - Approuver les documents
- `reject-documents` - Rejeter les documents

### Gestion des Développeurs
- `view-developers` - Voir les développeurs
- `create-developers` - Créer des développeurs
- `edit-developers` - Modifier les développeurs
- `delete-developers` - Supprimer les développeurs
- `approve-developers` - Approuver les développeurs
- `suspend-developers` - Suspendre les développeurs

### Gestion OAuth
- `view-oauth-apps` - Voir les applications OAuth
- `create-oauth-apps` - Créer des applications OAuth
- `edit-oauth-apps` - Modifier les applications OAuth
- `delete-oauth-apps` - Supprimer les applications OAuth
- `approve-oauth-apps` - Approuver les applications OAuth
- `suspend-oauth-apps` - Suspendre les applications OAuth

### Gestion des Scopes
- `view-scope-requests` - Voir les demandes de scopes
- `approve-scope-requests` - Approuver les demandes de scopes
- `reject-scope-requests` - Rejeter les demandes de scopes
- `manage-scopes` - Gérer les scopes des applications

### Gestion des Admins
- `view-admins` - Voir les administrateurs
- `create-admins` - Créer des administrateurs
- `edit-admins` - Modifier les administrateurs
- `delete-admins` - Supprimer les administrateurs

### Rôles et Permissions
- `view-roles` - Voir les rôles
- `create-roles` - Créer des rôles
- `edit-roles` - Modifier les rôles
- `delete-roles` - Supprimer les rôles
- `assign-roles` - Attribuer des rôles
- `view-permissions` - Voir les permissions
- `assign-permissions` - Attribuer des permissions

### Audit et Logs
- `view-audit-logs` - Voir les logs d'audit
- `view-connection-logs` - Voir les logs de connexion OAuth

### Statistiques
- `view-statistics` - Voir les statistiques
- `view-reports` - Voir les rapports

### Paramètres
- `manage-settings` - Gérer les paramètres système
- `manage-emails` - Gérer les emails

---

## 🚀 Commandes Artisan

### Attribuer le rôle Super Admin
```bash
php artisan admin:make-super email@exemple.com
```

### Réinitialiser le cache des permissions
```bash
php artisan permission:cache-reset
```

### Créer les rôles et permissions (seeder)
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## 📍 Routes Créées

### Gestion des Rôles
- `GET /admin/roles` - Liste des rôles
- `GET /admin/roles/create` - Formulaire de création
- `POST /admin/roles` - Enregistrer un nouveau rôle
- `GET /admin/roles/{role}/edit` - Formulaire d'édition
- `PUT /admin/roles/{role}` - Mettre à jour un rôle
- `DELETE /admin/roles/{role}` - Supprimer un rôle

### Gestion des Permissions
- `GET /admin/permissions` - Liste des permissions

### Attribution de Rôles
- `GET /admin/admins/{admin}/roles` - Gérer les rôles d'un admin
- `POST /admin/admins/{admin}/roles` - Attribuer des rôles

---

## 💻 Utilisation dans le Code

### Vérifier une Permission
```php
// Dans un contrôleur
$this->authorize('view-users');

// Dans une vue Blade
@can('view-users')
    <!-- Contenu visible uniquement avec la permission -->
@endcan

// Dans un middleware de route
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:view-users');
```

### Vérifier un Rôle
```php
// Dans un contrôleur
if (auth('admin')->user()->hasRole('super-admin')) {
    // Code pour super admin
}

// Dans une vue Blade
@role('super-admin')
    <!-- Contenu visible uniquement pour super-admin -->
@endrole
```

### Attribuer des Rôles/Permissions
```php
// Attribuer un rôle
$admin->assignRole('moderator');

// Attribuer plusieurs rôles
$admin->assignRole(['moderator', 'support']);

// Synchroniser les rôles (remplace tous les rôles existants)
$admin->syncRoles(['admin']);

// Retirer un rôle
$admin->removeRole('moderator');

// Attribuer une permission directe
$admin->givePermissionTo('view-statistics');
```

---

## 🎨 Interface Admin

### Nouveau Menu "Rôles & Permissions"
Un nouveau lien a été ajouté dans le menu latéral admin :
- **Icône**: 🛡️ Bouclier
- **Accessible**: Uniquement aux utilisateurs avec la permission `view-roles`
- **Fonctionnalités**:
  - Liste des rôles avec nombre de permissions
  - Création de nouveaux rôles personnalisés
  - Modification des permissions par rôle
  - Suppression de rôles (sauf super-admin)
  - Consultation de toutes les permissions système

### Gestion des Admins
- Bouton "Gérer les rôles" ajouté pour chaque admin
- Interface pour attribuer/retirer des rôles
- Affichage des rôles actuels de chaque admin

---

## 🔒 Sécurité

### Protections Implémentées
1. **Rôle Super-Admin protégé** - Ne peut pas être modifié ou supprimé
2. **Audit logging** - Toutes les modifications de rôles sont enregistrées
3. **Vérification avant suppression** - Impossible de supprimer un rôle attribué à des admins
4. **Middleware de permissions** - Protection des routes sensibles
5. **Authorization Gates** - Vérifications au niveau contrôleur

---

## 📊 Statistiques

- **5 rôles pré-configurés**
- **50 permissions définies**
- **Interface complète de gestion**
- **Audit logging activé**
- **Cache des permissions optimisé**

---

## ✨ Prochaines Étapes Recommandées

1. **Créer d'autres admins** et leur attribuer des rôles appropriés
2. **Tester les permissions** avec différents rôles
3. **Personnaliser les rôles** selon vos besoins spécifiques
4. **Créer des rôles supplémentaires** si nécessaire
5. **Former les administrateurs** sur l'utilisation du système

---

## 🆘 Support

### Problèmes Courants

**Permission refusée (403)**
- Vérifier que l'admin a le bon rôle
- Vérifier que le rôle a la bonne permission
- Vider le cache: `php artisan permission:cache-reset`

**Rôle non trouvé**
- Exécuter le seeder: `php artisan db:seed --class=RolesAndPermissionsSeeder`

**Permission non reconnue**
- Vérifier l'orthographe exacte de la permission
- Vider le cache des permissions

---

## 📝 Notes Importantes

- Le premier admin créé doit se voir attribuer le rôle `super-admin` manuellement
- Les permissions sont cumulatives (un admin peut avoir plusieurs rôles)
- Le guard name est `admin` pour toutes les permissions et rôles
- Les modifications de permissions nécessitent parfois de vider le cache

---

**Date de mise en place**: 20 Novembre 2025  
**Version Laravel**: 12.0  
**Package utilisé**: spatie/laravel-permission v6.23
