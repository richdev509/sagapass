<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'role:Super Admin,admin']);
    }

    /**
     * Afficher les statistiques avancées
     */
    public function index()
    {
        // Statistiques globales
        $totalDocuments = Document::count();
        $totalUsers = User::count();
        $totalAdmins = Admin::count();
        $pendingDocuments = Document::where('verification_status', 'pending')->count();
        $verifiedDocuments = Document::where('verification_status', 'verified')->count();
        $rejectedDocuments = Document::where('verification_status', 'rejected')->count();

        // Taux d'approbation
        $processedDocuments = $verifiedDocuments + $rejectedDocuments;
        $approvalRate = $processedDocuments > 0 ? round(($verifiedDocuments / $processedDocuments) * 100, 2) : 0;
        $rejectionRate = $processedDocuments > 0 ? round(($rejectedDocuments / $processedDocuments) * 100, 2) : 0;

        // Temps moyen de vérification (en heures)
        $averageProcessingTime = Document::whereNotNull('verified_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_hours')
            ->first()
            ->avg_hours ?? 0;

        // Documents par type
        $cniCount = Document::where('document_type', 'cni')->count();
        $passportCount = Document::where('document_type', 'passport')->count();

        // Documents vérifiés par statut (pour graphique en camembert)
        $statusDistribution = [
            'verified' => $verifiedDocuments,
            'rejected' => $rejectedDocuments,
            'pending' => $pendingDocuments
        ];

        // Performance par admin (top 10)
        $adminPerformance = Document::whereNotNull('verified_by')
            ->select('verified_by', DB::raw('COUNT(*) as total_verified'))
            ->groupBy('verified_by')
            ->orderBy('total_verified', 'desc')
            ->limit(10)
            ->with('verifiedBy:id,name')
            ->get()
            ->map(function($item) {
                return [
                    'admin_name' => $item->verifiedBy->name ?? 'N/A',
                    'total' => $item->total_verified,
                    'approved' => Document::where('verified_by', $item->verified_by)->where('verification_status', 'verified')->count(),
                    'rejected' => Document::where('verified_by', $item->verified_by)->where('verification_status', 'rejected')->count(),
                ];
            });

        // Documents par jour (30 derniers jours)
        $last30Days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $last30Days[] = [
                'date' => $date->format('d/m'),
                'submitted' => Document::whereDate('created_at', $date)->count(),
                'verified' => Document::whereDate('verified_at', $date)->where('verification_status', 'verified')->count(),
                'rejected' => Document::whereDate('verified_at', $date)->where('verification_status', 'rejected')->count(),
            ];
        }

        // Documents par mois (12 derniers mois)
        $last12Months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->subMonths($i);
            $last12Months[] = [
                'month' => $month->format('M Y'),
                'submitted' => Document::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
                'verified' => Document::whereYear('verified_at', $month->year)
                    ->whereMonth('verified_at', $month->month)
                    ->where('verification_status', 'verified')
                    ->count(),
            ];
        }

        // Statistiques par heure de la journée (pour voir les heures de pointe)
        $hourlyStats = Document::whereNotNull('verified_at')
            ->select(DB::raw('HOUR(verified_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Top 5 raisons de rejet
        $topRejectionReasons = Document::where('verification_status', 'rejected')
            ->whereNotNull('rejection_reason')
            ->select('rejection_reason', DB::raw('COUNT(*) as count'))
            ->groupBy('rejection_reason')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return view('admin.statistics.index', compact(
            'totalDocuments',
            'totalUsers',
            'totalAdmins',
            'pendingDocuments',
            'verifiedDocuments',
            'rejectedDocuments',
            'approvalRate',
            'rejectionRate',
            'averageProcessingTime',
            'cniCount',
            'passportCount',
            'statusDistribution',
            'adminPerformance',
            'last30Days',
            'last12Months',
            'hourlyStats',
            'topRejectionReasons'
        ));
    }
}
