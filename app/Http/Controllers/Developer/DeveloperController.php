<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\ScopeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DeveloperController extends Controller
{
    /**
     * Afficher le formulaire d'inscription développeur
     */
    public function showRegisterForm()
    {
        return view('developers.register');
    }

    /**
     * Traiter l'inscription développeur (optionnel - juste rediriger vers dashboard)
     */
    public function register(Request $request)
    {
        // Les développeurs sont simplement des citoyens qui créent des applications
        // Pas besoin d'enregistrement séparé
        return redirect()->route('developers.dashboard')
            ->with('success', 'Bienvenue dans le Developer Dashboard! Créez votre première application OAuth.');
    }

    /**
     * Dashboard développeur principal
     */
    public function dashboard()
    {
        $user = auth()->user();

        // Statistiques globales pour ce développeur
        $applications = DeveloperApplication::where('user_id', $user->id)->get();

        $stats = [
            'total_applications' => $applications->count(),
            'approved_applications' => $applications->where('status', 'approved')->count(),
            'pending_applications' => $applications->where('status', 'pending')->count(),
            'total_users' => $applications->sum(function ($app) {
                return $app->userAuthorizations()->where('revoked_at', null)->count();
            }),
        ];

        return view('developers.dashboard', compact('applications', 'stats'));
    }

    /**
     * Liste des applications du développeur
     */
    public function index()
    {
        $applications = DeveloperApplication::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('developers.applications.index', compact('applications'));
    }

    /**
     * Formulaire de création d'application
     */
    public function create()
    {
        // Liste des scopes disponibles
        $availableScopes = [
            'profile' => 'Profil de base (nom, prénom, statut de vérification)',
            'email' => 'Adresse email',
            'phone' => 'Numéro de téléphone',
            'address' => 'Adresse postale',
            'documents' => 'Informations sur les documents vérifiés (sans images)'
        ];

        return view('developers.applications.create', compact('availableScopes'));
    }

    /**
     * Enregistrer une nouvelle application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'website' => ['required', 'url', 'max:255'],
            'redirect_uris' => ['required', 'string'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        // Traiter les redirect URIs (une par ligne)
        $redirectUris = array_filter(array_map('trim', explode("\n", $validated['redirect_uris'])));

        // Valider que toutes les URIs sont valides
        foreach ($redirectUris as $uri) {
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                return back()->withErrors(['redirect_uris' => 'Toutes les URIs doivent être des URLs valides.'])->withInput();
            }
            // Vérifier HTTPS en production
            if (app()->environment('production') && !str_starts_with($uri, 'https://')) {
                return back()->withErrors(['redirect_uris' => 'Les URIs doivent utiliser HTTPS en production.'])->withInput();
            }
        }

        // Upload du logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('application-logos', 'public');
        }

        // Créer l'application
        $application = DeveloperApplication::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'website' => $validated['website'],
            'logo_path' => $logoPath,
            'redirect_uris' => $redirectUris,
            'allowed_scopes' => ['profile'], // Par défaut, seul profile est autorisé
            'status' => 'pending', // En attente d'approbation admin
        ]);

        return redirect()->route('developers.applications.show', $application)
            ->with('success', 'Application créée avec succès! Elle est en attente d\'approbation par un administrateur.');
    }

    /**
     * Afficher les détails d'une application
     */
    public function show(DeveloperApplication $application)
    {
        $this->authorize('view', $application);

        // Charger les demandes de scopes en attente
        $application->load(['scopeRequests' => function ($query) {
            $query->where('status', 'pending');
        }]);

        // Charger les statistiques
        $stats = [
            'active_users' => $application->userAuthorizations()->whereNull('revoked_at')->count(),
            'total_authorizations' => $application->userAuthorizations()->count(),
            'authorization_codes_issued' => $application->authorizationCodes()->count(),
            'authorization_codes_used' => $application->authorizationCodes()->where('used', true)->count(),
        ];

        // Récupérer un nouveau client secret si demandé
        $showSecret = session('show_secret') === $application->id;
        $newSecret = session('new_secret');

        return view('developers.applications.show', compact('application', 'stats', 'showSecret', 'newSecret'));
    }

    /**
     * Formulaire de modification
     */
    public function edit(DeveloperApplication $application)
    {
        $this->authorize('update', $application);

        $availableScopes = [
            'profile' => 'Profil de base (nom, prénom, statut de vérification)',
            'email' => 'Adresse email',
            'phone' => 'Numéro de téléphone',
            'address' => 'Adresse postale',
            'documents' => 'Informations sur les documents vérifiés (sans images)'
        ];

        return view('developers.applications.edit', compact('application', 'availableScopes'));
    }

    /**
     * Mettre à jour une application
     */
    public function update(Request $request, DeveloperApplication $application)
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'website' => ['required', 'url', 'max:255'],
            'redirect_uris' => ['required', 'string'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        // Traiter les redirect URIs
        $redirectUris = array_filter(array_map('trim', explode("\n", $validated['redirect_uris'])));

        foreach ($redirectUris as $uri) {
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                return back()->withErrors(['redirect_uris' => 'Toutes les URIs doivent être des URLs valides.'])->withInput();
            }
        }

        // Upload du nouveau logo
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo
            if ($application->logo_path && Storage::disk('public')->exists($application->logo_path)) {
                Storage::disk('public')->delete($application->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('application-logos', 'public');
        }

        $application->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'website' => $validated['website'],
            'logo_path' => $validated['logo_path'] ?? $application->logo_path,
            'redirect_uris' => $redirectUris,
        ]);

        return redirect()->route('developers.applications.show', $application)
            ->with('success', 'Application mise à jour avec succès!');
    }

    /**
     * Supprimer une application
     */
    public function destroy(DeveloperApplication $application)
    {
        $this->authorize('delete', $application);

        // Révoquer toutes les autorisations actives
        $application->userAuthorizations()->whereNull('revoked_at')->each(function ($auth) {
            $auth->revoke();
        });

        // Supprimer le logo
        if ($application->logo_path && Storage::disk('public')->exists($application->logo_path)) {
            Storage::disk('public')->delete($application->logo_path);
        }

        $application->delete();

        return redirect()->route('developers.applications.index')
            ->with('success', 'Application supprimée avec succès!');
    }

    /**
     * Régénérer le client secret
     */
    public function regenerateSecret(DeveloperApplication $application)
    {
        $this->authorize('update', $application);

        // Générer un nouveau secret
        $newSecret = Str::random(60);
        $application->update([
            'client_secret' => Hash::make($newSecret),
        ]);

        // Stocker temporairement pour l'affichage (une seule fois)
        session(['show_secret' => $application->id, 'new_secret' => $newSecret]);

        return redirect()->route('developers.applications.show', $application)
            ->with('warning', 'Le Client Secret a été régénéré. Copiez-le maintenant, il ne sera plus affiché!');
    }

    /**
     * Demander des scopes additionnels
     */
    public function requestScopes(Request $request, DeveloperApplication $application)
    {
        \Log::info('=== Début requestScopes ===', [
            'application_id' => $application->id,
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
        ]);

        $this->authorize('update', $application);
        \Log::info('Authorization passed');

        $validated = $request->validate([
            'requested_scopes' => ['required', 'array', 'min:1'],
            'requested_scopes.*' => ['required', 'string', 'in:profile,email,phone,address,birthdate,photo,documents'],
            'justification' => ['required', 'string', 'min:50', 'max:1000'],
        ]);
        \Log::info('Validation passed', ['validated' => $validated]);

        // Vérifier qu'il n'y a pas déjà une demande en attente
        $pendingRequest = $application->scopeRequests()->where('status', 'pending')->first();
        if ($pendingRequest) {
            \Log::info('Pending request exists');
            return redirect()->route('developers.applications.show', $application)
                ->with('error', 'Vous avez déjà une demande de scopes en attente de révision.');
        }

        // Filtrer les scopes déjà autorisés
        $newScopes = array_diff($validated['requested_scopes'], $application->allowed_scopes ?? []);
        \Log::info('Scopes after filter', [
            'requested' => $validated['requested_scopes'],
            'allowed' => $application->allowed_scopes,
            'new_scopes' => $newScopes,
        ]);

        if (empty($newScopes)) {
            \Log::info('No new scopes to request');
            return redirect()->route('developers.applications.show', $application)
                ->with('error', 'Tous les scopes sélectionnés sont déjà autorisés pour cette application.');
        }

        // Créer la demande
        \Log::info('Creating ScopeRequest', [
            'application_id' => $application->id,
            'requested_scopes' => array_values($newScopes),
            'justification_length' => strlen($validated['justification']),
        ]);

        $scopeRequest = ScopeRequest::create([
            'application_id' => $application->id,
            'requested_scopes' => array_values($newScopes),
            'justification' => $validated['justification'],
            'status' => 'pending',
        ]);

        \Log::info('ScopeRequest created', ['id' => $scopeRequest->id]);

        return redirect()->route('developers.applications.show', $application)
            ->with('success', 'Votre demande de scopes additionnels a été soumise. Elle sera examinée par l\'équipe SAGAPASS dans les prochains jours.');
    }

    /**
     * Statistiques détaillées d'une application
     */
    public function stats(DeveloperApplication $application)
    {
        $this->authorize('view', $application);

        // Statistiques par jour (30 derniers jours)
        $dailyStats = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dailyStats[] = [
                'date' => $date->format('d/m'),
                'authorizations' => $application->userAuthorizations()
                    ->whereDate('granted_at', $date)
                    ->count(),
                'revocations' => $application->userAuthorizations()
                    ->whereDate('revoked_at', $date)
                    ->count(),
            ];
        }

        return view('developers.applications.stats', compact('application', 'dailyStats'));
    }

    /**
     * Documentation API
     */
    public function documentation()
    {
        return view('developers.documentation');
    }
}
