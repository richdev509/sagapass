<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthConnectionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_connection_logs';

    protected $fillable = [
        'user_id',
        'application_id',
        'authorization_id',
        'action',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'scopes',
        'connected_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'connected_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'application
     */
    public function application()
    {
        return $this->belongsTo(DeveloperApplication::class, 'application_id');
    }

    /**
     * Relation avec l'autorisation
     */
    public function authorization()
    {
        return $this->belongsTo(UserAuthorization::class, 'authorization_id');
    }

    /**
     * Déterminer le type de device depuis le user agent
     */
    public static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Extraire le navigateur du user agent
     */
    public static function detectBrowser(string $userAgent): string
    {
        if (stripos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (stripos($userAgent, 'Edg') !== false) return 'Edge';
        if (stripos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (stripos($userAgent, 'Safari') !== false) return 'Safari';
        if (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) return 'Opera';

        return 'Unknown';
    }

    /**
     * Extraire la plateforme du user agent
     */
    public static function detectPlatform(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (stripos($userAgent, 'windows') !== false) return 'Windows';
        if (stripos($userAgent, 'mac') !== false) return 'macOS';
        if (stripos($userAgent, 'linux') !== false) return 'Linux';
        if (stripos($userAgent, 'android') !== false) return 'Android';
        if (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) return 'iOS';

        return 'Unknown';
    }
}
