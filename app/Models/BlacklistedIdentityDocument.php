<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistedIdentityDocument extends Model
{
    protected $fillable = [
        'blacklisted_identity_id',
        'document_type',
        'document_number',
    ];

    public function blacklistedIdentity(): BelongsTo
    {
        return $this->belongsTo(BlacklistedIdentity::class);
    }
}
