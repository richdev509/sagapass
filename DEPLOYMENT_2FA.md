# Déploiement 2FA sur le Serveur de Production

## Après `git pull` sur le serveur

### 1. Installer les nouvelles dépendances Composer
```bash
composer install --no-dev --optimize-autoloader
```

**Packages nécessaires :**
- `pragmarx/google2fa` (v9.0.0)
- `endroid/qr-code` (v6.0.9)

---

### 2. Exécuter les migrations de base de données
```bash
php artisan migrate --force
```

**Migrations à exécuter :**
- `add_two_factor_columns_to_admins_table` (3 colonnes)
- `update_two_factor_secret_column_size` (TEXT pour secret chiffré)

---

### 3. Créer le paramètre système pour forcer le 2FA
```bash
php artisan tinker --execute="App\Models\SystemSetting::set('force_2fa_for_admins', false, 'boolean');"
```

**Note :** Commence à `false` - à activer manuellement après tests

---

### 4. Nettoyer tous les caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

---

### 5. Vérifier les permissions des fichiers
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

### 6. Tests de Vérification

#### A. Tester la configuration 2FA
1. Connectez-vous en tant que super-admin
2. Allez sur `/admin/two-factor`
3. Activez le 2FA :
   - Scannez le QR code avec Google Authenticator
   - Entrez le code à 6 chiffres
   - **Sauvegardez les 8 codes de récupération** (très important!)
4. Déconnectez-vous
5. Reconnectez-vous → vérifiez que la page de vérification 2FA s'affiche
6. Entrez le code de Google Authenticator → accès au dashboard

#### B. Tester le paramètre "Forcer 2FA"
1. Connectez-vous en tant que super-admin (avec 2FA activé)
2. Allez sur `/admin/settings`
3. **NE PAS ENCORE ACTIVER** "Forcer le 2FA pour tous les administrateurs"
4. Créez un admin de test sans 2FA
5. Vérifiez qu'il peut se connecter normalement
6. Revenez aux paramètres, activez "Forcer le 2FA"
7. Déconnectez-vous et reconnectez-vous avec l'admin test
8. **Vérification :** Il doit être redirigé automatiquement vers `/admin/two-factor/enable`
9. Configurez le 2FA pour cet admin
10. Vérifiez qu'il peut maintenant accéder au dashboard

---

### 7. Configuration en Production

#### Mettre APP_DEBUG à false
Dans le fichier `.env` :
```env
APP_DEBUG=false
```

**Important :** Plus aucun code de debug ne s'affichera dans les vues

#### Vérifier le fuseau horaire
Dans le fichier `.env` :
```env
APP_TIMEZONE=Africa/Dakar
```

**Note :** Actuellement configuré sur `America/Anchorage` - à changer pour le Sénégal

---

### 8. Recommandations de Sécurité

#### A. Configurer le 2FA pour TOUS les admins existants
```bash
# Lister tous les admins
php artisan tinker --execute="App\Models\Admin::all(['id', 'name', 'email'])->each(fn(\$a) => print(\$a->email . ' - 2FA: ' . (\$a->hasTwoFactorEnabled() ? 'OUI' : 'NON') . PHP_EOL));"
```

#### B. Activer "Forcer le 2FA" après configuration
1. Assurez-vous que tous les admins actifs ont configuré leur 2FA
2. Allez sur `/admin/settings`
3. Activez "Forcer le 2FA pour tous les administrateurs"
4. Sauvegardez

#### C. Codes de récupération
- Chaque admin doit **impérativement** sauvegarder ses 8 codes de récupération
- Ces codes sont à usage unique
- En cas de perte du téléphone, ces codes permettent de se reconnecter
- Ils peuvent être régénérés depuis `/admin/two-factor` (avec confirmation par mot de passe)

---

### 9. En cas de problème

#### Admin bloqué (smartphone perdu, 2FA non fonctionnel)
```bash
# Désactiver le 2FA pour un admin spécifique (en tant que super-admin via tinker)
php artisan tinker

# Dans tinker :
$admin = App\Models\Admin::where('email', 'admin@example.com')->first();
$admin->two_factor_secret = null;
$admin->two_factor_recovery_codes = null;
$admin->two_factor_confirmed_at = null;
$admin->save();
```

#### Désactiver temporairement le "Forcer 2FA"
```bash
php artisan tinker --execute="App\Models\SystemSetting::set('force_2fa_for_admins', false, 'boolean');"
php artisan cache:clear
```

---

### 10. Ordre de Déploiement Recommandé

```bash
# 1. Pull du code
git pull origin main

# 2. Dépendances
composer install --no-dev --optimize-autoloader

# 3. Migrations
php artisan migrate --force

# 4. Paramètres système
php artisan tinker --execute="App\Models\SystemSetting::set('force_2fa_for_admins', false, 'boolean');"

# 5. Nettoyage des caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# 6. Permissions (si nécessaire)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Tests (voir section 6)
# - Tester 2FA individuel
# - Tester force 2FA

# 8. Production
# - Mettre APP_DEBUG=false dans .env
# - Corriger APP_TIMEZONE si nécessaire
```

---

### 11. Checklist Finale

- [ ] Dépendances Composer installées
- [ ] Migrations exécutées avec succès
- [ ] Paramètre `force_2fa_for_admins` créé (false par défaut)
- [ ] Tous les caches nettoyés
- [ ] Permissions des dossiers correctes
- [ ] Super-admin a configuré son 2FA
- [ ] Codes de récupération du super-admin sauvegardés
- [ ] Tests de connexion avec 2FA réussis
- [ ] Test de la fonctionnalité "Forcer 2FA" réussi
- [ ] APP_DEBUG=false en production
- [ ] APP_TIMEZONE correct pour votre région
- [ ] Documentation remise à tous les admins sur l'utilisation du 2FA

---

## Support

En cas de problème, vérifier les logs :
```bash
tail -f storage/logs/laravel.log
```

Les logs 2FA incluent :
- Génération de secret
- Tentatives de validation
- Fenêtres de temps valides
- Succès/échecs de connexion
