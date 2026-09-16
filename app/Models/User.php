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
        'email_verified_at',
        'password',
        'date_of_birth',
        'phone',
        'niu',
        'address',
        'profile_photo',
        'verification_status',
        'account_status',
        // Nouveaux champs pour système à 2 niveaux
        'account_level',
        'verification_level',
        'verified_at',
        'profile_picture',
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
            // Nouveaux casts
            'verified_at' => 'datetime',
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
     * Get verification documents for mobile registration.
     */
    public function verificationDocuments()
    {
        return $this->hasMany(UserVerificationDocument::class);
    }

    /**
     * Get the latest verification document for mobile registration.
     */
    public function verificationDocument()
    {
        return $this->hasOne(UserVerificationDocument::class)->latest();
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
     * Override email verification to use same structure as DocumentRejectedMail
     */
    public function sendEmailVerificationNotification()
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        // Structure identique à DocumentRejectedMail
        \Illuminate\Support\Facades\Mail::to($this->email)->send(
            new \App\Mail\EmailVerificationMail($this, $verificationUrl)
        );
    }
}
