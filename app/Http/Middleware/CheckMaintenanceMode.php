<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si le mode maintenance est activé
        if (SystemSetting::isMaintenanceMode()) {
            // Toujours permettre l'accès aux routes admin (pour le login admin aussi)
            if ($request->is('admin') || $request->is('admin/*')) {
                return $next($request);
            }

            // Les admins authentifiés peuvent toujours accéder
            if ($request->user('admin')) {
                return $next($request);
            }

            // Afficher la page de maintenance pour les autres
            return response()->view('maintenance', [
                'message' => SystemSetting::get('maintenance_message'),
            ], 503);
        }

        return $next($request);
    }
}
