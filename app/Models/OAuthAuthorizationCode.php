<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OAuthAuthorizationCode extends Model
{
    protected $table = 'oauth_authorization_codes';

    protected $fillable = [
        'application_id',
        'user_id',
        'code',
        'redirect_uri',
        'scopes',
        'state',
        'code_challenge',
        'code_challenge_method',
        'expires_at',
        'used',
        'used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($code) {
            if (!$code->code) {
                $code->code = Str::random(80);
            }
            if (!$code->expires_at) {
                $code->expires_at = now()->addMinutes(10);
            }
        });
    }

    /**
     * Get the application that owns the authorization code.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(DeveloperApplication::class, 'application_id');
    }

    /**
     * Get the user that owns the authorization code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the authorization code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the authorization code is valid.
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    /**
     * Mark the authorization code as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Verify PKCE code challenge.
     */
    public function verifyCodeChallenge(string $codeVerifier): bool
    {
        if (!$this->code_challenge) {
            return true; // PKCE not used
        }

        if ($this->code_challenge_method === 'S256') {
            $hash = hash('sha256', $codeVerifier);
            $challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
            return $challenge === $this->code_challenge;
        }

        return $codeVerifier === $this->code_challenge;
    }
}
