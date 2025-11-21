<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'attempts',
        'blocked_until',
        'is_permanent',
        'blocked_by',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_permanent' => 'boolean',
    ];

    /**
     * Relation avec Admin qui a bloqué
     */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'blocked_by');
    }

    /**
     * Vérifier si une IP est bloquée
     */
    public static function isBlocked(string $ip): bool
    {
        $blocked = self::where('ip_address', $ip)->first();

        if (!$blocked) {
            return false;
        }

        // Si blocage permanent
        if ($blocked->is_permanent) {
            return true;
        }

        // Si blocage temporaire expiré
        if ($blocked->blocked_until && $blocked->blocked_until->isPast()) {
            $blocked->delete();
            return false;
        }

        return true;
    }

    /**
     * Bloquer une IP
     */
    public static function blockIp(string $ip, string $reason = null, int $hours = 24, bool $permanent = false): self
    {
        return self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason ?? 'Tentatives suspectes répétées',
                'attempts' => \DB::raw('attempts + 1'),
                'blocked_until' => $permanent ? null : now()->addHours($hours),
                'is_permanent' => $permanent,
                'blocked_by' => auth('admin')->id(),
            ]
        );
    }

    /**
     * Débloquer une IP
     */
    public static function unblockIp(string $ip): bool
    {
        return self::where('ip_address', $ip)->delete() > 0;
    }

    /**
     * Obtenir toutes les IP bloquées actives
     */
    public static function getActiveBlocks()
    {
        return self::where(function ($query) {
            $query->where('is_permanent', true)
                  ->orWhere('blocked_until', '>', now());
        })
        ->orderByDesc('created_at')
        ->get();
    }

    /**
     * Nettoyer les blocages expirés
     */
    public static function cleanExpired()
    {
        return self::where('is_permanent', false)
            ->where('blocked_until', '<', now())
            ->delete();
    }
}
