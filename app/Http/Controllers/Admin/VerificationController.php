<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\AuditLog;
use App\Models\DocumentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\DocumentApprovedMail;
use App\Mail\DocumentRejectedMail;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:verify-documents,admin']);
    }

    /**
     * Liste des documents en attente de vérification
     */
    public function index(Request $request)
    {
        $query = Document::with('user')
            ->where('verification_status', 'pending');

        // Filtre par type de document
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Recherche globale (nom, email, numéro document)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par plage de dates de soumission
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Options de tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'user_name':
                $query->join('users', 'documents.user_id', '=', 'users.id')
                      ->select('documents.*')
                      ->orderBy('users.first_name', $sortOrder)
                      ->orderBy('users.last_name', $sortOrder);
                break;
            case 'document_number':
                $query->orderBy('document_number', $sortOrder);
                break;
            case 'expiry_date':
                $query->orderBy('expiry_date', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $documents = $query->paginate(15)->withQueryString();

        return view('admin.verification.index', compact('documents'));
    }

    /**
     * Liste des documents vérifiés
     */
    public function verified(Request $request)
    {
        $query = Document::with(['user', 'verifiedBy'])
            ->where('verification_status', 'verified');

        // Filtre par type de document
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Recherche globale (nom, email, numéro document)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par plage de dates de vérification
        if ($request->filled('date_from')) {
            $query->whereDate('verified_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('verified_at', '<=', $request->date_to);
        }

        // Filtre par admin vérificateur
        if ($request->filled('verified_by')) {
            $query->where('verified_by', $request->verified_by);
        }

        // Options de tri
        $sortBy = $request->get('sort_by', 'verified_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'user_name':
                $query->join('users', 'documents.user_id', '=', 'users.id')
                      ->select('documents.*')
                      ->orderBy('users.first_name', $sortOrder)
                      ->orderBy('users.last_name', $sortOrder);
                break;
            case 'document_number':
                $query->orderBy('document_number', $sortOrder);
                break;
            case 'verified_at':
            default:
                $query->orderBy('verified_at', $sortOrder);
                break;
        }

        $documents = $query->paginate(15)->withQueryString();

        // Récupérer la liste des admins pour le filtre
        $admins = \App\Models\Admin::orderBy('name')->get();

        return view('admin.verification.verified', compact('documents', 'admins'));
    }

    /**
     * Liste des documents rejetés
     */
    public function rejected(Request $request)
    {
        $query = Document::with(['user', 'verifiedBy'])
            ->where('verification_status', 'rejected');

        // Filtre par type de document
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par plage de dates de rejet
        if ($request->filled('date_from')) {
            $query->whereDate('verified_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('verified_at', '<=', $request->date_to);
        }

        $documents = $query->orderBy('verified_at', 'desc')->paginate(15)->withQueryString();

        // Statistiques
        $stats = [
            'total' => Document::where('verification_status', 'rejected')->count(),
            'cni' => Document::where('verification_status', 'rejected')->where('document_type', 'cni')->count(),
            'passport' => Document::where('verification_status', 'rejected')->where('document_type', 'passport')->count(),
            'today' => Document::where('verification_status', 'rejected')->whereDate('verified_at', today())->count(),
        ];

        return view('admin.verification.rejected', compact('documents', 'stats'));
    }

    /**
     * Afficher les détails d'un document pour vérification
     */
    public function show(Document $document)
    {
        $document->load(['user', 'verifiedBy', 'histories.admin']);

        return view('admin.verification.show', compact('document'));
    }

    /**
     * Approuver un document
     */
    public function approve(Request $request, Document $document)
    {
        if ($document->verification_status !== 'pending') {
            return redirect()
                ->route('admin.verification.index')
                ->with('error', 'Ce document a déjà été traité.');
        }

        $document->update([
            'verification_status' => 'verified',
            'verified_by' => Auth::guard('admin')->id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        // Vérifier si l'utilisateur a tous ses documents vérifiés
        $user = $document->user;
        $totalDocuments = $user->documents()->count();
        $verifiedDocuments = $user->documents()->where('verification_status', 'verified')->count();

        // Si tous les documents sont vérifiés, marquer l'utilisateur comme vérifié
        if ($totalDocuments > 0 && $totalDocuments === $verifiedDocuments) {
            $user->update([
                'verification_status' => 'verified',
                'account_status' => 'active',
            ]);
        }

        // Enregistrer dans l'historique
        DocumentHistory::create([
            'document_id' => $document->id,
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'verified',
            'old_status' => 'pending',
            'new_status' => 'verified',
            'details' => 'Document approuvé et vérifié',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envoyer l'email de notification
        try {
            Mail::to($document->user->email)->send(new DocumentApprovedMail($document));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email approbation: ' . $e->getMessage());
        }

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $document->user_id,
            'action' => 'document_verified',
            'description' => "Document {$document->document_type} N°{$document->document_number} vérifié",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = 'Document approuvé avec succès !';
        if ($user->verification_status === 'verified') {
            $message .= ' Le compte utilisateur est maintenant vérifié.';
        }

        return redirect()
            ->route('admin.verification.index')
            ->with('success', $message);
    }

    /**
     * Rejeter un document
     */
    public function reject(Request $request, Document $document)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.required' => 'Veuillez indiquer la raison du rejet.',
            'rejection_reason.min' => 'La raison doit contenir au moins 10 caractères.',
            'rejection_reason.max' => 'La raison ne peut pas dépasser 500 caractères.',
        ]);

        if ($document->verification_status !== 'pending') {
            return redirect()
                ->route('admin.verification.index')
                ->with('error', 'Ce document a déjà été traité.');
        }

        $document->update([
            'verification_status' => 'rejected',
            'verified_by' => Auth::guard('admin')->id(),
            'verified_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Mettre à jour le statut de l'utilisateur
        $user = $document->user;
        $user->update([
            'verification_status' => 'rejected',
        ]);

        // Enregistrer dans l'historique
        DocumentHistory::create([
            'document_id' => $document->id,
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'rejected',
            'old_status' => 'pending',
            'new_status' => 'rejected',
            'details' => 'Document rejeté: ' . $request->rejection_reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Envoyer l'email de notification
        try {
            Mail::to($document->user->email)->send(new DocumentRejectedMail($document, $request->rejection_reason));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email rejet: ' . $e->getMessage());
        }

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $document->user_id,
            'action' => 'document_rejected',
            'description' => "Document {$document->document_type} N°{$document->document_number} rejeté : {$request->rejection_reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.verification.index')
            ->with('success', 'Document rejeté. L\'utilisateur pourra soumettre un nouveau document.');
    }

    /**
     * Servir une image de document pour les admins
     */
    public function serveImage(Document $document, string $type)
    {
        // Vérifier le type (front ou back)
        if ($type === 'front' && $document->front_photo_path) {
            $path = $document->front_photo_path;
        } elseif ($type === 'back' && $document->back_photo_path) {
            $path = $document->back_photo_path;
        } else {
            abort(404);
        }

        // Vérifier que le fichier existe
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        // Retourner le fichier
        return response()->file(Storage::disk('private')->path($path));
    }
}

