<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Protection contre le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêcher le MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protection XSS intégrée au navigateur
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Forcer HTTPS (31536000 = 1 an)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ]));

        // Contrôle des référents
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (anciennement Feature-Policy)
        // Autoriser camera et microphone pour l'inscription
        $response->headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=*',  // Autoriser microphone
            'camera=*',      // Autoriser caméra
            'payment=()',
        ]));

        return $response;
    }
}
