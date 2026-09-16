<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Session de capture faciale à jeton, initiée par un partenaire (voir
 * Api\Partner\PartnerVerificationSessionController) pour son propre
 * utilisateur final — qui n'a pas de compte SagaID. Le jeton est la seule
 * "authentification" de la page publique de capture. Distincte de Document
 * (couplé à un vrai compte User SagaID) et de PartnerVerificationChallenge
 * (couplé à l'app mobile native + FCM) — voir le plan associé.
 */
class PartnerVerificationSession extends Model
{
    protected $fillable = [
        'token',
        'developer_application_id',
        'partner_reference',
        'webhook_url',
        'document_type',
        'front_photo_path',
        'back_photo_path',
        'selfie_left_path',
        'selfie_center_path',
        'selfie_right_path',
        'status',
        'face_match_score',
        'liveness_passed',
        'ocr_extracted_document_number',
        'ocr_extracted_full_name',
        'ocr_extracted_date_of_birth',
        'analysis_raw',
        'warnings',
        'ip_address',
        'user_agent',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'ocr_extracted_date_of_birth' => 'date',
            'liveness_passed' => 'boolean',
            'analysis_raw' => 'array',
            'warnings' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function developerApplication()
    {
        return $this->belongsTo(DeveloperApplication::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAwaitingCapture(): bool
    {
        return $this->status === 'awaiting_capture' && ! $this->isExpired();
    }
}
