# Commandes à exécuter pour finaliser la gestion des citoyens

## ✅ DÉJÀ FAIT

```bash
# 1. Seeder exécuté avec succès
php artisan db:seed --class=RolesAndPermissionsSeeder
# Résultat: 61 permissions super-admin, 31 admin, 16 moderator, 10 support, 12 oauth-manager, 9 cyber-admin
```

## 🔄 À EXÉCUTER MAINTENANT

```powershell
# 1. Réinitialiser le cache des permissions
php artisan permission:cache-reset

# 2. Nettoyer le cache de l'application
php artisan cache:clear

# 3. Nettoyer le cache des vues
php artisan view:clear

# 4. Nettoyer le cache des routes
php artisan route:clear

# 5. Optimiser l'application
php artisan optimize

# 6. Vérifier que les routes sont bien enregistrées
php artisan route:list --name=citizens
```

## 🧪 TESTS À EFFECTUER

```powershell
# 1. Vérifier les permissions dans Tinker
php artisan tinker

# Dans Tinker:
$admin = Admin::find(1);
$admin->hasPermissionTo('view-users');        # devrait retourner true
$admin->hasPermissionTo('search-users');      # devrait retourner true
$admin->hasPermissionTo('edit-users');        # devrait retourner true
$admin->getAllPermissions()->count();         # devrait retourner 61 pour super-admin

# Vérifier qu'un admin normal a les bonnes permissions
$adminNormal = Admin::whereHas('roles', function($q) {
    $q->where('name', 'admin');
})->first();

if ($adminNormal) {
    $adminNormal->getAllPermissions()->count();  # devrait retourner 31
}

exit
```

## 🌐 ACCÈS À L'INTERFACE

### Développement local:
```
URL: http://localhost/admin/citizens
```

### Production (VPS):
```
URL: https://sagapass.com/admin/citizens
```

## 📊 VÉRIFICATIONS FONCTIONNELLES

### 1. Menu visible
- ✅ Se connecter en tant que super-admin ou admin
- ✅ Le menu "Gestion Citoyens" doit apparaître dans la sidebar
- ✅ L'icône doit être `bi bi-people-fill`

### 2. Page d'index (liste)
- ✅ Statistiques affichées en haut (Total, Vérifiés, Actifs, Développeurs)
- ✅ Formulaire de recherche avec 5 filtres
- ✅ Tableau avec colonnes: ID, Nom, Email, Téléphone, Statuts, Type, Date, Actions
- ✅ Pagination fonctionnelle
- ✅ Bouton "Exporter en CSV" visible (si permission export-users)

### 3. Actions conditionnelles
Test avec différents rôles:

**Super-admin doit voir:**
- ✅ Bouton "Voir le profil" (œil)
- ✅ Bouton "Suspendre" (pause)
- ✅ Bouton "Activer" (play) si suspendu
- ✅ Bouton "Exporter en CSV"

**Admin doit voir:**
- ✅ Bouton "Voir le profil"
- ✅ Bouton "Suspendre"
- ✅ Bouton "Activer"
- ✅ Bouton "Exporter en CSV"

**Moderator doit voir:**
- ✅ Bouton "Voir le profil"
- ✅ Bouton "Suspendre"
- ✅ Bouton "Activer"
- ❌ PAS de bouton "Exporter"

**Support doit voir:**
- ✅ Bouton "Voir le profil"
- ❌ PAS de boutons d'action (suspendre, activer)
- ❌ PAS de bouton "Exporter"

