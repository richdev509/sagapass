<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
        ];

        // Derniers documents
        $recentDocuments = $user->documents()
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact('user', 'stats', 'recentDocuments'));
    }
}
