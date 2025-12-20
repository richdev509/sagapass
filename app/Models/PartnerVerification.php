<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerVerification extends Model
{
    protected $fillable = [
        'partner_id',
        'partner_reference',
        'user_id',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer par partenaire
     */
    public function scopeByPartner($query, string $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
