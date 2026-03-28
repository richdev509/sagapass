<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\UserVerificationDocument;
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
            // Statistiques utilisateurs (app mobile)
            'total_users'          => User::count(),
            'active_users'         => User::where('account_status', 'active')->count(),
            'new_users_today'      => User::whereDate('created_at', today())->count(),
            'new_users_week'       => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),

            // Vérifications mobile (UserVerificationDocument)
            'mobile_pending'       => UserVerificationDocument::where('status', 'pending')->count(),
            'mobile_approved'      => UserVerificationDocument::where('status', 'approved')->count(),
            'mobile_rejected'      => UserVerificationDocument::where('status', 'rejected')->count(),
            'mobile_total'         => UserVerificationDocument::count(),

            // Statuts utilisateurs
            'users_pending'        => User::where('verification_status', 'pending')->count(),
            'users_verified'       => User::where('verification_status', 'verified')->count(),
            'users_rejected'       => User::where('verification_status', 'rejected')->count(),

            // Documents anciens
            'total_documents'      => Document::count(),
            'pending_documents'    => Document::where('verification_status', 'pending')->count(),
            'verified_documents'   => Document::where('verification_status', 'verified')->count(),
            'rejected_documents'   => Document::where('verification_status', 'rejected')->count(),

            // Statistiques admins
            'total_admins'         => Admin::count(),
            'active_admins'        => Admin::where('status', 'active')->count(),
        ];

        // Inscriptions mobiles en attente de vérification
        $pending_mobile = UserVerificationDocument::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Documents anciens en attente
        $pending_documents = Document::with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // Dernières activités (audit logs)
        $recent_activities = AuditLog::with(['admin', 'user'])
            ->latest()
            ->take(15)
            ->get();

        // Nouveaux utilisateurs (inscrits via mobile)
        $new_users = User::with('verificationDocument')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'pending_mobile',
            'pending_documents',
            'recent_activities',
            'new_users'
        ));
    }
}

