# Structure de Stockage des Images - Application Mobile SAGA ID

## 🔄 Différence entre Web et Mobile

### 📱 Application Mobile (NOUVELLE STRUCTURE - SANS VIDÉO)

**Table utilisée:** `user_verification_documents`

**Champs de stockage:**
```php
- id_card_front    // Photo RECTO de la carte d'identité
- id_card_back     // Photo VERSO de la carte d'identité  
- selfie           // Photo SELFIE avec détection de visage
- status           // pending | approved | rejected
- rejection_reason // Raison si rejeté
- verified_at      // Date de vérification
```

**Dossiers de stockage:**
- `storage/app/id_cards/` - Photos recto/verso des cartes
- `storage/app/selfies/` - Photos selfie avec détection faciale

**Format des fichiers:**
- Encodage: Base64 depuis Flutter → décodé en JPG
- Nom: `{uniqid()}_{timestamp}.jpg`
- Exemple: `65f8a7b9c3d2e_1710849273.jpg`

**Processus:**
1. Flutter capture photo → encode en base64
2. API reçoit base64 → décode
3. Sauvegarde dans `storage/app/{folder}/{filename}.jpg`
4. Chemin stocké en DB: `id_cards/65f8a7b9c3d2e_1710849273.jpg`

---

### 🌐 Application Web (ANCIENNE STRUCTURE - AVEC VIDÉO)

**Table utilisée:** `users` (champs directs)

**Champs de stockage:**
```php
- profile_picture     // UNE SEULE photo de profil
- verification_video  // Vidéo de vérification (obligatoire)
- video_status        // none | pending | approved | rejected
- video_verified_at   // Date de vérification vidéo
```

**Dossiers de stockage:**
- `storage/app/public/profile_pictures/` - Photo de profil
- `storage/app/verification_videos/` - Vidéos de vérification

---

## 📊 Comparaison

| Critère | Application Mobile | Application Web |
|---------|-------------------|-----------------|
| Photos carte ID | ✅ Recto + Verso | ❌ Non demandé |
| Photo selfie | ✅ Avec détection faciale | ✅ Photo simple |
| Vidéo | ❌ Non demandé | ✅ Obligatoire |
| Table DB | `user_verification_documents` | `users` |
| Storage disk | `local` | `public` + `local` |
| Format | Base64 → JPG | Upload direct + Base64 |

---

## 📝 Modèle Laravel Créé

**Fichier:** `app/Models/UserVerificationDocument.php`

**Relations:**
```php
// Dans User.php (à ajouter si pas encore fait)
public function verificationDocuments()
{
    return $this->hasMany(UserVerificationDocument::class);
}

// Dans UserVerificationDocument.php
public function user()
{
    return $this->belongsTo(User::class);
}
```

**Méthodes utiles:**
```php
$document->isApproved()   // bool
$document->isPending()    // bool
$document->isRejected()   // bool
$document->approve()      // Approuver
$document->reject($reason) // Rejeter
```

---

## 🎯 Code d'Enregistrement (Mobile)

**Dans:** `MobileAuthController.php` → `completeRegistration()`

```php
// Sauvegarder les 3 photos
$idCardFrontPath = $this->saveBase64Image($request->id_card_front, 'id_cards');
$idCardBackPath = $this->saveBase64Image($request->id_card_back, 'id_cards');
$selfiePath = $this->saveBase64Image($request->selfie, 'selfies');

// Créer l'entrée dans user_verification_documents
DB::table('user_verification_documents')->insert([
    'user_id' => $user->id,
    'id_card_front' => $idCardFrontPath,
    'id_card_back' => $idCardBackPath,
    'selfie' => $selfiePath,
    'status' => 'pending',
    'created_at' => Carbon::now(),
    'updated_at' => Carbon::now(),
]);
```

**Méthode de sauvegarde:**
```php
private function saveBase64Image(string $base64String, string $folder): string
{
    // Nettoyer le préfixe data:image/...;base64,
    $image = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    $image = base64_decode($image);

    // Nom unique
    $filename = uniqid() . '_' . time() . '.jpg';
    $path = $folder . '/' . $filename;

    // Sauvegarder
    Storage::disk('local')->put($path, $image);

    return $path; // Retourne: "id_cards/65f8a7b9c3d2e_1710849273.jpg"
}
```

---

## ✅ Avantages de la Nouvelle Structure

1. **Séparation des données**: Documents de vérification dans une table dédiée
2. **Plus de photos**: Recto + Verso + Selfie vs 1 photo seulement
3. **Pas de vidéo**: Plus simple et rapide pour l'utilisateur mobile
4. **Gestion admin facilitée**: Statut de vérification indépendant
5. **Historique**: Possibilité d'ajouter plusieurs documents par user

---

## 🔍 Vérifier les Images Stockées

```powershell
# Voir les cartes d'identité
Get-ChildItem "storage\app\id_cards\" | Select-Object Name, Length, LastWriteTime

# Voir les selfies
Get-ChildItem "storage\app\selfies\" | Select-Object Name, Length, LastWriteTime

# Vérifier en DB
php artisan tinker
>>> DB::table('user_verification_documents')->latest()->first();
```

---

## 🎨 Flutter - Envoi des Images

**Dans:** `registration_provider.dart`

```dart
// Conversion en base64
_data.idCardFrontBase64 = base64Encode(await frontPhoto.readAsBytes());
_data.idCardBackBase64 = base64Encode(await backPhoto.readAsBytes());
_data.selfieBase64 = base64Encode(await selfiePhoto.readAsBytes());

// Envoi à l'API
final response = await _authService.completeRegistration(
  firstName: _data.firstName!,
  lastName: _data.lastName!,
  email: _data.email!,
  phone: _data.phone!,
  niu: _data.niu!,
  dateOfBirth: _data.dateOfBirth!,
  idCardFront: _data.idCardFrontBase64!,  // Base64 string
  idCardBack: _data.idCardBackBase64!,    // Base64 string
  selfie: _data.selfieBase64!,             // Base64 string
);
```
