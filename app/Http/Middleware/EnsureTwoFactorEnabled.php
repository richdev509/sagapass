<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * Vérifie si le 2FA est obligatoire et si l'admin l'a activé.
     * Si non, redirige vers la page de configuration 2FA.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si le 2FA est obligatoire dans les paramètres système
        $force2FA = SystemSetting::get('force_2fa_for_admins', false);
        
        if (!$force2FA) {
            // Si le 2FA n'est pas obligatoire, laisser passer
            return $next($request);
        }
        
        $admin = auth('admin')->user();
        
        // Si l'admin n'est pas connecté, laisser passer (géré par auth middleware)
        if (!$admin) {
            return $next($request);
        }
        
        // Vérifier si l'admin a activé le 2FA
        if (!$admin->hasTwoFactorEnabled()) {
            // Exempter les routes 2FA pour éviter une boucle de redirection
            $exemptedRoutes = [
                'admin.two-factor.*',
                'admin.logout',
            ];
            
            foreach ($exemptedRoutes as $pattern) {
                if ($request->routeIs($pattern)) {
                    return $next($request);
                }
            }
            
            // Rediriger vers la page de configuration 2FA avec un message
            return redirect()
                ->route('admin.two-factor.enable')
                ->with('warning', 'L\'authentification à deux facteurs est obligatoire. Veuillez la configurer maintenant.');
        }
        
        return $next($request);
    }
}
