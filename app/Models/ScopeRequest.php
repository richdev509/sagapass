<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeRequest extends Model
{
    protected $fillable = [
        'application_id',
        'requested_scopes',
        'justification',
        'status',
        'reviewed_by',
        'admin_comment',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_scopes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relation avec l'application
     */
    public function application()
    {
        return $this->belongsTo(DeveloperApplication::class, 'application_id');
    }

    /**
     * Relation avec l'admin qui a examiné la demande
     */
    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Vérifier si la demande est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si la demande est approuvée
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Vérifier si la demande est rejetée
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
