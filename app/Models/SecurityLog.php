<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'type',
        'severity',
        'method',
        'url',
        'user_agent',
        'payload',
        'description',
        'is_blocked',
        'blocked_until',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_blocked' => 'boolean',
        'blocked_until' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Types d'attaques
    const TYPE_SQL_INJECTION = 'sql_injection';
    const TYPE_XSS = 'xss';
    const TYPE_PATH_TRAVERSAL = 'path_traversal';
    const TYPE_BRUTE_FORCE = 'brute_force';
    const TYPE_RATE_LIMIT = 'rate_limit';
    const TYPE_SUSPICIOUS = 'suspicious';
    const TYPE_UNAUTHORIZED = 'unauthorized';

    // Niveaux de sévérité
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Relation avec User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log une tentative d'attaque
     */
    public static function logAttack(array $data): self
    {
        return self::create([
            'ip_address' => $data['ip'] ?? request()->ip(),
            'type' => $data['type'],
            'severity' => $data['severity'] ?? self::SEVERITY_MEDIUM,
            'method' => $data['method'] ?? request()->method(),
            'url' => $data['url'] ?? request()->fullUrl(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'payload' => $data['payload'] ?? request()->all(),
            'description' => $data['description'] ?? null,
            'is_blocked' => $data['is_blocked'] ?? false,
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);
    }

    /**
     * Statistiques par type
     */
    public static function getStatsByType()
    {
        return self::selectRaw('type, COUNT(*) as count, MAX(created_at) as last_attack')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Top IP attaquantes
     */
    public static function getTopAttackingIPs(int $limit = 10)
    {
        return self::selectRaw('ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit($limit)
            ->get();
    }

    /**
     * Attaques récentes
     */
    public static function getRecentAttacks(int $limit = 50)
    {
        return self::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Statistiques des dernières 24h
     */
    public static function getStats24Hours()
    {
        $now = now();
        $yesterday = $now->copy()->subDay();

        return [
            'total_attacks' => self::where('created_at', '>=', $yesterday)->count(),
            'blocked_count' => self::where('created_at', '>=', $yesterday)->where('is_blocked', true)->count(),
            'critical_count' => self::where('created_at', '>=', $yesterday)->where('severity', self::SEVERITY_CRITICAL)->count(),
            'unique_ips' => self::where('created_at', '>=', $yesterday)->distinct('ip_address')->count('ip_address'),
        ];
    }

    /**
     * Graphique par heure (24h)
     */
    public static function getHourlyChart()
    {
        return self::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }
}
