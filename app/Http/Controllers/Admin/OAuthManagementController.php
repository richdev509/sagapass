<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeveloperApplication;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\ApplicationSuspendedMail;

/**
 * Gestion des applications partenaires (DeveloperApplication) — recentré sur
 * la seule approbation/suspension/gestion du secret des applications qui
 * consomment l'API partenaire (voir Api\Partner\PartnerVerifyController).
 * L'ancien flux OAuth ("Login with SagaID"), le portail développeur
 * self-service, et la gestion des scopes ont été retirés — hors périmètre
 * du cas d'usage vérification document+selfie pour partenaires.
 */
class OAuthManagementController extends Controller
{
    /**
     * Display a listing of partner applications.
     */
    public function index(Request $request)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé. Permission requise: view-oauth-apps');
        }

        $query = DeveloperApplication::with(['user', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $applications = $query->paginate(15);

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
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé. Permission requise: view-oauth-apps');
        }

        $application->load(['user', 'approver']);

        return view('admin.oauth.show', compact('application'));
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

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_approved',
            'description' => "Application partenaire '{$application->name}' approuvée",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            Mail::to($application->user->email)->send(new ApplicationApprovedMail($application));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email approbation application partenaire: ' . $e->getMessage());
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

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_rejected',
            'description' => "Application partenaire '{$application->name}' rejetée : {$request->rejection_reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            Mail::to($application->user->email)->send(new ApplicationRejectedMail($application, $request->rejection_reason));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email rejet application partenaire: ' . $e->getMessage());
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

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_suspended',
            'description' => "Application partenaire '{$application->name}' suspendue : {$request->suspension_reason}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            Mail::to($application->user->email)->send(new ApplicationSuspendedMail($application, $request->suspension_reason));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email suspension application partenaire: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.oauth.index')
            ->with('success', "Application '{$application->name}' suspendue.");
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

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_reactivated',
            'description' => "Application partenaire '{$application->name}' réactivée",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()
            ->route('admin.oauth.show', $application)
            ->with('success', "Application '{$application->name}' réactivée avec succès !");
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
            'action' => 'partner_app_secret_regenerated',
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
            'action' => 'partner_app_secret_viewed',
            'description' => "Client secret consulté pour l'application '{$application->name}'",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'secret' => $secret ?? 'Impossible de déchiffrer le secret. Veuillez le régénérer.',
        ]);
    }

    /**
     * Regenerate the app_key for a partner application (Server-to-Server encryption).
     */
    public function regenerateAppKey(Request $request, DeveloperApplication $application)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $newAppKey = $application->regenerateAppKey();

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_key_regenerated',
            'description' => "App Key régénéré pour l'application '{$application->name}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.oauth.show', $application)
            ->with('success', 'App Key régénéré avec succès.')
            ->with('new_app_key', $newAppKey);
    }

    /**
     * Return the plaintext app_key (AJAX).
     */
    public function showAppKey(DeveloperApplication $application)
    {
        if (!auth('admin')->user()->can('view-oauth-apps')) {
            abort(403, 'Accès refusé.');
        }

        $appKey = $application->getPlaintextAppKey();

        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $application->user_id,
            'action' => 'partner_app_key_viewed',
            'description' => "App Key consulté pour l'application '{$application->name}'",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'app_key' => $appKey ?? 'Impossible de déchiffrer l\'app_key. Veuillez le régénérer.',
        ]);
    }
}
