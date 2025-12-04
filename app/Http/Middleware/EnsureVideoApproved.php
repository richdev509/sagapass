<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVideoApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vérifier que l'email est vérifié
        if (!$user->email_verified_at) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez d\'abord vérifier votre adresse email avant d\'accéder aux documents.');
        }

        // Vérifier que la vidéo est approuvée
        if ($user->video_status !== 'approved') {
            $message = 'Vous devez avoir une vidéo de vérification approuvée pour accéder aux documents.';

            if ($user->video_status === 'pending') {
                $message = 'Votre vidéo de vérification est en cours de validation. Vous pourrez accéder aux documents une fois approuvée.';
            } elseif ($user->video_status === 'rejected') {
                $message = 'Votre vidéo a été rejetée. Veuillez soumettre une nouvelle vidéo pour accéder aux documents.';
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
