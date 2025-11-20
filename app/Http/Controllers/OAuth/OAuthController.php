<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthConnectionLog;
use App\Models\UserAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Afficher l'écran de consentement OAuth
     */
    public function showAuthorization(Request $request)
    {
        // Valider les paramètres OAuth
        $request->validate([
            'client_id' => ['required', 'string', 'exists:developer_applications,client_id'],
            'redirect_uri' => ['required', 'url'],
            'response_type' => ['required', 'in:code'],
            'scope' => ['nullable', 'string'],
            'state' => ['required', 'string'],
            'code_challenge' => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256,plain'],
        ]);

        // Charger l'application
        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        // Vérifier que l'application est approuvée
        if (!$application->isApproved()) {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'unauthorized_client',
                'error_description' => 'L\'application n\'est pas approuvée.',
                'state' => $request->state,
            ]));
        }

        // Vérifier que le redirect_uri est autorisé
        if (!$application->isValidRedirectUri($request->redirect_uri)) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'L\'URI de redirection n\'est pas autorisée pour cette application.',
            ], 400);
        }

        // Parser les scopes demandés
        $requestedScopes = $request->scope ? explode(' ', $request->scope) : ['profile'];

        // Vérifier que tous les scopes sont autorisés pour cette application
        foreach ($requestedScopes as $scope) {
            if (!$application->hasScope($scope)) {
                return redirect($request->redirect_uri . '?' . http_build_query([
                    'error' => 'invalid_scope',
                    'error_description' => "Le scope '{$scope}' n'est pas autorisé pour cette application.",
                    'state' => $request->state,
                ]));
            }
        }

        // Vérifier si l'utilisateur a déjà autorisé cette application avec ces scopes
        $user = Auth::user();
        $existingAuth = UserAuthorization::where('user_id', $user->id)
            ->where('application_id', $application->id)
            ->whereNull('revoked_at')
            ->first();

        // Si l'autorisation existe et couvre tous les scopes demandés, auto-approuver
        if ($existingAuth && $this->scopesCovered($requestedScopes, $existingAuth->scopes)) {
            return $this->issueAuthorizationCode($request, $application, $user, $requestedScopes, true);
        }

        // Sinon, afficher l'écran de consentement
        return view('oauth.authorize', [
            'application' => $application,
            'scopes' => $this->getScopeDescriptions($requestedScopes),
            'user' => $user,
            'params' => [
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'scope' => $request->scope,
                'state' => $request->state,
                'code_challenge' => $request->code_challenge,
                'code_challenge_method' => $request->code_challenge_method,
            ],
        ]);
    }

    /**
     * Traiter l'approbation ou le refus de l'utilisateur
     */
    public function approveOrDeny(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'redirect_uri' => ['required', 'url'],
            'scope' => ['nullable', 'string'],
            'state' => ['required', 'string'],
            'action' => ['required', 'in:approve,deny'],
            'code_challenge' => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256,plain'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        // Si l'utilisateur refuse
        if ($request->action === 'deny') {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'access_denied',
                'error_description' => 'L\'utilisateur a refusé l\'autorisation.',
                'state' => $request->state,
            ]));
        }

        // Si l'utilisateur approuve
        $scopes = $request->scope ? explode(' ', $request->scope) : ['profile'];
        return $this->issueAuthorizationCode($request, $application, Auth::user(), $scopes);
    }

    /**
     * Émettre un code d'autorisation
     */
    private function issueAuthorizationCode(Request $request, DeveloperApplication $application, $user, array $scopes, bool $isReconnection = false)
    {
        // Créer ou mettre à jour l'autorisation utilisateur
        $authorization = UserAuthorization::updateOrCreate(
            [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'revoked_at' => null,
            ],
            [
                'scopes' => $scopes,
                'granted_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );

        // Enregistrer le log de connexion
        $userAgent = request()->userAgent() ?? '';
        OAuthConnectionLog::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'authorization_id' => $authorization->id,
            'action' => $isReconnection ? 'reconnected' : 'authorized',
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'device_type' => OAuthConnectionLog::detectDeviceType($userAgent),
            'browser' => OAuthConnectionLog::detectBrowser($userAgent),
            'platform' => OAuthConnectionLog::detectPlatform($userAgent),
            'scopes' => $scopes,
            'connected_at' => now(),
        ]);

        // Créer le code d'autorisation
        $authCode = OAuthAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'redirect_uri' => $request->redirect_uri,
            'scopes' => $scopes,
            'state' => $request->state,
            'code_challenge' => $request->code_challenge,
            'code_challenge_method' => $request->code_challenge_method,
        ]);

        // Rediriger vers l'application avec le code
        return redirect($request->redirect_uri . '?' . http_build_query([
            'code' => $authCode->code,
            'state' => $request->state,
        ]));
    }

    /**
     * Échanger le code d'autorisation contre un access token
     */
    public function issueToken(Request $request)
    {
        $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'client_secret' => ['required', 'string'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'code_verifier' => ['nullable', 'string'],
        ]);

        // Charger l'application
        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        // Vérifier le client secret
        if (!$application->verifySecret($request->client_secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Les identifiants du client sont invalides.',
            ], 401);
        }

        // Charger le code d'autorisation
        $authCode = OAuthAuthorizationCode::where('code', $request->code)
            ->where('application_id', $application->id)
            ->firstOrFail();

        // Vérifier que le code n'est pas utilisé
        if ($authCode->used) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Le code d\'autorisation a déjà été utilisé.',
            ], 400);
        }

        // Vérifier que le code n'est pas expiré
        if ($authCode->isExpired()) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Le code d\'autorisation a expiré.',
            ], 400);
        }

        // Vérifier que le redirect_uri correspond
        if ($authCode->redirect_uri !== $request->redirect_uri) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'L\'URI de redirection ne correspond pas.',
            ], 400);
        }

        // Vérifier le code challenge PKCE si présent
        if ($authCode->code_challenge) {
            if (!$request->code_verifier) {
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'Le code_verifier est requis pour PKCE.',
                ], 400);
            }

            if (!$authCode->verifyCodeChallenge($request->code_verifier)) {
                return response()->json([
                    'error' => 'invalid_grant',
                    'error_description' => 'Le code_verifier est invalide.',
                ], 400);
            }
        }

        // Marquer le code comme utilisé
        $authCode->markAsUsed();

        // Créer un access token Sanctum
        $user = $authCode->user;
        $token = $user->createToken(
            "oauth:{$application->id}",
            $authCode->scopes
        );

        // Retourner le token
        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 heure
            'scope' => implode(' ', $authCode->scopes),
        ]);
    }

    /**
     * Révoquer un access token
     */
    public function revokeToken(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        // Extraire le token de l'en-tête Authorization
        $token = $request->bearerToken() ?? $request->token;

        // Trouver le token Sanctum
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if ($accessToken) {
            // Révoquer l'autorisation utilisateur
            $appId = explode(':', $accessToken->name)[1] ?? null;
            if ($appId) {
                UserAuthorization::where('user_id', $accessToken->tokenable_id)
                    ->where('application_id', $appId)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            // Supprimer le token
            $accessToken->delete();

            return response()->json(['message' => 'Token révoqué avec succès.']);
        }

        return response()->json(['error' => 'Token invalide.'], 400);
    }

    /**
     * Vérifier la validité d'un token (introspection)
     */
    public function introspect(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'client_secret' => ['required', 'string'],
        ]);

        // Vérifier les identifiants du client
        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();
        if (!$application->verifySecret($request->client_secret)) {
            return response()->json(['active' => false], 401);
        }

        // Trouver le token
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);

        if (!$accessToken || $accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['active' => false]);
        }

        // Retourner les informations du token
        return response()->json([
            'active' => true,
            'scope' => implode(' ', $accessToken->abilities),
            'client_id' => $request->client_id,
            'user_id' => $accessToken->tokenable_id,
            'exp' => $accessToken->expires_at ? $accessToken->expires_at->timestamp : null,
        ]);
    }

    /**
     * Vérifier si les scopes demandés sont couverts par l'autorisation existante
     */
    private function scopesCovered(array $requestedScopes, array $grantedScopes): bool
    {
        foreach ($requestedScopes as $scope) {
            if (!in_array($scope, $grantedScopes)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtenir les descriptions des scopes
     */
    private function getScopeDescriptions(array $scopes): array
    {
        $descriptions = [
            'profile' => [
                'name' => 'Profil de base',
                'description' => 'Voir votre nom, prénom et statut de vérification d\'identité',
                'icon' => 'person',
            ],
            'email' => [
                'name' => 'Adresse email',
                'description' => 'Accéder à votre adresse email vérifiée',
                'icon' => 'envelope',
            ],
            'phone' => [
                'name' => 'Numéro de téléphone',
                'description' => 'Voir votre numéro de téléphone',
                'icon' => 'phone',
            ],
            'address' => [
                'name' => 'Adresse postale',
                'description' => 'Accéder à votre adresse de résidence',
                'icon' => 'geo-alt',
            ],
            'documents' => [
                'name' => 'Documents d\'identité',
                'description' => 'Vérifier que votre identité a été confirmée (sans voir vos documents)',
                'icon' => 'shield-check',
            ],
        ];

        $result = [];
        foreach ($scopes as $scope) {
            if (isset($descriptions[$scope])) {
                $result[$scope] = $descriptions[$scope];
            }
        }

        return $result;
    }

    /**
     * Afficher la page de connexion OAuth
     */
    public function showLogin(Request $request)
    {
        // Valider les paramètres OAuth de base
        $request->validate([
            'client_id' => 'required|exists:developer_applications,client_id',
            'redirect_uri' => 'required|url',
            'state' => 'required|string',
        ]);

        // Charger l'application
        $application = DeveloperApplication::where('client_id', $request->client_id)->first();

        // Stocker les informations de l'application en session pour affichage
        session([
            'oauth_app' => [
                'name' => $application->name,
                'website' => $application->website ?? parse_url($request->redirect_uri, PHP_URL_HOST),
                'logo' => $application->logo_path ? asset('storage/' . $application->logo_path) : null,
            ]
        ]);

        return view('oauth.login');
    }

    /**
     * Traiter la connexion OAuth
     */
    public function processLogin(Request $request)
    {
        // Valider les identifiants
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tentative de connexion
        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Récupérer les paramètres OAuth
            $params = $request->only([
                'client_id', 'redirect_uri', 'response_type',
                'scope', 'state', 'code_challenge', 'code_challenge_method'
            ]);

            // Nettoyer les paramètres vides
            $params = array_filter($params);

            // Rediriger vers la page d'autorisation OAuth
            return redirect()->route('oauth.authorize', $params);
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->withInput($request->except('password'));
    }
}
