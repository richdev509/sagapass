<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'date_of_birth',
        'phone',
        'address',
        'profile_photo',
        'verification_status',
        'account_status',
        'is_developer',
        'company_name',
        'developer_bio',
        'developer_website',
        'developer_verified_at',
        // Nouveaux champs pour système à 2 niveaux
        'account_level',
        'verification_level',
        'verified_at',
        'profile_picture',
        'verification_video',
        'video_verified_at',
        'video_status',
        'video_rejection_reason',
        'video_consent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_developer' => 'boolean',
            'developer_verified_at' => 'datetime',
            // Nouveaux casts
            'verified_at' => 'datetime',
            'video_verified_at' => 'datetime',
            'video_consent_at' => 'datetime',
        ];
    }

    // ============================================
    // MÉTHODES POUR SYSTÈME À 2 NIVEAUX
    // ============================================

    /**
     * Check if user has a Basic account level.
     */
    public function isBasicAccount(): bool
    {
        return $this->account_level === 'basic';
    }

    /**
     * Check if user has a Verified account level.
     */
    public function isVerifiedAccount(): bool
    {
        return $this->account_level === 'verified';
    }

    /**
     * Check if video verification is pending.
     */
    public function isVideoPending(): bool
    {
        return $this->video_status === 'pending';
    }

    /**
     * Check if video has been approved.
     */
    public function isVideoApproved(): bool
    {
        return $this->video_status === 'approved';
    }

    /**
     * Check if video has been rejected.
     */
    public function isVideoRejected(): bool
    {
        return $this->video_status === 'rejected';
    }

    /**
     * Check if user needs video verification.
     */
    public function needsVideoVerification(): bool
    {
        return empty($this->verification_video) ||
               $this->video_status === 'none' ||
               $this->video_status === 'rejected';
    }

    /**
     * Check if user can upgrade to Verified account.
     */
    public function canUpgradeToVerified(): bool
    {
        return $this->isBasicAccount() &&
               $this->isVideoApproved() &&
               $this->email_verified_at !== null;
    }

    /**
     * Check if user has a document pending verification.
     */
    public function hasDocumentPending(): bool
    {
        return $this->documents()
                    ->where('status', 'pending')
                    ->exists();
    }

    /**
     * Upgrade user from Basic to Verified account.
     */
    public function upgradeToVerified(): void
    {
        $this->update([
            'account_level' => 'verified',
            'verification_level' => 'document',
            'verified_at' => now(),
        ]);
    }

    /**
     * Get allowed OAuth scopes based on account level.
     */
    public function getAllowedScopes(): array
    {
        if ($this->isVerifiedAccount()) {
            return [
                'profile',
                'email',
                'documents',
                'documents:verified',
                'address',
                'phone',
            ];
        }

        // Scopes limités pour compte Basic
        return [
            'profile:basic',
            'email',
        ];
    }

    /**
     * Get user badge based on account level and video status.
     */
    public function getBadgeAttribute(): string
    {
        if ($this->isVerifiedAccount()) {
            return 'verified';
        }

        if ($this->isVideoApproved()) {
            return 'basic-video';
        }

        if ($this->isVideoPending()) {
            return 'basic-pending';
        }

        return 'basic';
    }

    /**
     * Get profile picture URL.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }

        return Storage::url($this->profile_picture);
    }

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Get all video verifications for the user.
     */
    public function videoVerifications()
    {
        return $this->hasMany(VideoVerification::class);
    }

    /**
     * Get all documents for the user.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get all consents for the user.
     */
    public function consents()
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * Get all access tokens for the user.
     */
    public function accessTokens()
    {
        return $this->hasMany(OAuthAccessToken::class);
    }

    /**
     * Get all developer applications owned by this user.
     */
    public function developerApplications()
    {
        return $this->hasMany(DeveloperApplication::class);
    }

    /**
     * Get the developer profile for this user.
     */
    public function developer()
    {
        return $this->hasOne(Developer::class);
    }

    /**
     * Get all OAuth authorizations (consents) for this user.
     */
    public function oauthAuthorizations()
    {
        return $this->hasMany(UserAuthorization::class);
    }

    /**
     * Get active OAuth authorizations (not revoked).
     */
    public function activeOAuthAuthorizations()
    {
        return $this->oauthAuthorizations()->whereNull('revoked_at');
    }

    /**
     * Check if user's identity is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Check if user is a developer.
     */
    public function isDeveloper(): bool
    {
        return $this->developer()->exists();
    }

    /**
     * Check if developer account is verified.
     */
    public function isDeveloperVerified(): bool
    {
        return $this->developer && $this->developer->isVerified();
    }

    /**
     * Check if developer account is active.
     */
    public function isDeveloperActive(): bool
    {
        return $this->developer && $this->developer->isActive();
    }
}
