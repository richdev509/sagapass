<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'card_number',
        'document_number',
        'issue_date',
        'expiry_date',
        'front_photo_path',
        'back_photo_path',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'selfie_path',
        'automated_analysis_raw',
        'ocr_extracted_document_number',
        'ocr_extracted_full_name',
        'ocr_extracted_date_of_birth',
        'face_match_score',
        'liveness_passed',
        'automated_check_status',
        'duplicate_check',
        'pending_face_embedding',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'automated_analysis_raw' => 'array',
            'ocr_extracted_date_of_birth' => 'date',
            'liveness_passed' => 'boolean',
            'duplicate_check' => 'array',
            'pending_face_embedding' => 'encrypted:array',
        ];
    }

    /**
     * Le moteur automatisé n'a pas encore analysé ce document (aucun selfie
     * fourni, ou pas encore traité par AnalyzeDocumentJob).
     */
    public function automatedCheckNotRun(): bool
    {
        return $this->automated_check_status === 'not_run';
    }

    /**
     * Identité utilisée par le contrôle de doublons de visage : d'abord ce que
     * l'OCR a lu sur la pièce, à défaut ce que l'utilisateur a déclaré.
     *
     * @return array{document_number: ?string, full_name: ?string, date_of_birth: ?string}
     */
    public function identityForDuplicateCheck(): array
    {
        $user = $this->user;
        $declaredName = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));

        return [
            'document_number' => $this->ocr_extracted_document_number ?: $this->document_number,
            'full_name' => $this->ocr_extracted_full_name ?: ($declaredName !== '' ? $declaredName : null),
            'date_of_birth' => ($this->ocr_extracted_date_of_birth ?? $user?->date_of_birth)?->toDateString(),
        ];
    }

    /**
     * Get the user that owns the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified the document.
     */
    public function verifiedBy()
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    /**
     * Get the history records for this document.
     */
    public function histories()
    {
        return $this->hasMany(DocumentHistory::class)->latest();
    }

    /**
     * Check if document is pending.
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }
}
