<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Afficher le dashboard admin
     */
    public function index()
    {
        $stats = [
            // Statistiques utilisateurs
            'total_users' => User::count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'active_users' => User::where('account_status', 'active')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),

            // Statistiques documents
            'total_documents' => Document::count(),
            'pending_documents' => Document::where('verification_status', 'pending')->count(),
            'verified_documents' => Document::where('verification_status', 'verified')->count(),
            'rejected_documents' => Document::where('verification_status', 'rejected')->count(),

            // Documents par type
            'cni_documents' => Document::where('document_type', 'cni')->count(),
            'passport_documents' => Document::where('document_type', 'passport')->count(),

            // Statistiques admins
            'total_admins' => Admin::count(),
            'active_admins' => Admin::where('status', 'active')->count(),
        ];

        // Documents récents en attente
        $pending_documents = Document::with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Dernières activités (audit logs)
        $recent_activities = AuditLog::with(['admin', 'user'])
            ->latest()
            ->take(15)
            ->get();

        // Nouveaux utilisateurs
        $new_users = User::latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'pending_documents', 'recent_activities', 'new_users'));
    }
}

