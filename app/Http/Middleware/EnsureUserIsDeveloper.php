<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDeveloper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('developers.login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        if (!auth()->user()->developer()->exists()) {
            return redirect()->route('developers.register')
                ->with('error', 'Vous devez créer un profil développeur pour accéder à cette section.');
        }

        if (auth()->user()->developer->status === 'suspended') {
            auth()->logout();
            return redirect()->route('developers.login')
                ->with('error', 'Votre compte développeur a été suspendu. Contactez l\'administrateur.');
        }

        return $next($request);
    }
}
