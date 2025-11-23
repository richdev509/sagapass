<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class DigitalBadge extends Model
{
    protected $fillable = [
        'user_id',
        'badge_token',
        'qr_code_data',
        'expires_at',
        'is_active',
        'ip_address',
        'user_agent',
        'last_scanned_at',
        'scan_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Générer un nouveau badge avec token crypté unique
     */
    public static function generateForUser(User $user, $ipAddress = null, $userAgent = null): self
    {
        // Révoquer les anciens badges actifs
        self::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Données à encoder dans le QR code
        $data = [
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'account_level' => $user->account_level,
            'verification_level' => $user->verification_level,
            'timestamp' => now()->timestamp,
            'expires_at' => now()->addHours(12)->timestamp,
            'nonce' => Str::random(32),
        ];

        // Token crypté unique
        $badgeToken = hash('sha256', Crypt::encryptString(json_encode($data)));

        // Créer le badge avec données chiffrées
        return self::create([
            'user_id' => $user->id,
            'badge_token' => $badgeToken,
            'qr_code_data' => Crypt::encryptString(json_encode($data)), // ✅ Chiffrement des données
            'expires_at' => now()->addHours(12),
            'is_active' => true,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Vérifier si le badge est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si le badge est valide
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Marquer comme scanné
     */
    public function markAsScanned(): void
    {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => now()]);
    }

    /**
     * Révoquer le badge
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Obtenir l'URL du badge pour validation
     */
    public function getValidationUrl(): string
    {
        return route('badge.validate', ['token' => $this->badge_token]);
    }

    /**
     * Déchiffrer et obtenir les données du badge
     */
    public function getDecryptedData(): array
    {
        try {
            return json_decode(Crypt::decryptString($this->qr_code_data), true);
        } catch (\Exception $e) {
            // Si le déchiffrement échoue, tenter de lire en JSON direct (compatibilité anciens badges)
            return json_decode($this->qr_code_data, true) ?? [];
        }
    }

    /**
     * Scope pour les badges actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope pour les badges expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
