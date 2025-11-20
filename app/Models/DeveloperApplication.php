<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($app) {
            if (!$app->client_id) {
                $app->client_id = (string) Str::uuid();
            }
            if (!$app->client_secret) {
                $app->client_secret = bcrypt(Str::random(60));
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
     * Get authorization codes for this application.
     */
    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(OAuthAuthorizationCode::class, 'application_id');
    }

    /**
     * Get scope requests for this application.
     */
    public function scopeRequests(): HasMany
    {
        return $this->hasMany(ScopeRequest::class, 'application_id');
    }

    /**
     * Get user authorizations for this application.
     */
    public function userAuthorizations(): HasMany
    {
        return $this->hasMany(UserAuthorization::class, 'application_id');
    }

    /**
     * Check if the application is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if a redirect URI is valid.
     */
    public function isValidRedirectUri(string $uri): bool
    {
        return in_array($uri, $this->redirect_uris ?? []);
    }

    /**
     * Check if a scope is allowed.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->allowed_scopes ?? []);
    }

    /**
     * Verify client secret.
     */
    public function verifySecret(string $secret): bool
    {
        return password_verify($secret, $this->client_secret);
    }
}
