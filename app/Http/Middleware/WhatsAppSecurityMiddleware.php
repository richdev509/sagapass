<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier la signature du webhook WhatsApp
        if (config('whatsapp.verify_signature')) {
            if (!$this->verifySignature($request)) {
                Log::warning('WhatsApp webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Vérifier que la requête vient de Meta/Facebook
        if (!$this->isFromMeta($request)) {
            Log::warning('WhatsApp webhook not from Meta IP', [
                'ip' => $request->ip(),
            ]);

            // En production, on pourrait rejeter
            // Pour le dev/test, on laisse passer
            if (config('app.env') === 'production') {
                return response()->json(['error' => 'Unauthorized origin'], 403);
            }
        }

        return $next($request);
    }

    /**
     * Vérifier la signature HMAC du webhook
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $payload,
            config('whatsapp.app_secret')
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vérifier que la requête vient des serveurs Meta
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isFromMeta(Request $request): bool
    {
        $ip = $request->ip();

        // Liste des plages IP de Meta/Facebook (à jour en 2026)
        // Source: https://developers.facebook.com/docs/whatsapp/cloud-api/support/network-requirements
        $metaIpRanges = [
            '31.13.24.0/21',
            '31.13.64.0/18',
            '66.220.144.0/20',
            '69.63.176.0/20',
            '69.171.224.0/19',
            '74.119.76.0/22',
            '102.132.96.0/20',
            '103.4.96.0/22',
            '129.134.0.0/16',
            '157.240.0.0/16',
            '173.252.64.0/18',
            '179.60.192.0/22',
            '185.60.216.0/22',
            '204.15.20.0/22',
        ];

        foreach ($metaIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        // Autoriser localhost pour le développement
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si une IP est dans une plage CIDR
     *
     * @param  string  $ip
     * @param  string  $range
     * @return bool
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        }

        // Pour IPv6 (optionnel)
        return false;
    }
}
