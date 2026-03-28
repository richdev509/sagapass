<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\Developer;
use App\Models\User;
use App\Models\UserAuthorization;
use App\Models\AuditLog;
use App\Services\OAuthScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\ApplicationSuspendedMail;

class OAuthManagementController extends Controller
{
    /**
     * Display a listing of OAuth applications.
     */
    public function index(Request $request)
    {
        // Vérifier la permission
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé. Permission requise: view-oauth-apps');
        }

        $query = DeveloperApplication::with(['user', 'approver'])
            ->withCount(['userAuthorizations', 'authorizationCodes']);

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par recherche (nom app ou email développeur)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Trier
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $applications = $query->paginate(15);

        // Statistiques
        $stats = [
            'total' => DeveloperApplication::count(),
            'pending' => DeveloperApplication::where('status', 'pending')->count(),
            'approved' => DeveloperApplication::where('status', 'approved')->count(),
            'rejected' => DeveloperApplication::where('status', 'rejected')->count(),
            'suspended' => DeveloperApplication::where('status', 'suspended')->count(),
        ];

        return view('admin.oauth.index', compact('applications', 'stats'));
    }

    /**
     * Display the specified application.
     */
    public function show(DeveloperApplication $application)
    {
        // Vérifier la permission
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé. Permission requise: view-oauth-apps');
        }

        $application->load([
            'user.developer',
            'approver',
            'userAuthorizations.user',
            'authorizationCodes'
        ]);

        // Statistiques de l'application
        $appStats = [
            'total_users' => $application->userAuthorizations()->distinct('user_id')->count('user_id'),
            'active_users' => $application->userAuthorizations()->whereNull('revoked_at')->distinct('user_id')->count('user_id'),
            'revoked_users' => $application->userAuthorizations()->whereNotNull('revoked_at')->distinct('user_id')->count('user_id'),
            'total_authorizations' => $application->userAuthorizations()->count(),
            'codes_generated' => $application->authorizationCodes()->count(),
            'codes_used' => $application->authorizationCodes()->where('used', true)->count(),
        ];

        return view('admin.oauth.show', compact('application', 'appStats'));
    }

    /**
     * Approve an application.
     */
    public function approve(Request $request, DeveloperApplication $application)
    {
        if ($application->status !== 'pending') {
            return redirect()
                ->route('admin.oauth.show', $application)
                ->with('error', 'Cette application a déjà été traitée.');
        }

        $application->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::guard('admin')->id(),
        ]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_approved',
            'description' => "Application OAuth '{$application->name}' approuvée",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envoyer email au développeur
        try {
            Mail::to($application->user->email)->send(new ApplicationApprovedMail($application));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email approbation OAuth: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.oauth.index')
            ->with('success', "Application '{$application->name}' approuvée avec succès !");
    }

    /**
     * Reject an application.
     */
    public function reject(Request $request, DeveloperApplication $application)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.required' => 'Veuillez indiquer la raison du rejet.',
            'rejection_reason.min' => 'La raison doit contenir au moins 10 caractères.',
        ]);

        if ($application->status !== 'pending' && $application->status !== 'approved') {
            return redirect()
                ->route('admin.oauth.show', $application)
                ->with('error', 'Cette application ne peut pas être rejetée.');
        }

        $application->update([
            'status' => 'rejected',
        ]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_rejected',
            'description' => "Application OAuth '{$application->name}' rejetée : {$request->rejection_reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envoyer email au développeur
        try {
            Mail::to($application->user->email)->send(new ApplicationRejectedMail($application, $request->rejection_reason));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email rejet OAuth: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.oauth.index')
            ->with('success', "Application '{$application->name}' rejetée.");
    }

    /**
     * Suspend an application.
     */
    public function suspend(Request $request, DeveloperApplication $application)
    {
        $request->validate([
            'suspension_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'suspension_reason.required' => 'Veuillez indiquer la raison de la suspension.',
            'suspension_reason.min' => 'La raison doit contenir au moins 10 caractères.',
        ]);

        if ($application->status === 'suspended') {
            return redirect()
                ->route('admin.oauth.show', $application)
                ->with('error', 'Cette application est déjà suspendue.');
        }

        $application->update([
            'status' => 'suspended',
        ]);

        // Révoquer toutes les autorisations actives
        $revokedCount = UserAuthorization::where('application_id', $application->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_suspended',
            'description' => "Application OAuth '{$application->name}' suspendue : {$request->suspension_reason}. {$revokedCount} autorisations révoquées.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envoyer email au développeur
        try {
            Mail::to($application->user->email)->send(new ApplicationSuspendedMail($application, $request->suspension_reason));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email suspension OAuth: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.oauth.index')
            ->with('success', "Application '{$application->name}' suspendue. {$revokedCount} autorisations ont été révoquées.");
    }

    /**
     * Reactivate a suspended application.
     */
    public function reactivate(DeveloperApplication $application)
    {
        if ($application->status !== 'suspended') {
            return redirect()
                ->route('admin.oauth.show', $application)
                ->with('error', 'Cette application n\'est pas suspendue.');
        }

        $application->update([
            'status' => 'approved',
        ]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_reactivated',
            'description' => "Application OAuth '{$application->name}' réactivée",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()
            ->route('admin.oauth.show', $application)
            ->with('success', "Application '{$application->name}' réactivée avec succès !");
    }

    /**
     * Display users who authorized this application.
     */
    public function users(DeveloperApplication $application)
    {
        $authorizations = UserAuthorization::where('application_id', $application->id)
            ->with('user')
            ->orderBy('granted_at', 'desc')
            ->paginate(20);

        return view('admin.oauth.users', compact('application', 'authorizations'));
    }

    /**
     * Revoke a specific user authorization.
     */
    public function revokeUserAuthorization(Request $request, UserAuthorization $authorization)
    {
        $request->validate([
            'revoke_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if ($authorization->revoked_at) {
            return back()->with('error', 'Cette autorisation est déjà révoquée.');
        }

        $authorization->revoke();

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $authorization->user_id,
            'action' => 'oauth_authorization_revoked_by_admin',
            'description' => "Autorisation révoquée par admin pour l'application '{$authorization->application->name}' : {$request->revoke_reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Autorisation révoquée avec succès.');
    }

    /**
     * Ajouter un scope à une application
     */
    public function addScope(Request $request, DeveloperApplication $application)
    {
        $availableScopes = \App\Services\OAuthScopeService::getAllScopes();

        $request->validate([
            'scope' => ['required', 'string', 'in:' . implode(',', $availableScopes)],
        ]);

        $currentScopes = $application->allowed_scopes ?? [];

        if (in_array($request->scope, $currentScopes)) {
            return back()->with('error', 'Ce scope est déjà autorisé pour cette application.');
        }

        $currentScopes[] = $request->scope;
        $application->update(['allowed_scopes' => $currentScopes]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_scope_added',
            'description' => "Scope '{$request->scope}' ajouté à l'application '{$application->name}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Le scope '{$request->scope}' a été ajouté à l'application.");
    }

    /**
     * Retirer un scope d'une application
     */
    public function removeScope(Request $request, DeveloperApplication $application, string $scope)
    {
        $currentScopes = $application->allowed_scopes ?? [];

        if (!in_array($scope, $currentScopes)) {
            return back()->with('error', 'Ce scope n\'est pas autorisé pour cette application.');
        }

        $currentScopes = array_values(array_diff($currentScopes, [$scope]));
        $application->update(['allowed_scopes' => $currentScopes]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_scope_removed',
            'description' => "Scope '{$scope}' retiré de l'application '{$application->name}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Le scope '{$scope}' a été retiré de l'application.");
    }

    /**
     * Afficher toutes les demandes de scopes
     */
    public function scopeRequests(Request $request)
    {
        $query = \App\Models\ScopeRequest::with(['application.user', 'reviewer'])
            ->latest();

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $scopeRequests = $query->paginate(20);

        return view('admin.oauth.scope-requests', compact('scopeRequests'));
    }

    /**
     * Approuver une demande de scope
     */
    public function approveScopeRequest(Request $request, \App\Models\ScopeRequest $scopeRequest)
    {
        if ($scopeRequest->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $request->validate([
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Ajouter les scopes demandés à l'application
        $application = $scopeRequest->application;
        $currentScopes = $application->allowed_scopes ?? [];
        $newScopes = array_unique(array_merge($currentScopes, $scopeRequest->requested_scopes));

        $application->update(['allowed_scopes' => $newScopes]);

        // Mettre à jour la demande
        $scopeRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'admin_comment' => $request->admin_comment,
        ]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_scope_request_approved',
            'description' => "Demande de scopes approuvée pour l'application '{$application->name}'. Scopes ajoutés : " . implode(', ', $scopeRequest->requested_scopes),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Envoyer une notification par email au développeur

        return back()->with('success', 'Demande de scopes approuvée avec succès.');
    }

    /**
     * Rejeter une demande de scope
     */
    public function rejectScopeRequest(Request $request, \App\Models\ScopeRequest $scopeRequest)
    {
        if ($scopeRequest->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $request->validate([
            'admin_comment' => ['required', 'string', 'min:20', 'max:500'],
        ]);

        // Mettre à jour la demande
        $scopeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'admin_comment' => $request->admin_comment,
        ]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $scopeRequest->application->user_id,
            'action' => 'oauth_scope_request_rejected',
            'description' => "Demande de scopes rejetée pour l'application '{$scopeRequest->application->name}'. Raison : {$request->admin_comment}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Envoyer une notification par email au développeur

        return back()->with('success', 'Demande de scopes rejetée.');
    }

    /**
     * Update OAuth configuration for an application.
     */
    public function updateConfig(Request $request, DeveloperApplication $application)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'redirect_uris' => ['required', 'string'],
            'is_trusted' => ['nullable', 'boolean'],
        ]);

        // Parser les redirect_uris (une par ligne)
        $redirectUris = array_values(array_filter(
            array_map('trim', explode("\n", $validated['redirect_uris']))
        ));

        $application->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'website' => $validated['website'],
            'redirect_uris' => $redirectUris,
            'is_trusted' => $request->boolean('is_trusted'),
        ]);

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_config_updated',
            'description' => "Configuration OAuth mise à jour pour '{$application->name}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.oauth.show', $application)
            ->with('success', 'Configuration OAuth mise à jour avec succès.');
    }

    /**
     * Regenerate client secret for an application.
     */
    public function regenerateSecret(Request $request, DeveloperApplication $application)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $newSecret = $application->regenerateSecret();

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_secret_regenerated',
            'description' => "Client secret régénéré pour l'application '{$application->name}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.oauth.show', $application)
            ->with('success', 'Client secret régénéré avec succès.')
            ->with('new_secret', $newSecret);
    }

    /**
     * Return the plaintext client secret (AJAX).
     */
    public function showSecret(DeveloperApplication $application)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $secret = $application->getPlaintextSecret();

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'oauth_app_secret_viewed',
            'description' => "Client secret consulté pour l'application '{$application->name}'",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'secret' => $secret ?? 'Impossible de déchiffrer le secret. Veuillez le régénérer.',
        ]);
    }

    /**
     * Afficher le formulaire de création de compte développeur par l'admin.
     */
    public function createDeveloper()
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $availableScopes = OAuthScopeService::getScopesForForm(true);

        return view('admin.oauth.create-developer', compact('availableScopes'));
    }

    /**
     * Rechercher un citoyen par NIU (AJAX).
     */
    public function searchCitizen(Request $request)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $request->validate([
            'niu' => ['required', 'string', 'size:10'],
        ]);

        $user = User::where('niu', $request->niu)->first();

        if (!$user) {
            return response()->json([
                'found' => false,
                'message' => 'Aucun citoyen trouvé avec ce NIU.',
            ]);
        }

        if ($user->verification_status !== 'verified') {
            return response()->json([
                'found' => false,
                'message' => 'Ce citoyen n\'est pas vérifié. Statut actuel : ' . $user->verification_status,
            ]);
        }

        $hasDeveloper = $user->developer()->exists();

        return response()->json([
            'found' => true,
            'citizen' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'niu' => $user->niu,
                'verification_status' => $user->verification_status,
                'account_level' => $user->account_level,
                'has_developer_account' => $hasDeveloper,
            ],
        ]);
    }

    /**
     * Créer un compte développeur + application OAuth pour un citoyen vérifié.
     */
    public function storeDeveloper(Request $request)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'developer_bio' => ['nullable', 'string', 'max:1000'],
            'developer_website' => ['nullable', 'url', 'max:255'],
            'app_name' => ['required', 'string', 'max:255'],
            'app_description' => ['nullable', 'string', 'max:1000'],
            'app_website' => ['nullable', 'url', 'max:255'],
            'redirect_uris' => ['required', 'string'],
            'allowed_scopes' => ['nullable', 'array'],
            'allowed_scopes.*' => ['string', 'in:' . implode(',', OAuthScopeService::getAllScopes())],
            'is_trusted' => ['nullable', 'boolean'],
            'auto_approve' => ['nullable', 'boolean'],
        ], [
            'user_id.required' => 'Veuillez rechercher un citoyen par NIU.',
            'user_id.exists' => 'Citoyen introuvable.',
            'company_name.required' => 'Le nom de la société/organisation est requis.',
            'app_name.required' => 'Le nom de l\'application est requis.',
            'redirect_uris.required' => 'Au moins une URI de redirection est requise.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Vérifier que le citoyen est vérifié
        if ($user->verification_status !== 'verified') {
            return back()->withInput()->with('error', 'Ce citoyen n\'est pas vérifié.');
        }

        DB::beginTransaction();

        try {
            // Créer ou mettre à jour le profil développeur
            $developer = Developer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $validated['company_name'],
                    'developer_bio' => $validated['developer_bio'] ?? null,
                    'developer_website' => $validated['developer_website'] ?? null,
                    'status' => 'active',
                    'verified_at' => now(),
                ]
            );

            // Mettre à jour les champs développeur sur le User
            $user->update([
                'is_developer' => true,
                'company_name' => $validated['company_name'],
                'developer_bio' => $validated['developer_bio'] ?? null,
                'developer_website' => $validated['developer_website'] ?? null,
                'developer_verified_at' => now(),
            ]);

            // Parser les redirect_uris
            $redirectUris = array_values(array_filter(
                array_map('trim', explode("\n", $validated['redirect_uris']))
            ));

            // Scopes
            $scopes = $validated['allowed_scopes'] ?? ['profile'];
            if (!in_array('profile', $scopes)) {
                array_unshift($scopes, 'profile');
            }

            // Créer l'application OAuth
            $status = $request->boolean('auto_approve') ? 'approved' : 'pending';
            $application = DeveloperApplication::create([
                'user_id' => $user->id,
                'name' => $validated['app_name'],
                'description' => $validated['app_description'] ?? null,
                'website' => $validated['app_website'] ?? null,
                'redirect_uris' => $redirectUris,
                'allowed_scopes' => $scopes,
                'status' => $status,
                'is_trusted' => $request->boolean('is_trusted'),
                'approved_at' => $status === 'approved' ? now() : null,
                'approved_by' => $status === 'approved' ? Auth::guard('admin')->id() : null,
            ]);

            // Audit log
            AuditLog::create([
                'admin_id' => Auth::guard('admin')->id(),
                'user_id' => $user->id,
                'action' => 'developer_account_created_by_admin',
                'description' => "Compte développeur créé par admin pour {$user->first_name} {$user->last_name} (NIU: {$user->niu}). Application: '{$application->name}' ({$status})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('admin.oauth.show', $application)
                ->with('success', "Compte développeur créé avec succès pour {$user->first_name} {$user->last_name}. Application '{$application->name}' " . ($status === 'approved' ? 'approuvée automatiquement.' : 'en attente d\'approbation.'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création compte développeur par admin: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }
}
