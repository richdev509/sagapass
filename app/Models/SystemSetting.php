<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Obtenir une valeur de paramètre avec cache
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Définir une valeur de paramètre
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
            ]
        );

        Cache::forget("setting_{$key}");
    }

    /**
     * Convertir la valeur selon le type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Vérifier si le mode maintenance est activé
     */
    public static function isMaintenanceMode(): bool
    {
        return self::get('maintenance_mode', false);
    }

    /**
     * Vérifier si le mode beta est activé
     */
    public static function isBetaMode(): bool
    {
        return self::get('beta_mode', true);
    }

    /**
     * Obtenir le lien WhatsApp
     */
    public static function getWhatsAppLink(): string
    {
        return self::get('whatsapp_support_link', 'https://wa.me/221000000000');
    }
}
