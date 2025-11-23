<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\UserAuthorization;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {
        $user = Auth::user();

        // Statistiques pour le dashboard
        $stats = [
            'documents' => $user->documents()->count(),
            'documents_verified' => $user->documents()->where('verification_status', 'verified')->count(),
            'documents_pending' => $user->documents()->where('verification_status', 'pending')->count(),
            'active_consents' => UserAuthorization::where('user_id', $user->id)->whereNull('revoked_at')->count(),
            'connected_services' => UserAuthorization::where('user_id', $user->id)->whereNull('revoked_at')->distinct('application_id')->count(),
        ];

        // Derniers documents
        $recentDocuments = $user->documents()
            ->latest()
            ->take(3)
            ->get();

        // Dernières connexions (services connectés)
        $recentConsents = UserAuthorization::where('user_id', $user->id)
            ->with('application')
            ->whereNull('revoked_at')
            ->latest('granted_at')
            ->take(5)
            ->get();

        return view('dashboard', compact('user', 'stats', 'recentDocuments', 'recentConsents'));
    }

    /**
     * Afficher la page de recapture de vidéo
     */
    public function recaptureVideo()
    {
        $user = Auth::user();

        // Vérifier que la vidéo a été rejetée OU que c'est la première soumission (none/pending)
        if (!in_array($user->video_status, ['rejected', 'none', 'pending'])) {
            return redirect()->route('dashboard')->with('error', 'Aucune capture de vidéo nécessaire.');
        }

        return view('video.recapture', compact('user'));
    }

    /**
     * Soumettre la nouvelle photo ET vidéo
     */
    public function submitRecaptureVideo(Request $request)
    {
        $user = Auth::user();

        // Vérifier que la vidéo a été rejetée OU que c'est la première soumission
        if (!in_array($user->video_status, ['rejected', 'none', 'pending'])) {
            return redirect()->route('dashboard')->with('error', 'Aucune capture nécessaire.');
        }

        // Valider la photo et la vidéo
        $request->validate([
            'photo' => 'required|string',
            'video' => 'required|file|mimes:webm,mp4,mov|max:10240', // 10 MB max
            'consent' => 'required|accepted',
        ], [
            'photo.required' => 'La photo est obligatoire.',
            'video.required' => 'La vidéo est obligatoire.',
            'video.mimes' => 'La vidéo doit être au format webm, mp4 ou mov.',
            'video.max' => 'La vidéo ne doit pas dépasser 10 Mo.',
            'consent.required' => 'Vous devez accepter le consentement.',
            'consent.accepted' => 'Vous devez accepter le consentement.',
        ]);

        // Traiter la photo (base64)
        $photoData = $request->input('photo');

        // Supprimer l'ancienne photo si elle existe
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Décoder et sauvegarder la nouvelle photo
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $photoData = substr($photoData, strpos($photoData, ',') + 1);
            $photoData = base64_decode($photoData);

            $photoExtension = strtolower($type[1]);
            $photoFilename = 'profile_pictures/' . $user->id . '_' . time() . '.' . $photoExtension;

            Storage::disk('public')->put($photoFilename, $photoData);
        } else {
            return back()->with('error', 'Format de photo invalide.');
        }

        // Supprimer l'ancienne vidéo si elle existe
        if ($user->verification_video) {
            Storage::disk('local')->delete($user->verification_video);
        }

        // Enregistrer la nouvelle vidéo
        $videoPath = $request->file('video')->store('verification_videos/' . $user->id, 'local');

        // Mettre à jour l'utilisateur
        $user->profile_picture = $photoFilename;
        $user->verification_video = $videoPath;
        $user->video_status = 'pending';
        $user->video_rejection_reason = null;
        $user->video_consent_at = now();
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Votre nouvelle photo et vidéo ont été soumises avec succès. Elles seront examinées prochainement.');
    }
}
