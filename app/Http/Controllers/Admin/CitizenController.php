<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CitizenController extends Controller
{
    /**
     * Liste des citoyens avec recherche avancée
     */
    public function index(Request $request)
    {
        $query = User::query()->with(['documents']);

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        if ($request->filled('is_developer')) {
            $query->where('is_developer', $request->boolean('is_developer'));
        }

        if ($request->filled('email_verified')) {
            if ($request->email_verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $citizens = $query->paginate(50);

        return view('admin.citizens.index', compact('citizens'));
    }

    /**
     * Profil détaillé d'un citoyen
     */
    public function show($id)
    {
        $citizen = User::with([
            'documents' => function($query) {
                $query->latest();
            },
            'documents.verifiedBy',
            'oauthAuthorizations.application',
        ])->findOrFail($id);

        // Statistiques du citoyen
        $stats = [
            'total_documents' => $citizen->documents()->count(),
            'verified_documents' => $citizen->documents()->where('verification_status', 'verified')->count(),
            'pending_documents' => $citizen->documents()->where('verification_status', 'pending')->count(),
            'rejected_documents' => $citizen->documents()->where('verification_status', 'rejected')->count(),
            'oauth_apps_count' => $citizen->oauthAuthorizations()->distinct('application_id')->count(),
            'account_age_days' => $citizen->created_at->diffInDays(now()),
        ];

        // Activités récentes
        $activities = AuditLog::where('user_id', $citizen->id)
            ->with('admin')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.citizens.show', compact('citizen', 'stats', 'activities'));
    }

    /**
     * Suspendre un citoyen
     */
    public function suspend(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $citizen = User::findOrFail($id);
        $citizen->update(['account_status' => 'suspended']);

        // Logger l'action
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'user_id' => $citizen->id,
            'action' => 'suspend_account',
            'model_type' => 'User',
            'model_id' => $citizen->id,
            'description' => "Compte suspendu. Raison: {$request->reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Le compte du citoyen a été suspendu avec succès.');
    }

    /**
     * Réactiver un citoyen
     */
    public function activate(Request $request, $id)
    {
        $citizen = User::findOrFail($id);
        $citizen->update(['account_status' => 'active']);

        // Logger l'action
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'user_id' => $citizen->id,
            'action' => 'activate_account',
            'model_type' => 'User',
            'model_id' => $citizen->id,
            'description' => "Compte réactivé",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Le compte du citoyen a été réactivé avec succès.');
    }

    /**
     * Mettre à jour les informations d'un citoyen
     */
    public function update(Request $request, $id)
    {
        $citizen = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $citizen->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:500',
        ]);

        $citizen->update($validated);

        // Logger l'action
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'user_id' => $citizen->id,
            'action' => 'update_citizen_info',
            'model_type' => 'User',
            'model_id' => $citizen->id,
            'description' => "Informations du citoyen mises à jour",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Les informations du citoyen ont été mises à jour avec succès.');
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $citizen = User::findOrFail($id);
        $citizen->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Logger l'action
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'user_id' => $citizen->id,
            'action' => 'reset_citizen_password',
            'model_type' => 'User',
            'model_id' => $citizen->id,
            'description' => "Mot de passe réinitialisé par l'administrateur",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Le mot de passe du citoyen a été réinitialisé avec succès.');
    }

    /**
     * Recherche AJAX pour autocomplete
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');

        $citizens = User::where('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'verification_status']);

        return response()->json($citizens);
    }

    /**
     * Exporter les citoyens en CSV
     */
    public function export(Request $request)
    {
        $query = User::query();

        // Appliquer les mêmes filtres que l'index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $citizens = $query->get();

        $filename = 'citoyens_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function() use ($citizens) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, [
                'ID', 'Prénom', 'Nom', 'Email', 'Téléphone',
                'Date de naissance', 'Adresse', 'Statut Vérification',
                'Statut Compte', 'Email Vérifié', 'Développeur', 'Date Création'
            ]);

            // Données
            foreach ($citizens as $citizen) {
                fputcsv($file, [
                    $citizen->id,
                    $citizen->first_name,
                    $citizen->last_name,
                    $citizen->email,
                    $citizen->phone,
                    $citizen->date_of_birth,
                    $citizen->address,
                    $citizen->verification_status,
                    $citizen->account_status,
                    $citizen->email_verified_at ? 'Oui' : 'Non',
                    $citizen->is_developer ? 'Oui' : 'Non',
                    $citizen->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
