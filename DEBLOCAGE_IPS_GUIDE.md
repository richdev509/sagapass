# 🔓 Déblocage des IPs - Guide Complet

## ⚙️ Configuration Actuelle

### Seuil de Blocage
- **Tentatives autorisées** : 15 tentatives suspectes
- **Période** : 24 heures
- **Durée du blocage** : 24 heures (par défaut)

Si une IP effectue **15 tentatives d'attaque en 24h**, elle est automatiquement bloquée pour **24h**.

---

## 🔓 3 Façons de Débloquer une IP

### 1. ✅ Déblocage Automatique (Par défaut)

**Comment ça marche :**
Le système vérifie automatiquement à chaque requête si le blocage est expiré.

**Code dans `BlockedIp::isBlocked()` :**
```php
// Si blocage temporaire expiré
if ($blocked->blocked_until && $blocked->blocked_until->isPast()) {
    $blocked->delete();  // ← Suppression automatique
    return false;        // ← IP débloquée
}
```

**Exemple concret :**
- IP bloquée : 21/11/2025 à 10:00
- Durée : 24 heures
- Déblocage auto : 22/11/2025 à 10:00
- Dès que l'IP essaie d'accéder après 10:00, elle est automatiquement débloquée

**Avantages :**
- ✅ Aucune intervention nécessaire
- ✅ Fonctionne pour les blocages temporaires uniquement
- ✅ Pas besoin de cron job

---

### 2. 🖱️ Déblocage Manuel (Dashboard Admin)

**Via le Dashboard :**
1. Accéder à : `/admin/security/blocked-ips`
2. Trouver l'IP dans la liste
3. Cliquer sur le bouton "Débloquer" 🔓
4. Confirmation → IP débloquée immédiatement

**Via le Dashboard principal :**
1. Accéder à : `/admin/security`
2. Section "IPs Bloquées Actives"
3. Cliquer "Débloquer" sur l'IP concernée

**Code JavaScript :**
```javascript
function unblockIp(ip) {
    fetch('/admin/security/unblock-ip', {
        method: 'POST',
        body: JSON.stringify({ ip_address: ip })
    });
}
```

**Quand l'utiliser :**
- IP bloquée par erreur (faux positif)
- Utilisateur légitime qui a déclenché la protection
- Besoin d'accès immédiat avant expiration

---

### 3. 🧹 Nettoyage des Blocages Expirés (Maintenance)

**Via le Dashboard :**
1. Accéder à : `/admin/security/blocked-ips`
2. Cliquer sur "Nettoyer expirés" 🧹
3. Tous les blocages expirés sont supprimés de la base

**Via Tinker :**
```bash
php artisan tinker
BlockedIp::cleanExpired();
```

**Via Cron (Recommandé pour production) :**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Nettoyer les blocages expirés chaque heure
    $schedule->call(function () {
        \App\Models\BlockedIp::cleanExpired();
    })->hourly();
}
```

**Ajouter au crontab VPS :**
```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne
0 * * * * cd /var/www/sagapass && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Types de Blocage

### Blocage Temporaire (Par défaut)
- **Durée** : 24 heures (modifiable)
- **Déblocage** : Automatique après expiration
- **Cas d'usage** : Attaques automatiques, tentatives répétées

### Blocage Permanent
- **Durée** : Infini
- **Déblocage** : Manuel uniquement (via dashboard)
- **Cas d'usage** : IPs malveillantes connues, bots dangereux

---

## 🔧 Personnaliser les Paramètres

### Changer le Nombre de Tentatives

**Fichier : `app/Http/Middleware/SecurityCheck.php`**
```php
private int $maxAttempts = 15; // ← Modifier ici (actuellement 15)
```

**Exemples :**
- `10` : Très strict (sécurité maximale)
- `15` : Équilibré (actuel)
- `20` : Tolérant (moins de faux positifs)
- `50` : Très tolérant (pour tests)

---

### Changer la Durée de Blocage

**Fichier : `app/Http/Middleware/SecurityCheck.php` (ligne 95)**
```php
BlockedIp::blockIp(
    $ip,
    "Blocage automatique: {$recentAttempts} tentatives suspectes détectées",
    24, // ← Durée en HEURES (modifier ici)
    false
);
```

**Exemples :**
- `1` : 1 heure
- `12` : 12 heures
- `24` : 24 heures (actuel)
- `48` : 2 jours
- `168` : 1 semaine

---

### Changer la Période de Comptage

**Fichier : `app/Http/Middleware/SecurityCheck.php` (ligne 86)**
```php
$recentAttempts = SecurityLog::where('ip_address', $ip)
    ->where('created_at', '>=', now()->subDay()) // ← Période de comptage
    ->count();
```

**Exemples :**
- `now()->subHour()` : Compter sur 1 heure
- `now()->subHours(6)` : Compter sur 6 heures
- `now()->subDay()` : Compter sur 24h (actuel)
- `now()->subDays(7)` : Compter sur 7 jours

---

## 🎯 Scénarios d'Utilisation

### Scénario 1 : Attaque Automatique
```
10:00 - Tentative 1-5 : SQL injection détecté
10:05 - Tentative 6-10 : XSS détecté
10:10 - Tentative 11-15 : Path traversal détecté
10:12 - Tentative 16 : ❌ IP BLOQUÉE pour 24h
10:13 - Toutes requêtes : HTTP 403 "IP bloquée"
```
**Déblocage : Automatique le lendemain à 10:12**

