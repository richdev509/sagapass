<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Identité KYC durable ("kyc_id"), distincte de PartnerVerificationSession
 * (éphémère, photos purgées après analyse). Une ligne par (partenaire,
 * numéro de document) — mise à jour et sa validité prolongée à chaque
 * nouvelle session complétée pour le même document, jamais dupliquée
 * (contrainte unique developer_application_id+document_number).
 *
 * Permet au partenaire de revérifier plus tard, sans refaire toute la
 * capture, si une personne déjà vérifiée est toujours "KYC valide" — voir
 * Api\Partner\PartnerKycIdentityController::status(). SagaID alerte aussi
 * proactivement le partenaire par webhook (NotifyPartnerKycExpiryWebhook)
 * dès que la période de validité se termine.
 */
class PartnerVerifiedIdentity extends Model
{
    protected $fillable = [
        'kyc_id',
        'developer_application_id',
        'document_type',
        'document_number',
        'full_name',
        'date_of_birth',
        'face_match_score',
        'liveness_passed',
        'status',
        'verified_at',
        'valid_until',
        'webhook_url',
        'last_partner_verification_session_id',
        'expired_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'liveness_passed' => 'boolean',
            'verified_at' => 'datetime',
            'valid_until' => 'datetime',
            'expired_notified_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $identity) {
            if (! $identity->kyc_id) {
                $identity->kyc_id = (string) Str::uuid();
            }
        });
    }

    public function developerApplication(): BelongsTo
    {
        return $this->belongsTo(DeveloperApplication::class);
    }

    public function lastPartnerVerificationSession(): BelongsTo
    {
        return $this->belongsTo(PartnerVerificationSession::class, 'last_partner_verification_session_id');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid' && $this->valid_until->isFuture();
    }
}
