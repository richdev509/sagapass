<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Personne en liste de vigilance (anti-fraude) — une entrée par personne,
 * potentiellement plusieurs pièces d'identité associées (voir
 * BlacklistedIdentityDocument, ex. la même personne a déjà utilisé plusieurs
 * numéros). Comparaison faciale volontairement hors scope pour l'instant
 * (voir BlacklistScreeningService) — prévue plus tard.
 */
class BlacklistedIdentity extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'photo_path',
        'reason',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BlacklistedIdentityDocument::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'added_by');
    }

    /**
     * Normalisation tolérante (majuscules, espaces multiples réduits,
     * accents non gérés volontairement — l'OCR ne renvoie déjà que des
     * majuscules non accentuées sur les champs testés) pour la comparaison
     * par nom, un signal faible (homonymes) contrairement au numéro de pièce.
     */
    public static function normalizeName(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($value)));
    }

    public function matchesName(string $fullName): bool
    {
        $normalized = self::normalizeName($fullName);

        return $normalized === self::normalizeName("{$this->first_name} {$this->last_name}")
            || $normalized === self::normalizeName("{$this->last_name} {$this->first_name}");
    }
}
