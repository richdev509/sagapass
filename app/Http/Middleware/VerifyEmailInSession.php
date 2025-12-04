<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailInSession
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'email a été vérifié avant d'accéder aux étapes suivantes
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier que la session d'inscription existe
        if (!session()->has('registration')) {
            return redirect()
                ->route('register.basic.step1')
                ->with('error', 'Votre session a expiré. Veuillez recommencer l\'inscription.');
        }

        $registration = session('registration');

        // Vérifier que l'email a été vérifié
        if (!isset($registration['email_verified']) || $registration['email_verified'] !== true) {
            return redirect()
                ->route('register.basic.verify-email')
                ->with('error', 'Vous devez d\'abord vérifier votre adresse email.');
        }

        // Vérifier que la session n'a pas expiré (24h max)
        if (isset($registration['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($registration['expires_at']);

            if ($expiresAt->isPast()) {
                // Session expirée - nettoyer et rediriger
                session()->forget('registration');

                return redirect()
                    ->route('register.basic.step1')
                    ->with('error', 'Votre session d\'inscription a expiré (24h maximum). Veuillez recommencer.');
            }
        }

        // Vérification de sécurité supplémentaire : IP cohérente (optionnel)
        if (isset($registration['ip_address']) && $registration['ip_address'] !== $request->ip()) {
            // Log de sécurité
            \Log::warning('Tentative d\'accès à l\'inscription avec une IP différente', [
                'email' => $registration['email'] ?? 'N/A',
                'original_ip' => $registration['ip_address'],
                'current_ip' => $request->ip(),
            ]);

            // On peut soit bloquer, soit juste logger
            // Pour l'instant on continue mais on pourrait bloquer pour plus de sécurité
        }

        return $next($request);
    }
}
