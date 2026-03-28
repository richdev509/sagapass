<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserVerificationDocument;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MobileVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Liste des inscriptions mobiles en attente
     */
    public function index()
    {
        $documents = UserVerificationDocument::with('user')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        $stats = [
            'pending'  => UserVerificationDocument::where('status', 'pending')->count(),
            'approved' => UserVerificationDocument::where('status', 'approved')->count(),
            'rejected' => UserVerificationDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.mobile-verification.index', compact('documents', 'stats'));
    }

    /**
     * Liste des inscriptions approuvées
     */
    public function approved()
    {
        $documents = UserVerificationDocument::with('user')
            ->where('status', 'approved')
            ->latest()
            ->paginate(20);

        $stats = [
            'pending'  => UserVerificationDocument::where('status', 'pending')->count(),
            'approved' => UserVerificationDocument::where('status', 'approved')->count(),
            'rejected' => UserVerificationDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.mobile-verification.index', compact('documents', 'stats'));
    }

    /**
     * Liste des inscriptions rejetées
     */
    public function rejected()
    {
        $documents = UserVerificationDocument::with('user')
            ->where('status', 'rejected')
            ->latest()
            ->paginate(20);

        $stats = [
            'pending'  => UserVerificationDocument::where('status', 'pending')->count(),
            'approved' => UserVerificationDocument::where('status', 'approved')->count(),
            'rejected' => UserVerificationDocument::where('status', 'rejected')->count(),
        ];

        return view('admin.mobile-verification.index', compact('documents', 'stats'));
    }

    /**
     * Détail d'une inscription mobile
     */
    public function show(UserVerificationDocument $mobileVerification)
    {
        $mobileVerification->load('user');
        return view('admin.mobile-verification.show', compact('mobileVerification'));
    }

    /**
     * Servir une image de façon sécurisée (non accessible publiquement)
     */
    public function serveImage(UserVerificationDocument $mobileVerification, string $type)
    {
        $allowed = ['id_card_front', 'id_card_back', 'selfie'];
        if (!in_array($type, $allowed)) {
            abort(404);
        }

        $path = $mobileVerification->$type;

        if (!$path || !Storage::exists($path)) {
            abort(404);
        }

        $fullPath = Storage::path($path);
        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

        return response()->file($fullPath, [
            'Content-Type'        => $mimeType,
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Approuver une inscription mobile
     */
    public function approve(Request $request, UserVerificationDocument $mobileVerification)
    {
        if ($mobileVerification->status !== 'pending') {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        $mobileVerification->update([
            'status'      => 'approved',
            'verified_at' => now(),
        ]);

        // Mettre à jour le statut de l'utilisateur
        $mobileVerification->user->update([
            'verification_status' => 'verified',
            'account_level'       => 'basic',  // Accès confirmé, niveau de base
            'verification_level'  => 'document',
            'verified_at'         => now(),
        ]);

        // Audit log
        $this->logAction('approve_mobile_registration', $mobileVerification->user, [
            'document_id' => $mobileVerification->id,
        ]);

        Log::info('Mobile registration approved', [
            'user_id'     => $mobileVerification->user_id,
            'admin_id'    => Auth::guard('admin')->id(),
            'document_id' => $mobileVerification->id,
        ]);

        return redirect()
            ->route('admin.mobile-verification.index')
            ->with('success', "Inscription de {$mobileVerification->user->first_name} {$mobileVerification->user->last_name} approuvée avec succès.");
    }

    /**
     * Rejeter une inscription mobile
     */
    public function reject(Request $request, UserVerificationDocument $mobileVerification)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($mobileVerification->status !== 'pending') {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        $mobileVerification->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        // Mettre à jour le statut de l'utilisateur
        $mobileVerification->user->update([
            'verification_status' => 'rejected',
        ]);

        // Audit log
        $this->logAction('reject_mobile_registration', $mobileVerification->user, [
            'document_id' => $mobileVerification->id,
            'reason'      => $request->reason,
        ]);

        Log::info('Mobile registration rejected', [
            'user_id'     => $mobileVerification->user_id,
            'admin_id'    => Auth::guard('admin')->id(),
            'reason'      => $request->reason,
        ]);

        return redirect()
            ->route('admin.mobile-verification.index')
            ->with('success', "Inscription de {$mobileVerification->user->first_name} {$mobileVerification->user->last_name} rejetée.");
    }

    /**
     * Enregistrer une action d'audit
     */
    private function logAction(string $action, User $targetUser, array $extra = []): void
    {
        try {
            AuditLog::create([
                'admin_id'    => Auth::guard('admin')->id(),
                'user_id'     => $targetUser->id,
                'action'      => $action,
                'description' => "Action: {$action} sur l'utilisateur #{$targetUser->id} ({$targetUser->email})" . (!empty($extra) ? ' | ' . json_encode($extra) : ''),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not create audit log: ' . $e->getMessage());
        }
    }
}