---

### Scénario 2 : Utilisateur Légitime (Faux Positif)
```
14:00 - Développeur teste API avec caractères spéciaux
14:05 - 15 tentatives déclenchent la protection
14:06 - IP bloquée ❌
```
**Solution :**
1. Admin accède à `/admin/security/blocked-ips`
2. Trouve l'IP du développeur
3. Clique "Débloquer" ✅
4. Développeur peut continuer immédiatement

---

### Scénario 3 : Bot Malveillant Persistant
```
Jour 1 : 50 tentatives → Bloqué 24h
Jour 2 : Débloqué automatiquement → 50 nouvelles tentatives → Bloqué 24h
Jour 3 : Débloqué automatiquement → Continue...
```
**Solution : Blocage Permanent**
1. Admin accède à `/admin/security/blocked-ips`
2. Clique "Bloquer une IP"
3. Entre l'IP, raison : "Bot malveillant persistant"
4. ✅ Coche "Blocage permanent"
5. IP bloquée définitivement

---

## 🔍 Vérifier l'État d'une IP

### Via Dashboard
`/admin/security/blocked-ips` → Rechercher l'IP

### Via Tinker
```bash
php artisan tinker
```

```php
// Vérifier si IP est bloquée
BlockedIp::isBlocked('192.168.1.100');  // true ou false

// Voir détails du blocage
BlockedIp::where('ip_address', '192.168.1.100')->first();

// Voir historique des attaques
SecurityLog::where('ip_address', '192.168.1.100')->get();
```

---

## 📧 Notifications (Future Feature)

### Notification Admin par Email
```php
// À implémenter dans SecurityCheck.php après blocage
Mail::to(config('mail.admin'))->send(
    new IpBlockedNotification($ip, $recentAttempts)
);
```

### Notification Utilisateur (si authentifié)
```php
// Alerter l'utilisateur si son IP est bloquée
if (auth()->check()) {
    $user = auth()->user();
    Mail::to($user->email)->send(
        new SecurityAlertMail($ip, $reason)
    );
}
```

---

## 🛡️ Whitelist (IPs de Confiance)

Pour éviter de bloquer vos propres IPs (VPS, bureau, etc.), ajoutez une whitelist :

**Fichier : `app/Http/Middleware/SecurityCheck.php`**
```php
private array $whitelist = [
    '127.0.0.1',           // Localhost
    '::1',                 // Localhost IPv6
    'IP_DE_VOTRE_BUREAU',  // Votre IP fixe
    'IP_DU_VPS',           // IP du serveur
];

public function handle(Request $request, Closure $next): Response
{
    $ip = $request->ip();

    // Ignorer les IPs en whitelist
    if (in_array($ip, $this->whitelist)) {
        return $next($request);
    }

    // ... reste du code
}
```

---

## 📊 Statistiques de Déblocage

### Voir les IPs qui ont été débloquées
```php
// Les déblocages automatiques ne laissent pas de trace (supprimés)
// Pour garder un historique, modifier la méthode isBlocked() :

public static function isBlocked(string $ip): bool
{
    $blocked = self::where('ip_address', $ip)->first();

    if (!$blocked) {
        return false;
    }

    if ($blocked->is_permanent) {
        return true;
    }

    if ($blocked->blocked_until && $blocked->blocked_until->isPast()) {
        // Au lieu de delete(), marquer comme "unblocked"
        $blocked->update(['unblocked_at' => now(), 'unblocked_reason' => 'auto']);
        return false;
    }

    return true;
}
```

---

## ⚡ Résumé Rapide

| Méthode | Délai | Intervention | Usage |
|---------|-------|--------------|-------|
| **Automatique** | Après expiration (24h) | ❌ Aucune | Par défaut |
| **Manuel Dashboard** | Immédiat | ✅ Admin | Faux positifs |
| **Nettoyage Cron** | Chaque heure | ⚙️ Automatisé | Maintenance |

---

## 🎯 Configuration Actuelle Recommandée

```
✅ Tentatives max : 15 (en 24h)
✅ Durée blocage : 24 heures
✅ Déblocage auto : Activé
✅ Type : Temporaire par défaut
✅ Permanent : Manuel uniquement
```

Cette configuration offre un **bon équilibre** entre :
- 🛡️ Sécurité (15 tentatives suffisent pour détecter les attaques)
- 👤 Expérience utilisateur (24h permet déblocage naturel)
- ⚙️ Maintenance (déblocage automatique sans intervention)

---

## 🚀 Pour Aller Plus Loin

### Ajuster par Type d'Attaque
```php
// Bloquer plus vite pour SQL Injection (critique)
if ($attackDetected['type'] === SecurityLog::TYPE_SQL_INJECTION) {
    $maxAttempts = 5;  // Plus strict
}

// Bloquer moins vite pour Rate Limit (moins critique)
if ($attackDetected['type'] === SecurityLog::TYPE_RATE_LIMIT) {
    $maxAttempts = 30;  // Plus tolérant
}
```

### Déblocage Progressif
```php
// Première fois : 1h
// Deuxième fois : 6h
// Troisième fois : 24h
// Quatrième fois : Permanent

$attempts = BlockedIp::where('ip_address', $ip)->count();
$duration = match($attempts) {
    1 => 1,
    2 => 6,
    3 => 24,
    default => 0  // Permanent
};
```

Le système est maintenant configuré pour **15 tentatives** et le déblocage automatique fonctionne parfaitement ! 🎉
