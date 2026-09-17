<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'partner_submitted_data',
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
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'partner_verified_identity_id',
        'blacklist_hit',
        'blacklist_matched_identity_id',
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
            'partner_submitted_data' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function developerApplication()
    {
        return $this->belongsTo(DeveloperApplication::class);
    }

    public function partnerVerifiedIdentity()
    {
        return $this->belongsTo(PartnerVerifiedIdentity::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAwaitingIdCapture(): bool
    {
        return $this->status === 'awaiting_id_capture' && ! $this->isExpired();
    }

    public function isAwaitingSelfieCapture(): bool
    {
        return $this->status === 'awaiting_selfie_capture' && ! $this->isExpired();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'expired'], true);
    }

    /**
     * L'analyse automatisée a échoué techniquement (OCR/vivacité/correspondance
     * n'a pas pu tourner) — photos conservées (voir purgePhotos(), jamais
     * appelée pour ce statut) pour qu'un admin tranche manuellement, plutôt
     * que de rejeter à l'aveugle une vraie personne à cause d'un problème
     * technique.
     */
    public function isAwaitingManualReview(): bool
    {
        return $this->status === 'awaiting_manual_review';
    }

    /**
     * Supprime les photos (pièce + selfies) du disque privé. Appelé après
     * analyse (succès ou échec) et par la commande de nettoyage des sessions
     * expirées — aucune raison de conserver ces fichiers une fois la session
     * terminée d'une manière ou d'une autre.
     */
    public function purgePhotos(): void
    {
        $disk = Storage::disk('private');

        foreach ([
            $this->front_photo_path,
            $this->back_photo_path,
            $this->selfie_left_path,
            $this->selfie_center_path,
            $this->selfie_right_path,
        ] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
    }
}