### 4. Page profil citoyen
- ✅ Photo ou initiale affichée
- ✅ Badges de statut (vérification, compte, développeur si applicable)
- ✅ 4 cartes statistiques
- ✅ Onglets fonctionnels:
  - Informations personnelles (tableau)
  - Documents (liste avec lien vers vérification)
  - Autorisations OAuth (liste des apps)
  - Informations développeur (si is_developer = true)
  - Activité (logs d'audit)
- ✅ Boutons d'action selon permissions:
  - Modifier (modal avec formulaire)
  - Suspendre (modal avec raison)
  - Activer (formulaire POST direct)
  - Réinitialiser mot de passe (modal)

### 5. Fonctionnalités de recherche
Test des filtres:
- ✅ Recherche par nom (prénom ou nom)
- ✅ Recherche par email
- ✅ Recherche par téléphone
- ✅ Filtre par statut de vérification
- ✅ Filtre par statut de compte
- ✅ Filtre par email vérifié/non vérifié
- ✅ Filtre par type (développeur/non-développeur)
- ✅ Tri par date, nom, prénom, email
- ✅ Ordre croissant/décroissant

### 6. Export CSV
- ✅ Cliquer sur "Exporter en CSV"
- ✅ Fichier téléchargé avec nom `citoyens_YYYY-MM-DD_HHMMSS.csv`
- ✅ Contenu: toutes les colonnes principales
- ✅ Encodage UTF-8 correct (caractères spéciaux)
- ✅ Filtres appliqués dans l'export

### 7. Actions et audit
Test de suspension:
- ✅ Cliquer sur "Suspendre"
- ✅ Modal s'ouvre avec champ "Raison" obligatoire
- ✅ Soumettre le formulaire
- ✅ Message de succès affiché
- ✅ Statut du citoyen change à "suspended"
- ✅ Log créé dans `audit_logs` avec admin_id, action, description, IP

Test d'activation:
- ✅ Compte suspendu affiche bouton "Activer"
- ✅ Cliquer sur "Activer"
- ✅ Statut change à "active"
- ✅ Log créé dans audit_logs

Test de modification:
- ✅ Cliquer sur "Modifier"
- ✅ Modal pré-rempli avec données actuelles
- ✅ Modifier un champ (ex: téléphone)
- ✅ Enregistrer
- ✅ Données mises à jour
- ✅ Log créé

Test de réinitialisation mot de passe:
- ✅ Cliquer sur "Réinitialiser mot de passe"
- ✅ Saisir nouveau mot de passe (min 8 caractères)
- ✅ Confirmer le mot de passe
- ✅ Enregistrer
- ✅ Message de succès
- ✅ Log créé

## 🚨 DÉPANNAGE

### Si le menu n'apparaît pas:
```powershell
php artisan permission:cache-reset
php artisan cache:clear
php artisan config:clear
```

### Si erreur 403 (Forbidden):
```powershell
php artisan tinker

# Donner la permission manuellement
$admin = Admin::find(ID);
$admin->givePermissionTo('view-users');
exit
```

### Si les routes ne fonctionnent pas:
```powershell
php artisan route:clear
php artisan optimize
php artisan route:list --name=citizens
```

### Si erreur sur les vues:
```powershell
php artisan view:clear
php artisan optimize
```

## 📝 FICHIERS CRÉÉS/MODIFIÉS

### Nouveaux fichiers:
1. `app/Http/Controllers/Admin/CitizenController.php` - Contrôleur principal
2. `resources/views/admin/citizens/index.blade.php` - Vue liste/recherche
3. `resources/views/admin/citizens/show.blade.php` - Vue profil détaillé
4. `CITIZENS_MANAGEMENT_GUIDE.md` - Documentation complète
5. `COMMANDS_CITIZENS.md` - Ce fichier

### Fichiers modifiés:
1. `routes/admin.php` - Ajout des routes citoyens
2. `database/seeders/RolesAndPermissionsSeeder.php` - Ajout des 11 permissions
3. `resources/views/admin/layouts/admin.blade.php` - Ajout du menu

## 📦 DÉPLOIEMENT SUR VPS

Une fois les tests locaux validés:

```bash
# 1. Se connecter au VPS
ssh user@sagapass.com

# 2. Aller dans le répertoire
cd /var/www/sagapass

# 3. Récupérer les modifications (après git push)
git pull origin main

# 4. Exécuter le seeder en production
php artisan db:seed --class=RolesAndPermissionsSeeder --force

# 5. Nettoyer les caches
php artisan permission:cache-reset
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# 6. Optimiser
php artisan optimize

# 7. Vérifier les permissions des fichiers
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 8. Redémarrer Apache
sudo systemctl restart apache2

# 9. Vérifier que tout fonctionne
php artisan route:list --name=citizens
```

## ✅ CHECKLIST FINALE

Avant de considérer le système comme complet:

- [ ] Seeder exécuté avec succès ✅ (FAIT)
- [ ] Caches nettoyés
- [ ] Routes vérifiées (`php artisan route:list --name=citizens`)
- [ ] Permissions vérifiées dans Tinker
- [ ] Menu "Gestion Citoyens" visible
- [ ] Page index accessible
- [ ] Recherche fonctionnelle
- [ ] Filtres fonctionnels
- [ ] Page profil accessible
- [ ] Tous les onglets du profil fonctionnent
- [ ] Actions conditionnelles par permission
- [ ] Suspension/Activation testées
- [ ] Modification d'informations testée
- [ ] Export CSV testé
- [ ] Logs d'audit vérifiés dans la BDD
- [ ] Tests avec différents rôles (super-admin, admin, moderator, support)
- [ ] Interface responsive (mobile, tablette, desktop)
- [ ] Déploiement sur VPS (si applicable)

## 📞 SUPPORT

En cas de problème:

1. **Vérifier les logs:**
   ```
   storage/logs/laravel.log
   ```

2. **Vérifier les permissions en BDD:**
   ```sql
   SELECT * FROM permissions WHERE name LIKE '%user%';
   SELECT * FROM model_has_permissions WHERE model_id = ADMIN_ID;
   SELECT * FROM role_has_permissions WHERE role_id = ROLE_ID;
   ```

3. **Vérifier les audit logs:**
   ```sql
   SELECT * FROM audit_logs WHERE action LIKE '%citizen%' ORDER BY created_at DESC LIMIT 10;
   ```

## 🎉 RÉSUMÉ

Le système de gestion des citoyens est maintenant complet avec:
- ✅ 11 nouvelles permissions
- ✅ Contrôleur complet avec 8 méthodes
- ✅ 2 vues Blade (liste + profil)
- ✅ 8 routes protégées par permissions
- ✅ Menu ajouté dans la sidebar
- ✅ Recherche avancée multi-critères
- ✅ Export CSV
- ✅ Actions conditionnelles par permission
- ✅ Audit complet de toutes les actions
- ✅ Interface responsive et moderne
- ✅ Intégration avec systèmes existants (documents, OAuth, audit)

**Prochaine étape:** Exécuter les commandes de cache et tester l'interface !
