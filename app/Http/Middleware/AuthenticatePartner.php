<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class AuthenticatePartner
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que la requête provient d'un partenaire authentifié via token API
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier la présence du token Bearer
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Token d\'authentification manquant',
                'message' => 'Utilisez: Authorization: Bearer {votre_token}'
            ], 401);
        }

        // Valider le token
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'error' => 'Token invalide ou expiré'
            ], 401);
        }

        // Vérifier que c'est un token d'application OAuth (client_credentials)
        $tokenable = $accessToken->tokenable;

        if (!$tokenable || get_class($tokenable) !== 'App\\Models\\User') {
            return response()->json([
                'success' => false,
                'error' => 'Ce token n\'est pas autorisé pour l\'API Partner'
            ], 403);
        }

        // Vérifier que le token est de type client_credentials (commence par "client_credentials:")
        if (!str_starts_with($accessToken->name, 'client_credentials:')) {
            return response()->json([
                'success' => false,
                'error' => 'Ce token n\'est pas un token client_credentials. Utilisez grant_type=client_credentials'
            ], 403);
        }

        // Extraire le client_id du nom du token
        $parts = explode(':', $accessToken->name);
        $clientId = $parts[1] ?? null;

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'error' => 'Token invalide: client_id manquant'
            ], 403);
        }

        // Charger l'application OAuth
        $application = \App\Models\DeveloperApplication::where('client_id', $clientId)->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'error' => 'Application OAuth non trouvée'
            ], 403);
        }

        // Vérifier que l'application est approuvée
        if ($application->status !== 'approved') {
            return response()->json([
                'success' => false,
                'error' => 'Votre application n\'est pas encore approuvée'
            ], 403);
        }

        // Vérifier les scopes requis pour l'API Partner
        if (!$accessToken->can('partner:create-citizen')) {
            return response()->json([
                'success' => false,
                'error' => 'Token sans permission partner:create-citizen',
                'required_scope' => 'partner:create-citizen'
            ], 403);
        }

        // Ajouter les infos du partenaire à la requête
        $request->merge([
            'partner_id' => $application->client_id,
            'partner_name' => $application->name,
            'partner_app' => $application,
        ]);

        // Log de l'accès (pour tracking)
        Log::info('Partner API Access', [
            'partner_id' => $application->client_id,
            'partner_name' => $application->name,
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
