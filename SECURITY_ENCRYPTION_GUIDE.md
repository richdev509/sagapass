# 🔐 Système de Sécurité et Cryptage SAGA ID

## 📋 Vue d'ensemble

Le système de sécurité SAGA ID protège les données sensibles (photos de cartes d'identité et selfies) contre l'interception par des hackers lors de la transmission entre l'application mobile et le serveur.

### 🛡️ Protections Mises en Place

1. **Cryptage AES-256-CBC** - Images cryptées avant transmission
2. **Signature HMAC-SHA256** - Vérification de l'intégrité des données
3. **Nonce anti-replay** - Protection contre les attaques par rejeu
4. **Timestamp** - Validation de la fraîcheur des requêtes
5. **HTTPS** - Canal de communication sécurisé (à configurer en production)

---

## 🔄 Flux de Sécurité

### Côté Flutter (Application Mobile)

```
1. Capture photo → Conversion Base64
2. Cryptage AES-256 avec IV aléatoire
3. Génération timestamp + nonce
4. Création signature HMAC
5. Envoi données cryptées + métadonnées
```

### Côté Laravel (Serveur)

```
1. Réception requête cryptée
2. Vérification nonce (anti-replay)
3. Validation signature HMAC
4. Décryptage AES-256
5. Traitement images décryptées
```

---

## 📁 Architecture des Fichiers

### Flutter

```
lib/
  services/
    encryption_service.dart    # Service de cryptage
  models/
    registration_data.dart     # toJsonEncrypted() ajouté
  services/
    auth_service.dart         # Utilise toJsonEncrypted()
```

### Laravel

```
app/
  Services/
    EncryptionService.php     # Service de décryptage
  Http/
    Controllers/
      Api/Mobile/
        MobileAuthController.php  # Décryptage avant traitement
    Requests/
      Mobile/
        CompleteRegistrationRequest.php  # Validation avec champs sécurité
```

---

## 🔑 Clé de Cryptage

**⚠️ IMPORTANT**: La clé de cryptage DOIT être identique côté Flutter et Laravel.

### Clé Actuelle (à changer en production)

```dart
// Flutter: lib/services/encryption_service.dart
static const String _masterKey = 'SAGA_ID_2026_SECRET_KEY_32BYTE'; // 32 caractères
```

```php
// Laravel: app/Services/EncryptionService.php
private const MASTER_KEY = 'SAGA_ID_2026_SECRET_KEY_32BYTE'; // 32 caractères
```

### 🔐 Pour la Production

1. **Générer une clé aléatoire forte** (32 caractères):
   ```bash
   php artisan tinker
   >>> Str::random(32)
   ```

2. **Stocker dans .env**:
   ```env
   APP_ENCRYPTION_KEY=votre_cle_aleatoire_32_caracteres_ici
   ```

3. **Modifier Laravel**:
   ```php
   private const MASTER_KEY = env('APP_ENCRYPTION_KEY');
   ```

4. **Modifier Flutter** (utiliser API pour récupérer la clé ou la stocker sécurisément):
   - Option 1: Récupérer via endpoint sécurisé lors de l'authentification
   - Option 2: Stocker dans flutter_secure_storage après premier échange
   - Option 3: Utiliser Firebase Remote Config

---

## 🔬 Format des Données Cryptées

### Requête Flutter → Laravel

```json
{
  "first_name": "Jean",
  "last_name": "Dupont",
  "email": "jean@example.com",
  "phone": "+50928123456",
  "date_of_birth": "1990-01-01",
  "niu": "1234567890",
  
  "id_card_front": "dGVzdA==:Y3J5cHRlZA==",  // IV:encrypted_data
  "id_card_back": "dGVzdA==:Y3J5cHRlZA==",   // IV:encrypted_data
  "selfie": "dGVzdA==:Y3J5cHRlZA==",         // IV:encrypted_data
  
  "encrypted": true,
  "nonce": "1710849273000:abc123",
  "timestamp": "1710849273000",
  "signature": "a1b2c3d4e5f6..." // HMAC-SHA256
}
```

### Format Cryptage

```
Format: {IV_base64}:{encrypted_data_base64}
Exemple: "dGVzdA==:Y3J5cHRlZGRhdGE="

- IV: 16 bytes aléatoires (nouveau à chaque cryptage)
- Encrypted Data: Données cryptées avec AES-256-CBC
```

---

## 🧪 Tests

### Test de Cryptage/Décryptage

#### Flutter (Debug)

```dart
void testEncryption() {
  final encryption = EncryptionService();
  
  // Test cryptage
  final plainText = "Test data";
  final encrypted = encryption.encrypt(plainText);
  print("Encrypted: $encrypted");
  
  // Test décryptage
  final decrypted = encryption.decrypt(encrypted);
  print("Decrypted: $decrypted");
  
  assert(decrypted == plainText);
}
```

#### Laravel (Tinker)

```bash
php artisan tinker

>>> $service = new \App\Services\EncryptionService();

>>> $encrypted = $service->encrypt("Test data");
=> "dGVzdA==:Y3J5cHRlZGRhdGE="

>>> $decrypted = $service->decrypt($encrypted);
=> "Test data"
```

### Test de Signature HMAC

```bash
php artisan tinker

>>> $service = new \App\Services\EncryptionService();

>>> $data = ['test' => 'value'];
>>> $timestamp = '1710849273000';

>>> $signature = $service->createSignature($data, $timestamp);
=> "a1b2c3d4e5f6..."

>>> $valid = $service->verifySignature($data, $timestamp, $signature);
=> true
```

---

## 📊 Logs de Sécurité

### Logs Générés

```
[2026-03-21] ⚠️ Détection de données cryptées - décryptage en cours
[2026-03-21] ✅ Données décryptées et validées avec succès
[2026-03-21] ⚠️ Données non cryptées détectées (mode compatibilité)
[2026-03-21] ❌ Erreur de décryptage: Signature invalide
```

### Vérifier les Logs

```bash
# Laravel
tail -f storage/logs/laravel.log | grep -E "cryptées|décryptage|signature"

# Flutter (dans VS Code Debug Console)
# Rechercher: encryption, decrypt, signature
```

---

## 🚨 Gestion des Erreurs

### Erreurs Possibles

| Erreur | Cause | Solution |
|--------|-------|----------|
| "Format de données cryptées invalide" | Format IV:data incorrect | Vérifier format cryptage Flutter |
| "Signature invalide" | Clés différentes ou données altérées | Vérifier MASTER_KEY identique |
| "Nonce invalide ou expiré" | Requête trop vieille ou rejouée | Vérifier horloge système |
| "Erreur de décryptage" | Clé incorrecte | Vérifier clé 32 caractères exactement |

### Mode Compatibilité

Le système supporte **deux modes** pour faciliter la transition:

1. **Mode Sécurisé** (`encrypted: true`): Données cryptées avec validation
2. **Mode Compatibilité** (`encrypted: false/absent`): Données en clair (pour tests)

```php
// Le serveur accepte les deux formats
if ($request->has('encrypted') && $request->encrypted === true) {
    // Décryptage
} else {
    // Mode compatibilité (données en clair)
}
```

---

## ⚡ Performance

### Impact du Cryptage

- **Temps de cryptage**: ~10-50ms par image
- **Taille des données**: +33% (base64) puis même taille après cryptage
- **Latence réseau**: Identique (taille similaire)

### Optimisations

1. **Cryptage asynchrone** en Flutter (déjà isolé)
2. **Cache des clés** en mémoire
3. **Compression avant cryptage** (optionnel)

---

## 🔒 Bonnes Pratiques de Sécurité

### ✅ À FAIRE

1. ✅ Utiliser HTTPS en production
2. ✅ Changer la clé de cryptage (ne pas utiliser celle par défaut)
3. ✅ Stocker la clé dans .env (Laravel) et secure_storage (Flutter)
4. ✅ Implémenter rotation des clés tous les 90 jours
5. ✅ Logger toutes les tentatives de décryptage échouées
6. ✅ Limiter le taux de requêtes (rate limiting)
7. ✅ Valider les formats d'images après décryptage

### ❌ À ÉVITER

1. ❌ Hardcoder la clé dans le code (actuel = temporaire)
2. ❌ Désactiver le cryptage en production
3. ❌ Ignorer les erreurs de signature HMAC
4. ❌ Accepter des nonces expirés
5. ❌ Logger les données décryptées (logs sensibles)

---

## 🔄 Migration et Rétro-compatibilité

### Déploiement Progressif

**Phase 1: Dual Mode (Actuel)**
```
- Accepter données cryptées ET non cryptées
- Logger quel mode est utilisé
```

**Phase 2: Cryptage Recommandé**
```
- Logger warning si données non cryptées
- Notification aux utilisateurs de mettre à jour l'app
```

**Phase 3: Cryptage Obligatoire**
```
- Rejeter toutes requêtes non cryptées
- Supprimer mode compatibilité
```

### Vérifier le Mode Utilisé

```bash
# Compter les requêtes cryptées vs non cryptées
php artisan tinker
>>> DB::connection()->enableQueryLog();
# Analyser les logs pour voir encrypted: true/false
```

---

## 📚 Ressources

### Documentation Technique

- **AES-256-CBC**: [Wikipedia](https://en.wikipedia.org/wiki/Advanced_Encryption_Standard)
- **HMAC-SHA256**: [RFC 2104](https://tools.ietf.org/html/rfc2104)
- **Package Flutter encrypt**: [pub.dev](https://pub.dev/packages/encrypt)
- **OpenSSL PHP**: [php.net](https://www.php.net/manual/en/book.openssl.php)

### Packages Utilisés

**Flutter:**
- `encrypt: ^5.0.3` - Cryptage AES
- `crypto: ^3.0.5` - Hashing HMAC/SHA256

**Laravel:**
- OpenSSL (inclus dans PHP) - Cryptage/décryptage

---

## 🧩 Exemple Complet

### Flutter: Envoyer Inscription Cryptée

```dart
// Dans registration_provider.dart
Future<UserModel?> completeRegistration() async {
  final response = await _authService.completeRegistration(_data);
  return response.data;
}

// auth_service.dart utilise automatiquement:
data.toJsonEncrypted() // ✅ Crypté automatiquement
```

### Laravel: Recevoir et Décrypter

```php
// MobileAuthController.php
public function completeRegistration(CompleteRegistrationRequest $request) {
    // Détection automatique et décryptage
    if ($request->encrypted === true) {
        $decryptedData = $encryptionService->decryptAndValidateRequest($request->all());
        // Utiliser $decryptedData
    }
    
    // Suite du traitement...
}
```

---

## 📞 Support

En cas de problème de sécurité:

1. Vérifier les logs Laravel: `storage/logs/laravel.log`
2. Vérifier logs Flutter: Debug Console
3. Tester cryptage/décryptage isolément
4. Vérifier que les clés sont identiques
5. Vérifier format des données (IV:encrypted_data)

---

**💡 Note**: Ce système protège les données EN TRANSIT. Pour une sécurité complète, pensez aussi à:
- Crypter les données AT REST (sur le serveur)
- Implémenter un système de gestion des clés (KMS)
- Auditer régulièrement les accès aux données sensibles
- Mettre en place des alertes de sécurité
