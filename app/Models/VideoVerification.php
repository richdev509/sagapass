<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoVerification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'video_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'notes',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the video verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the video.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected verifications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Approve the video verification.
     */
    public function approve(Admin $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'video_status' => 'approved',
            'video_verified_at' => now(),
            'verification_level' => 'video',
        ]);
    }

    /**
     * Reject the video verification.
     */
    public function reject(Admin $admin, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'notes' => $notes,
        ]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'video_status' => 'rejected',
            'video_rejection_reason' => $reason,
        ]);
    }
}
