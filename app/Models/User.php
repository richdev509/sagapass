<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        ];
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
