# 👥 Gestion des Rôles et Permissions - Sécurité SAGAPASS

## 🎯 Nouveau Rôle : Cyber Admin

Un nouveau rôle **`cyber-admin`** a été créé spécialement pour la gestion de la sécurité du système.

---

## 📊 Permissions de Sécurité Disponibles

| Permission | Description | Actions |
|------------|-------------|---------|
| `view-security-logs` | Voir les logs de sécurité | Accès au dashboard, consultation des logs |
| `manage-security` | Gérer la sécurité | Nettoyer blocages expirés, gestion globale |
| `view-blocked-ips` | Voir les IPs bloquées | Liste des IPs bloquées |
| `block-ips` | Bloquer des IPs | Bloquer manuellement une IP |
| `unblock-ips` | Débloquer des IPs | Débloquer une IP bloquée |
| `delete-security-logs` | Supprimer logs anciens | Nettoyer les logs de sécurité |

---

## 👔 Rôles et Accès à la Sécurité

### 1. **Super Admin** (Accès Complet)
✅ **Toutes les permissions** (56 au total)
- Accès total au dashboard de sécurité
- Peut bloquer/débloquer toutes les IPs
- Peut supprimer les logs
- Peut gérer tous les aspects du système

**Cas d'usage :** Administrateur principal du système

---

### 2. **Cyber Admin** 🆕 (Spécialisé Sécurité)
✅ **9 permissions** spécifiques à la sécurité :
- ✅ `view-security-logs`
- ✅ `manage-security`
- ✅ `view-blocked-ips`
- ✅ `block-ips`
- ✅ `unblock-ips`
- ✅ `delete-security-logs`
- ✅ `view-audit-logs`
- ✅ `view-connection-logs`
- ✅ `view-statistics`

**Ce qu'il peut faire :**
- ✅ Voir tous les logs de sécurité
- ✅ Bloquer/Débloquer des IPs manuellement
- ✅ Voir les statistiques d'attaques
- ✅ Nettoyer les logs anciens
- ✅ Voir l'historique des connexions
- ✅ Accès complet au dashboard sécurité

**Ce qu'il NE PEUT PAS faire :**
- ❌ Gérer les utilisateurs
- ❌ Modifier les documents
- ❌ Gérer les développeurs OAuth
- ❌ Créer/modifier des admins
- ❌ Gérer les rôles et permissions

**Cas d'usage :** Expert en cybersécurité dédié à la surveillance et protection du système

---

### 3. **Admin** (Lecture Seule Sécurité)
✅ **26 permissions** incluant :
- ✅ `view-security-logs` (lecture seule)
- ✅ `view-blocked-ips` (lecture seule)
- ❌ Pas de blocage/déblocage d'IPs
- ❌ Pas de suppression de logs

**Accès sécurité :**
- ✅ Consulter le dashboard de sécurité
- ✅ Voir les IPs bloquées
- ✅ Voir les logs d'attaques
- ❌ Ne peut pas bloquer/débloquer

**Cas d'usage :** Administrateur général avec consultation sécurité

---

## 🛠️ Créer un Cyber Admin

### Via Tinker
```bash
php artisan tinker
```

```php
// Créer un nouvel admin cyber
$admin = Admin::create([
    'name' => 'John Cyber',
    'email' => 'cyber@sagapass.com',
    'password' => bcrypt('MotDePasse123!'),
]);

// Assigner le rôle cyber-admin
$admin->assignRole('cyber-admin');

// Vérifier
$admin->hasRole('cyber-admin'); // true
$admin->can('block-ips'); // true
```

### Via Dashboard (Super Admin)
1. Accéder à `/admin/admins`
2. Créer un nouvel administrateur
3. Dans "Rôle", sélectionner **"cyber-admin"**
4. Enregistrer

---

## 📋 Tableau Récapitulatif des Accès

| Rôle | Dashboard | Voir Logs | Voir IPs | Bloquer | Débloquer | Supprimer Logs |
|------|-----------|-----------|----------|---------|-----------|----------------|
| **Super Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Cyber Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ (lecture) | ✅ (lecture) | ❌ | ❌ | ❌ |
| **Moderator** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Support** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **OAuth Manager** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🔐 Contrôle d'Accès dans l'Interface

### Menu Admin
Le lien "Sécurité" apparaît uniquement pour :
```blade
@can('view-security-logs', 'admin')
```
**Visible pour :** Super Admin, Cyber Admin, Admin

### Actions Conditionnelles
- **Bloquer IP** : Nécessite `block-ips` → Cyber Admin, Super Admin
- **Débloquer** : Nécessite `unblock-ips` → Cyber Admin, Super Admin
- **Nettoyer logs** : Nécessite `delete-security-logs` → Cyber Admin, Super Admin

---

## 🔄 Mise à Jour Appliquée

✅ **6 nouvelles permissions** de sécurité créées
✅ **1 nouveau rôle** : `cyber-admin`
✅ **Rôle admin** enrichi avec lecture sécurité
✅ **Routes protégées** par middleware de permissions
✅ **Interface conditionnelle** selon les permissions
✅ **Système non cassant** : anciens rôles préservés

---

## 🎓 Bonnes Pratiques

### 1. Principe du Moindre Privilège
✅ Donner uniquement les permissions nécessaires
```php
// BON
$user->assignRole('cyber-admin');

// ÉVITER
$user->assignRole('super-admin'); // Sauf si vraiment nécessaire
```

### 2. Avoir au Moins 2 Cyber Admins
- Assurer la continuité en cas d'absence
- Surveillance mutuelle des actions

### 3. Documenter les Blocages
Toujours renseigner une raison claire lors du blocage d'une IP

---

## 🆘 Commandes Utiles

```bash
# Vérifier les rôles
php artisan tinker
Admin::with('roles')->get()->map(fn($a) => [
    'name' => $a->name,
    'roles' => $a->getRoleNames()
]);

# Vider le cache des permissions
php artisan permission:cache-reset
php artisan cache:clear

# Re-exécuter le seeder
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Le système de permissions est maintenant complet et granulaire ! 🎉
