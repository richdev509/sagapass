<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Empreinte faciale d'une personne déjà vérifiée — registre global (tous
 * partenaires) servant à la détection de doublons, voir FaceDuplicateService.
 * L'empreinte est une donnée biométrique : toujours chiffrée au repos.
 */
class FaceEmbedding extends Model
{
    protected $fillable = [
        'embedding',
        'document_type',
        'document_number',
        'full_name',
        'date_of_birth',
        'developer_application_id',
        'partner_verification_session_id',
        'partner_verified_identity_id',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'encrypted:array',
            'date_of_birth' => 'date',
        ];
    }

    public function developerApplication(): BelongsTo
    {
        return $this->belongsTo(DeveloperApplication::class);
    }

    public function partnerVerificationSession(): BelongsTo
    {
        return $this->belongsTo(PartnerVerificationSession::class);
    }
}
