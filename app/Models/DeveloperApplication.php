<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DeveloperApplication extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'website',
        'logo_path',
        'client_id',
        'client_secret',
        'app_key',
        'redirect_uris',
        'allowed_scopes',
        'status',
        'is_trusted',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'allowed_scopes' => 'array',
        'is_trusted' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret',
        'app_key',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($app) {
            if (!$app->client_id) {
                $app->client_id = (string) Str::uuid();
            }
            if (!$app->client_secret) {
                $plainSecret = Str::random(64);
                $app->client_secret = encrypt($plainSecret);
            }
            if (!$app->app_key) {
                $plainAppKey = Str::random(64);
                $app->app_key = encrypt($plainAppKey);
            }
            if (!$app->allowed_scopes) {
                $app->allowed_scopes = ['profile'];
            }
        });
    }

    /**
     * Get the user that owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved the application.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Check if the application is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verify client secret.
     */
    public function verifySecret(string $secret): bool
    {
        try {
            return hash_equals(decrypt($this->client_secret), $secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return false;
        }
    }

    /**
     * Get the plaintext client secret (admin only).
     */
    public function getPlaintextSecret(): ?string
    {
        try {
            return decrypt($this->client_secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Regenerate client secret and return the new plaintext value.
     */
    public function regenerateSecret(): string
    {
        $plainSecret = Str::random(64);
        $this->client_secret = encrypt($plainSecret);
        $this->save();
        return $plainSecret;
    }

    /**
     * Get the plaintext app_key (admin only).
     */
    public function getPlaintextAppKey(): ?string
    {
        try {
            return $this->app_key ? decrypt($this->app_key) : null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    /**
     * Regenerate app_key and return the new plaintext value.
     */
    public function regenerateAppKey(): string
    {
        $plainAppKey = Str::random(64);
        $this->app_key = encrypt($plainAppKey);
        $this->save();
        return $plainAppKey;
    }

    /**
     * Create a Personal Access Token for this application (client_credentials grant)
     */
    public function createToken(string $name, array $abilities = ['*'])
    {
        // Utiliser le propriétaire de l'application comme contexte
        // ou créer un token "system" si besoin
        if ($this->user_id) {
            return $this->user->createToken($name, $abilities);
        }

        // Si pas d'utilisateur, utiliser un compte système (à créer)
        // Pour l'instant, retourner une erreur
        throw new \Exception('Cannot create token for application without user_id');
    }
}
