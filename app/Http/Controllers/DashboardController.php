<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use App\Models\UserAuthorization;

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
}
