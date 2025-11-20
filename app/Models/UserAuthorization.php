<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuthorization extends Model
{
    protected $fillable = [
        'user_id',
        'application_id',
        'scopes',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'scopes' => 'array',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auth) {
            if (!$auth->granted_at) {
                $auth->granted_at = now();
            }
        });
    }

    /**
     * Get the user that owns the authorization.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application that this authorization is for.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(DeveloperApplication::class, 'application_id');
    }

    /**
     * Get the connection logs for this authorization.
     */
    public function connectionLogs()
    {
        return $this->hasMany(OAuthConnectionLog::class, 'authorization_id');
    }

    /**
     * Get the most recent connection log.
     */
    public function lastConnection()
    {
        return $this->hasOne(OAuthConnectionLog::class, 'authorization_id')->latestOfMany('connected_at');
    }

    /**
     * Check if the authorization is active (not revoked).
     */
    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }

    /**
     * Revoke the authorization.
     */
    public function revoke(): void
    {
        $this->update([
            'revoked_at' => now(),
        ]);

        // Revoke all active tokens for this user and application
        $this->user->tokens()
            ->where('name', 'oauth:' . $this->application_id)
            ->delete();
    }

    /**
     * Check if a scope is authorized.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }
}
