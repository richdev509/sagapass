<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentHistory extends Model
{
    protected $fillable = [
        'document_id',
        'admin_id',
        'action',
        'old_status',
        'new_status',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the document associated with this history.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the admin who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
