# Guide de Gestion des Citoyens

## Vue d'ensemble

Système complet de gestion des citoyens avec recherche avancée, profil détaillé et permissions granulaires.

## Fichiers créés

### 1. Contrôleur CitizenController
**Fichier:** `app/Http/Controllers/Admin/CitizenController.php`

**Méthodes disponibles:**
- `index(Request $request)` - Liste et recherche des citoyens avec filtres avancés
- `show($id)` - Affichage du profil complet d'un citoyen
- `update(Request $request, $id)` - Mise à jour des informations
- `suspend(Request $request, $id)` - Suspension de compte
- `activate(Request $request, $id)` - Réactivation de compte
- `resetPassword(Request $request, $id)` - Réinitialisation de mot de passe
- `search(Request $request)` - Recherche AJAX pour autocomplete
- `export(Request $request)` - Export CSV des citoyens

### 2. Vues Blade

**Vue principale:** `resources/views/admin/citizens/index.blade.php`
- Statistiques (Total, Vérifiés, Actifs, Développeurs)
- Formulaire de recherche avec filtres multiples
- Tableau responsive avec actions conditionnelles
- Modal de suspension avec raison

**Vue profil:** `resources/views/admin/citizens/show.blade.php`
- En-tête avec photo et informations clés
- 4 statistiques rapides (documents, vérifiés, en attente, OAuth)
- Onglets:
  - Informations personnelles
  - Documents (avec liens vers vérification)
  - Autorisations OAuth
  - Informations développeur (si applicable)
  - Activité (logs d'audit)
- Modals: Édition, Suspension, Réinitialisation mot de passe

### 3. Routes
**Fichier:** `routes/admin.php`

Toutes les routes sous le préfixe `/admin/citizens`:
```php
GET  /admin/citizens              - Liste/recherche (permission: view-users)
GET  /admin/citizens/search       - AJAX search (permission: search-users)
GET  /admin/citizens/export       - Export CSV (permission: export-users)
GET  /admin/citizens/{id}         - Profil détaillé (permission: view-user-details)
PUT  /admin/citizens/{id}         - Mise à jour (permission: edit-users)
POST /admin/citizens/{id}/suspend - Suspendre (permission: suspend-users)
POST /admin/citizens/{id}/activate - Activer (permission: activate-users)
POST /admin/citizens/{id}/reset-password - Reset password (permission: reset-user-password)
```

### 4. Permissions ajoutées

11 nouvelles permissions créées:
- `view-users` - Voir les utilisateurs
- `search-users` - Rechercher des utilisateurs
- `view-user-details` - Voir les détails complets d'un utilisateur
- `create-users` - Créer des utilisateurs
- `edit-users` - Modifier les utilisateurs
- `delete-users` - Supprimer les utilisateurs
- `approve-users` - Approuver les utilisateurs
- `suspend-users` - Suspendre les utilisateurs
- `activate-users` - Activer les utilisateurs
- `export-users` - Exporter les données utilisateurs
- `reset-user-password` - Réinitialiser le mot de passe utilisateur

### 5. Attribution des permissions par rôle

**Super Admin (61 permissions):**
- ✅ Toutes les permissions (gestion complète)

**Admin (31 permissions):**
- ✅ view-users, search-users, view-user-details
- ✅ edit-users, approve-users, suspend-users, activate-users
- ✅ reset-user-password, export-users
- ❌ delete-users (réservé au super-admin)

**Moderator (16 permissions):**
- ✅ view-users, search-users, view-user-details
- ✅ approve-users, suspend-users, activate-users
- ❌ edit-users, delete-users, export-users, reset-password

**Support (10 permissions):**
- ✅ view-users, search-users, view-user-details
- ❌ Aucune action de modification

**OAuth Manager (12 permissions):**
- ❌ Aucune permission citoyens (focus OAuth uniquement)

**Cyber Admin (9 permissions):**
- ❌ Aucune permission citoyens (focus sécurité uniquement)

### 6. Menu Admin
**Fichier:** `resources/views/admin/layouts/admin.blade.php`

Nouvel élément de menu ajouté:
```blade
@can('view-users', 'admin')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.citizens.*') ? 'active' : '' }}" 
       href="{{ route('admin.citizens.index') }}">
        <i class="bi bi-people-fill"></i>
        <span>Gestion Citoyens</span>
    </a>
</li>
@endcan
```

## Fonctionnalités de recherche

### Critères de recherche disponibles:

1. **Recherche globale (texte libre):**
   - Prénom
   - Nom
   - Email
   - Téléphone
   - Adresse

2. **Filtres:**
   - Statut de vérification (pending, verified, rejected)
   - Statut du compte (active, suspended, inactive)
   - Email vérifié (oui/non)
   - Type (développeur/non-développeur)

3. **Tri:**
   - Par date d'inscription
   - Par prénom
   - Par nom
   - Par email
   - Ordre croissant/décroissant

4. **Pagination:**
   - 50 résultats par page
   - Navigation complète avec Laravel

## Actions disponibles

### Actions conditionnelles par permission:

1. **Voir le profil** (view-user-details)
   - Affichage complet de toutes les informations
   - Onglets multiples avec données relationnelles

2. **Modifier** (edit-users)
   - Prénom, nom, email, téléphone
   - Date de naissance, adresse

3. **Suspendre** (suspend-users)
   - Avec raison obligatoire
   - Logged dans l'audit

4. **Activer** (activate-users)
   - Réactivation de comptes suspendus
   - Logged dans l'audit

5. **Réinitialiser mot de passe** (reset-user-password)
   - Nouveau mot de passe avec confirmation
   - Minimum 8 caractères
   - Logged dans l'audit

6. **Exporter** (export-users)
   - Export CSV avec tous les critères de filtre appliqués
   - Nom de fichier: `citoyens_YYYY-MM-DD_HHMMSS.csv`

## Sécurité et audit

Toutes les actions sont loggées dans `audit_logs`:
- Suspension de compte (avec raison)
- Réactivation de compte
- Modification d'informations
- Réinitialisation de mot de passe

Format du log:
```php
AuditLog::create([
    'admin_id' => auth('admin')->id(),
    'user_id' => $citizen->id,
    'action' => 'action_name',
    'model_type' => 'User',
    'model_id' => $citizen->id,
    'description' => "Description de l'action",
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

## Commandes d'installation

```bash
# 1. Le seeder a déjà été exécuté avec succès
php artisan db:seed --class=RolesAndPermissionsSeeder
# ✅ Résultat: 61 permissions super-admin, 31 admin, 16 moderator, 10 support

# 2. Nettoyer les caches (à exécuter)
php artisan permission:cache-reset
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize
```

## Utilisation

### Pour accéder à la gestion des citoyens:

1. **Se connecter en tant qu'admin** avec au moins la permission `view-users`

2. **Accéder au menu:**
   - Cliquer sur "Gestion Citoyens" dans la sidebar

3. **Rechercher un citoyen:**
   - Utiliser la barre de recherche globale OU
   - Appliquer des filtres spécifiques

4. **Voir un profil:**
   - Cliquer sur l'icône œil dans la colonne Actions
   - Explorer les différents onglets

5. **Effectuer des actions:**
   - Actions disponibles selon vos permissions
   - Toutes les actions sont confirmées par modals

## Intégration avec les systèmes existants

### Relations utilisées:

1. **Documents** (`Document` model)
   - Affichage des documents uploadés
   - Lien vers le système de vérification

2. **Autorisations OAuth** (`Consent` model)
   - Applications autorisées par le citoyen
   - Scopes accordés

3. **Logs d'audit** (`AuditLog` model)
   - Historique complet des actions
   - Traçabilité des modifications

4. **Tokens API** (`PersonalAccessToken`)
   - Tokens d'accès personnel (si implémenté)

## Interface utilisateur

### Design:
- **Framework:** Bootstrap 5.3
- **Icônes:** Bootstrap Icons + Font Awesome
- **Couleurs:** Gradient violet/bleu (cohérent avec le design existant)
- **Responsive:** Entièrement adaptatif mobile/tablette/desktop

### Composants:
- Cartes statistiques avec icônes et gradients
- Tableaux avec hover et tri
- Badges colorés pour les statuts
- Modals pour les actions critiques
- Formulaires avec validation

## Prochaines étapes

1. **Tester le système:**
   ```bash
   # Vérifier les routes
   php artisan route:list --name=citizens
   
   # Tester les permissions
   php artisan tinker
   Admin::find(1)->can('view-users')  # true pour super-admin
   ```

2. **Accéder à l'interface:**
   - URL locale: `http://localhost/admin/citizens`
   - URL production: `https://sagapass.com/admin/citizens`

3. **Vérifier les permissions:**
   - Se connecter avec différents rôles
   - Vérifier que les boutons/menus apparaissent selon les permissions

4. **Tester les fonctionnalités:**
   - Recherche par nom, email, téléphone
   - Filtres multiples
   - Export CSV
   - Actions de suspension/activation
   - Modification d'informations
   - Réinitialisation de mot de passe

## Dépannage

### Problème: Menu "Gestion Citoyens" n'apparaît pas
**Solution:**
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

### Problème: Erreur 403 (Forbidden)
**Solution:** Vérifier que l'admin a la permission `view-users`
```bash
php artisan tinker
$admin = Admin::find(ID);
$admin->givePermissionTo('view-users');
```

### Problème: Routes non trouvées
**Solution:**
```bash
php artisan route:clear
php artisan optimize
```

## Notes importantes

1. **Pas de suppression de citoyens par défaut**
   - La permission `delete-users` existe mais n'est pas implémentée dans le contrôleur
   - Considérer la soft delete ou l'archivage plutôt que la suppression définitive

2. **Validation des emails uniques**
   - Lors de la modification, l'email doit rester unique
   - Validation: `'email' => 'required|email|unique:users,email,' . $citizen->id`

3. **Audit complet**
   - Toutes les actions sensibles sont loggées
   - Traçabilité complète pour la conformité RGPD

4. **Export CSV**
   - Les filtres appliqués dans la recherche sont pris en compte dans l'export
   - Format UTF-8 pour les caractères spéciaux

## Conformité RGPD

Le système respecte les principes RGPD:
- ✅ Traçabilité des accès (audit logs)
- ✅ Limitation des permissions (principe du moindre privilège)
- ✅ Export de données (droit à la portabilité)
- ✅ Possibilité de suspension (droit à l'effacement)
- ✅ Logs des modifications (accountability)

## Support

Pour toute question ou problème:
1. Vérifier les logs: `storage/logs/laravel.log`
2. Vérifier les permissions: Table `permissions` et `model_has_permissions`
3. Vérifier l'audit: Table `audit_logs` pour voir l'historique des actions
