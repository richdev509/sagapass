<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\OAuthAuthorizationCode;
use App\Models\OAuthConnectionLog;
use App\Models\UserAuthorization;
use App\Services\OAuthScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OAuthController extends Controller
{
    // ==========================================================
    // MOBILE APP-TO-APP FLOW (API JSON)
    // L'app SAGA ID appelle ces endpoints après deep link
    // ==========================================================

    /**
     * Récupérer les infos de l'app tierce pour afficher le consent screen
     * GET /api/oauth/app-info?client_id=xxx&scopes=profile,niu
     */
    public function getAppInfo(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'scopes' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->first();

        if (!$application) {
            return response()->json(['success' => false, 'message' => 'Application inconnue.', 'error' => 'invalid_client'], 404);
        }

        if (!$application->isApproved()) {
            return response()->json(['success' => false, 'message' => 'Application non approuvée.', 'error' => 'unauthorized_client'], 403);
        }

        // Vérification email : si fourni, l'email doit correspondre à l'utilisateur connecté
        $user = $request->user();
        if ($request->email && mb_strtolower($request->email) !== mb_strtolower($user->email ?? '')) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur Citoyen inconnu qui essaie de vérifier identité.',
                'error' => 'email_mismatch',
            ], 403);
        }

        $requestedScopes = $request->scopes ? array_map('trim', explode(',', $request->scopes)) : ['profile'];
        $validScopes = [];
        $invalidScopes = [];

        foreach ($requestedScopes as $scope) {
            if ($application->hasScope($scope)) {
                $validScopes[] = $scope;
            } else {
                $invalidScopes[] = $scope;
            }
        }

        if (empty($validScopes)) {
            return response()->json(['success' => false, 'message' => 'Aucun scope valide demandé.', 'error' => 'invalid_scope'], 400);
        }

        $existingAuth = UserAuthorization::where('user_id', $user->id)
            ->where('application_id', $application->id)
            ->whereNull('revoked_at')
            ->first();

        $alreadyAuthorized = $existingAuth && $this->scopesCovered($validScopes, $existingAuth->scopes);

        return response()->json([
            'success' => true,
            'message' => 'Informations de l\'application récupérées.',
            'data' => [
                'app' => [
                    'name' => $application->name,
                    'description' => $application->description,
                    'website' => $application->website,
                    'logo' => $application->logo_path ? asset('storage/' . $application->logo_path) : null,
                    'is_trusted' => $application->is_trusted,
                ],
                'scopes' => $this->getScopeDescriptions($validScopes),
                'invalid_scopes' => $invalidScopes,
                'already_authorized' => $alreadyAuthorized,
            ],
        ]);
    }

    /**
     * L'app SAGA ID appelle après consentement de l'utilisateur
     * Retourne un authorization code à transmettre à l'app tierce
     * POST /api/oauth/mobile-authorize
     */
    public function mobileAuthorize(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'scopes' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'state' => ['required', 'string'],
            'code_challenge' => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256,plain'],
            'action' => ['required', 'in:approve,deny'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        if (!$application->isApproved()) {
            return response()->json(['success' => false, 'message' => 'Application non approuvée.', 'error' => 'unauthorized_client'], 403);
        }

        if (!$application->isValidRedirectUri($request->redirect_uri)) {
            return response()->json(['success' => false, 'message' => 'URI de redirection non autorisée.', 'error' => 'invalid_redirect_uri'], 400);
        }

        $user = $request->user();

        if ($user->verification_status !== 'verified') {
            return response()->json(['success' => false, 'message' => 'Votre identité n\'est pas encore vérifiée.', 'error' => 'user_not_verified'], 403);
        }

        // Refus
        if ($request->action === 'deny') {
            return response()->json([
                'success' => true,
                'message' => 'Autorisation refusée.',
                'data' => [
                    'redirect_uri' => $request->redirect_uri,
                    'params' => [
                        'error' => 'access_denied',
                        'error_description' => 'L\'utilisateur a refusé l\'autorisation.',
                        'state' => $request->state,
                    ],
                ],
            ]);
        }

        // Approbation
        $scopes = array_map('trim', explode(',', $request->scopes));

        $authorization = UserAuthorization::updateOrCreate(
            ['user_id' => $user->id, 'application_id' => $application->id, 'revoked_at' => null],
            ['scopes' => $scopes, 'granted_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        $userAgent = $request->userAgent() ?? '';
        OAuthConnectionLog::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'authorization_id' => $authorization->id,
            'action' => 'authorized',
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => 'mobile',
            'browser' => 'SAGA ID App',
            'platform' => OAuthConnectionLog::detectPlatform($userAgent),
            'scopes' => $scopes,
            'connected_at' => now(),
        ]);

        $authCode = OAuthAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'redirect_uri' => $request->redirect_uri,
            'scopes' => $scopes,
            'state' => $request->state,
            'code_challenge' => $request->code_challenge,
            'code_challenge_method' => $request->code_challenge_method,
            'expires_at' => now()->addSeconds(60),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Autorisation accordée.',
            'data' => [
                'redirect_uri' => $request->redirect_uri,
                'params' => [
                    'code' => $authCode->code,
                    'state' => $request->state,
                ],
            ],
        ]);
    }

    // ==========================================================
    // TOKEN ENDPOINT (Server-to-Server)
    // L'app tierce échange le code contre un access token
    // ==========================================================

    /**
     * Échanger le code d'autorisation contre un access token
     * POST /oauth/token
     */
    public function issueToken(Request $request)
    {
        $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'client_secret' => ['required', 'string'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'code_verifier' => ['nullable', 'string'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        if (!$application->verifySecret($request->client_secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Identifiants client invalides.',
            ], 401);
        }

        $authCode = OAuthAuthorizationCode::where('code', $request->code)
            ->where('application_id', $application->id)
            ->first();

        if (!$authCode) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Code d\'autorisation invalide.'], 400);
        }

        if ($authCode->used) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Code d\'autorisation déjà utilisé.'], 400);
        }

        if ($authCode->isExpired()) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Code d\'autorisation expiré.'], 400);
        }

        if ($authCode->redirect_uri !== $request->redirect_uri) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'URI de redirection ne correspond pas.'], 400);
        }

        // Vérifier PKCE
        if ($authCode->code_challenge) {
            if (!$request->code_verifier) {
                return response()->json(['error' => 'invalid_request', 'error_description' => 'Le code_verifier est requis.'], 400);
            }
            if (!$authCode->verifyCodeChallenge($request->code_verifier)) {
                return response()->json(['error' => 'invalid_grant', 'error_description' => 'Code verifier invalide.'], 400);
            }
        }

        $authCode->markAsUsed();

        $user = $authCode->user;
        $token = $user->createToken("oauth:{$application->id}", $authCode->scopes);

        OAuthConnectionLog::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'action' => 'token_issued',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'device_type' => 'server',
            'browser' => 'API',
            'platform' => 'server',
            'scopes' => $authCode->scopes,
            'connected_at' => now(),
        ]);

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => implode(' ', $authCode->scopes),
        ]);
    }

    // ==========================================================
    // USERINFO ENDPOINT
    // L'app tierce utilise le token pour récupérer les données
    // ==========================================================

    /**
     * Retourner les infos de l'utilisateur selon les scopes autorisés
     * GET /oauth/userinfo
     */
    public function userInfo(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $scopes = $token->abilities ?? [];
        $data = [
            'sub' => (string) $user->id,
            'verified' => $user->verification_status === 'verified',
        ];

        if (in_array('profile', $scopes)) {
            $data['first_name'] = $user->first_name;
            $data['last_name'] = $user->last_name;
            $data['full_name'] = $user->first_name . ' ' . $user->last_name;
        }

        if (in_array('email', $scopes)) {
            $data['email'] = $user->email;
            $data['email_verified'] = !is_null($user->email_verified_at);
        }

        if (in_array('phone', $scopes)) {
            $data['phone'] = $user->phone;
        }

        if (in_array('birthdate', $scopes)) {
            $data['birthdate'] = $user->date_of_birth?->format('Y-m-d');
        }

        if (in_array('address', $scopes)) {
            $data['address'] = $user->address;
        }

        if (in_array('photo', $scopes)) {
            $data['photo_url'] = $user->profile_photo ? asset('storage/' . $user->profile_photo) : null;
        }

        if (in_array('niu', $scopes) || in_array('documents', $scopes)) {
            $data['niu'] = $user->niu;
        }

        return response()->json($data);
    }

    // ==========================================================
    // TOKEN MANAGEMENT
    // ==========================================================

    /**
     * Révoquer un access token
     * POST /oauth/revoke
     */
    public function revokeToken(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = $request->bearerToken() ?? $request->token;
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $appId = explode(':', $accessToken->name)[1] ?? null;
            if ($appId) {
                UserAuthorization::where('user_id', $accessToken->tokenable_id)
                    ->where('application_id', $appId)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }
            $accessToken->delete();
            return response()->json(['message' => 'Token révoqué avec succès.']);
        }

        return response()->json(['error' => 'Token invalide.'], 400);
    }

    /**
     * Introspection de token
     * POST /oauth/introspect
     */
    public function introspect(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'client_id' => ['required', 'exists:developer_applications,client_id'],
            'client_secret' => ['required', 'string'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();
        if (!$application->verifySecret($request->client_secret)) {
            return response()->json(['active' => false], 401);
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);

        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'scope' => implode(' ', $accessToken->abilities),
            'client_id' => $request->client_id,
            'user_id' => $accessToken->tokenable_id,
            'exp' => $accessToken->expires_at?->timestamp,
        ]);
    }

    // ==========================================================
    // WEB FLOW (consent screen navigateur — gardé pour dev portal)
    // ==========================================================

    /**
     * Afficher l'écran de consentement OAuth (web)
     */
    public function showAuthorization(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'string', 'exists:developer_applications,client_id'],
            'redirect_uri' => ['required', 'url'],
            'response_type' => ['required', 'in:code'],
            'scope' => ['nullable', 'string'],
            'state' => ['required', 'string'],
            'code_challenge' => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256,plain'],
        ]);

        $application = DeveloperApplication::where('client_id', $request->client_id)->firstOrFail();

        if (!$application->isApproved()) {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'unauthorized_client',
                'state' => $request->state,
            ]));
        }

        if (!$application->isValidRedirectUri($request->redirect_uri)) {
            return response()->json(['error' => 'invalid_redirect_uri'], 400);
        }

        $requestedScopes = $request->scope ? explode(' ', $request->scope) : ['profile'];

        foreach ($requestedScopes as $scope) {
            if (!$application->hasScope($scope)) {
                return redirect($request->redirect_uri . '?' . http_build_query([
                    'error' => 'invalid_scope',
                    'state' => $request->state,
                ]));
            }
        }

        $user = Auth::user();
        $existingAuth = UserAuthorization::where('user_id', $user->id)
            ->where('application_id', $application->id)
            ->whereNull('revoked_at')
            ->first();

        if ($existingAuth && $this->scopesCovered($requestedScopes, $existingAuth->scopes)) {
            return $this->issueWebAuthorizationCode($request, $application, $user, $requestedScopes, true);
        }

        return view('oauth.authorize', [
            'application' => $application,
            'scopes' => $this->getScopeDescriptions($requestedScopes),
            'user' => $user,
            'params' => $request->only(['client_id', 'redirect_uri', 'scope', 'state', 'code_challenge', 'code_challenge_method']),
        ]);
    }

    /**
     * Traiter l'approbation/refus (web)
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

        if ($request->action === 'deny') {
            return redirect($request->redirect_uri . '?' . http_build_query([
                'error' => 'access_denied',
                'state' => $request->state,
            ]));
        }

        $scopes = $request->scope ? explode(' ', $request->scope) : ['profile'];
        return $this->issueWebAuthorizationCode($request, $application, Auth::user(), $scopes);
    }

    // ==========================================================
    // PRIVATE HELPERS
    // ==========================================================

    private function issueWebAuthorizationCode(Request $request, DeveloperApplication $application, $user, array $scopes, bool $isReconnection = false)
    {
        $authorization = UserAuthorization::updateOrCreate(
            ['user_id' => $user->id, 'application_id' => $application->id, 'revoked_at' => null],
            ['scopes' => $scopes, 'granted_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
        );

        $userAgent = $request->userAgent() ?? '';
        OAuthConnectionLog::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'authorization_id' => $authorization->id,
            'action' => $isReconnection ? 'reconnected' : 'authorized',
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => OAuthConnectionLog::detectDeviceType($userAgent),
            'browser' => OAuthConnectionLog::detectBrowser($userAgent),
            'platform' => OAuthConnectionLog::detectPlatform($userAgent),
            'scopes' => $scopes,
            'connected_at' => now(),
        ]);

        $authCode = OAuthAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'redirect_uri' => $request->redirect_uri,
            'scopes' => $scopes,
            'state' => $request->state,
            'code_challenge' => $request->code_challenge,
            'code_challenge_method' => $request->code_challenge_method,
        ]);

        return redirect($request->redirect_uri . '?' . http_build_query([
            'code' => $authCode->code,
            'state' => $request->state,
        ]));
    }

    private function scopesCovered(array $requestedScopes, array $grantedScopes): bool
    {
        foreach ($requestedScopes as $scope) {
            if (!in_array($scope, $grantedScopes)) {
                return false;
            }
        }
        return true;
    }

    private function getScopeDescriptions(array $scopes): array
    {
        $allDescriptions = OAuthScopeService::AVAILABLE_SCOPES;
        $result = [];
        foreach ($scopes as $scope) {
            if (isset($allDescriptions[$scope])) {
                $result[$scope] = $allDescriptions[$scope];
            }
        }
        return $result;
    }
}
