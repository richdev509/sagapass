<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoVerification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:verify-documents,admin']);
    }

    /**
     * Liste des vidéos en attente de vérification
     */
    public function index(Request $request)
    {
        $query = User::with('videoVerifications')
            ->where('video_status', 'pending')
            ->whereNotNull('verification_video');

        // Recherche globale (nom, email, téléphone)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
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

        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(15)->withQueryString();

        // Statistiques
        $stats = [
            'pending' => User::where('video_status', 'pending')->count(),
            'approved' => User::where('video_status', 'approved')->count(),
            'rejected' => User::where('video_status', 'rejected')->count(),
            'today' => User::where('video_status', 'pending')->whereDate('created_at', today())->count(),
        ];

        return view('admin.video-verification.index', compact('users', 'stats'));
    }

    /**
     * Liste des vidéos approuvées
     */
    public function approved(Request $request)
    {
        $query = User::with(['videoVerifications' => function($q) {
                $q->where('status', 'approved')->latest();
            }])
            ->where('video_status', 'approved');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par niveau de compte
        if ($request->filled('account_level')) {
            $query->where('account_level', $request->account_level);
        }

        // Filtre par dates
        if ($request->filled('date_from')) {
            $query->whereDate('video_verified_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('video_verified_at', '<=', $request->date_to);
        }

        $users = $query->orderBy('video_verified_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.video-verification.approved', compact('users'));
    }

    /**
     * Liste des vidéos rejetées
     */
    public function rejected(Request $request)
    {
        $query = User::with(['videoVerifications' => function($q) {
                $q->where('status', 'rejected')->latest();
            }])
            ->where('video_status', 'rejected');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par dates
        if ($request->filled('date_from')) {
            $query->whereDate('video_verified_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('video_verified_at', '<=', $request->date_to);
        }

        $users = $query->orderBy('video_verified_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.video-verification.rejected', compact('users'));
    }

    /**
     * Afficher les détails d'une vidéo pour vérification
     */
    public function show(User $user)
    {
        $user->load(['videoVerifications' => function($q) {
            $q->latest();
        }]);

        // Récupérer la dernière vidéo de vérification
        $latestVerification = $user->videoVerifications()->latest()->first();

        return view('admin.video-verification.show', compact('user', 'latestVerification'));
    }

    /**
     * Approuver une vidéo
     */
    public function approve(Request $request, User $user)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($user->video_status !== 'pending') {
            return redirect()
                ->route('admin.video-verification.index')
                ->with('error', 'Cette vidéo a déjà été traitée.');
        }

        // Récupérer la dernière vidéo de vérification
        $verification = $user->videoVerifications()->latest()->first();

        // Si pas de VideoVerification mais que l'utilisateur a une vidéo, créer l'enregistrement
        if (!$verification && $user->verification_video) {
            $verification = VideoVerification::create([
                'user_id' => $user->id,
                'video_path' => $user->verification_video,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        if (!$verification) {
            return redirect()
                ->route('admin.video-verification.index')
                ->with('error', 'Aucune vidéo de vérification trouvée.');
        }

        // Mettre à jour le VideoVerification
        $admin = Auth::guard('admin')->user();
        $verification->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'notes' => $request->notes,
        ]);

        // Mettre à jour l'utilisateur - Passage en compte Basic
        $user->update([
            'account_level' => 'basic',
            'video_status' => 'approved',
            'video_verified_at' => now(),
            'video_rejection_reason' => null,
            'verification_level' => 'video',
        ]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $user->id,
            'action' => 'video_approved',
            'description' => "Vidéo de vérification approuvée pour {$user->full_name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Envoyer email de notification
        // Mail::to($user->email)->send(new VideoApprovedMail($user));

        return redirect()
            ->route('admin.video-verification.index')
            ->with('success', 'Vidéo approuvée avec succès ! L\'utilisateur peut maintenant utiliser son compte Basic.');
    }

    /**
     * Rejeter une vidéo
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Veuillez indiquer la raison du rejet.',
            'rejection_reason.min' => 'La raison doit contenir au moins 10 caractères.',
            'rejection_reason.max' => 'La raison ne peut pas dépasser 500 caractères.',
        ]);

        if ($user->video_status !== 'pending') {
            return redirect()
                ->route('admin.video-verification.index')
                ->with('error', 'Cette vidéo a déjà été traitée.');
        }

        // Récupérer la dernière vidéo de vérification
        $verification = $user->videoVerifications()->latest()->first();

        // Si pas de VideoVerification mais que l'utilisateur a une vidéo, créer l'enregistrement
        if (!$verification && $user->verification_video) {
            $verification = VideoVerification::create([
                'user_id' => $user->id,
                'video_path' => $user->verification_video,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        if (!$verification) {
            return redirect()
                ->route('admin.video-verification.index')
                ->with('error', 'Aucune vidéo de vérification trouvée.');
        }

        // Mettre à jour le VideoVerification
        $admin = Auth::guard('admin')->user();
        $verification->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
            'notes' => $request->notes,
        ]);

        // Mettre à jour l'utilisateur
        $user->update([
            'video_status' => 'rejected',
            'video_verified_at' => now(),
            'video_rejection_reason' => $request->rejection_reason,
        ]);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => $user->id,
            'action' => 'video_rejected',
            'description' => "Vidéo de vérification rejetée pour {$user->full_name} : {$request->rejection_reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Envoyer email de notification
        // Mail::to($user->email)->send(new VideoRejectedMail($user, $request->rejection_reason));

        return redirect()
            ->route('admin.video-verification.index')
            ->with('success', 'Vidéo rejetée. L\'utilisateur pourra soumettre une nouvelle vidéo.');
    }

    /**
     * Servir la vidéo de vérification pour les admins
     */
    public function serveVideo(User $user)
    {
        if (!$user->verification_video) {
            abort(404, 'Aucune vidéo de vérification trouvée.');
        }

        $path = $user->verification_video;

        // Vérifier que le fichier existe dans storage/app
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Fichier vidéo introuvable.');
        }

        // Retourner la vidéo
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'video/webm',
            'Content-Disposition' => 'inline; filename="verification-video.webm"'
        ]);
    }
}
