<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedForOAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si l'utilisateur n'est pas authentifié
        if (!Auth::check()) {
            // Stocker tous les paramètres OAuth dans la session pour les préserver
            session(['oauth_params' => $request->all()]);

            // Rediriger vers la page de connexion OAuth personnalisée avec les paramètres
            return redirect()->route('oauth.login', $request->all());
        }

        return $next($request);
    }
}

