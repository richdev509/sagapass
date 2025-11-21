<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityCheck
{
    /**
     * Patterns de détection d'attaques
     */
    private array $sqlInjectionPatterns = [
        '/(\%27)|(\')|(\-\-)|(\%23)|(#)/ix',
        '/(union|select|insert|update|delete|drop|create|alter|exec|script|javascript|eval)/ix',
        '/\bor\b.*=.*\bor\b/ix',
        '/1\s*=\s*1/ix',
    ];

    private array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/eval\(/i',
        '/expression\(/i',
    ];

    private array $pathTraversalPatterns = [
        '/\.\.[\/\\\\]/i',
        '/\.\.%2f/i',
        '/\.\.%5c/i',
    ];

    /**
     * Nombre de tentatives avant blocage automatique
     */
    private int $maxAttempts = 15;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Vérifier si l'IP est déjà bloquée
        if (BlockedIp::isBlocked($ip)) {
            SecurityLog::logAttack([
                'ip_address' => $ip,
                'type' => SecurityLog::TYPE_UNAUTHORIZED,
                'severity' => SecurityLog::SEVERITY_HIGH,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'description' => 'Tentative d\'accès depuis une IP bloquée',
                'is_blocked' => true,
            ]);

            return response()->json([
                'message' => 'Accès refusé. Votre adresse IP a été bloquée pour activité suspecte.',
                'error' => 'IP_BLOCKED'
            ], 403);
        }

        // Analyser tous les inputs pour détecter des attaques
        $attackDetected = $this->detectAttacks($request);

        if ($attackDetected) {
            // Logger l'attaque
            SecurityLog::logAttack([
                'ip_address' => $ip,
                'type' => $attackDetected['type'],
                'severity' => $attackDetected['severity'],
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'payload' => $attackDetected['payload'],
                'description' => $attackDetected['description'],
                'is_blocked' => false,
            ]);

            // Compter le nombre de tentatives récentes (dernières 24h)
            $recentAttempts = SecurityLog::where('ip_address', $ip)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            // Bloquer automatiquement si seuil dépassé
            if ($recentAttempts >= $this->maxAttempts) {
                BlockedIp::blockIp(
                    $ip,
                    "Blocage automatique: {$recentAttempts} tentatives suspectes détectées",
                    24, // 24 heures
                    false
                );

                SecurityLog::logAttack([
                    'ip_address' => $ip,
                    'type' => SecurityLog::TYPE_BRUTE_FORCE,
                    'severity' => SecurityLog::SEVERITY_CRITICAL,
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'description' => "IP bloquée automatiquement après {$recentAttempts} tentatives",
                    'is_blocked' => true,
                ]);

                return response()->json([
                    'message' => 'Trop de tentatives suspectes. Votre IP a été bloquée pour 24 heures.',
                    'error' => 'SECURITY_VIOLATION'
                ], 403);
            }

            // Pour les attaques critiques, bloquer immédiatement
            if ($attackDetected['severity'] === SecurityLog::SEVERITY_CRITICAL) {
                return response()->json([
                    'message' => 'Requête refusée pour des raisons de sécurité.',
                    'error' => 'MALICIOUS_REQUEST'
                ], 400);
            }
        }

        return $next($request);
    }

    /**
     * Détecter les attaques dans la requête
     */
    private function detectAttacks(Request $request): ?array
    {
        // Récupérer tous les inputs
        $inputs = array_merge(
            $request->all(),
            $request->query->all(),
            $request->headers->all()
        );

        foreach ($inputs as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            // Détecter SQL Injection
            foreach ($this->sqlInjectionPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return [
                        'type' => SecurityLog::TYPE_SQL_INJECTION,
                        'severity' => SecurityLog::SEVERITY_CRITICAL,
                        'payload' => [
                            'parameter' => $key,
                            'value' => substr($value, 0, 200),
                            'pattern_matched' => $pattern,
                        ],
                        'description' => "Tentative d'injection SQL détectée dans le paramètre '{$key}'",
                    ];
                }
            }

            // Détecter XSS
            foreach ($this->xssPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return [
                        'type' => SecurityLog::TYPE_XSS,
                        'severity' => SecurityLog::SEVERITY_HIGH,
                        'payload' => [
                            'parameter' => $key,
                            'value' => substr($value, 0, 200),
                            'pattern_matched' => $pattern,
                        ],
                        'description' => "Tentative de Cross-Site Scripting (XSS) détectée dans '{$key}'",
                    ];
                }
            }

            // Détecter Path Traversal
            foreach ($this->pathTraversalPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return [
                        'type' => SecurityLog::TYPE_PATH_TRAVERSAL,
                        'severity' => SecurityLog::SEVERITY_HIGH,
                        'payload' => [
                            'parameter' => $key,
                            'value' => substr($value, 0, 200),
                            'pattern_matched' => $pattern,
                        ],
                        'description' => "Tentative de Path Traversal détectée dans '{$key}'",
                    ];
                }
            }
        }

        return null;
    }
}
