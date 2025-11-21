<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    /**
     * Dashboard principal de sécurité
     */
    public function index()
    {
        $stats = SecurityLog::getStats24Hours();
        $topIps = SecurityLog::getTopAttackingIPs(10);
        $recentAttacks = SecurityLog::getRecentAttacks(20);
        $blockedIps = BlockedIp::getActiveBlocks();

        return view('admin.security.dashboard', compact('stats', 'topIps', 'recentAttacks', 'blockedIps'));
    }

    /**
     * API: Récupérer les logs de sécurité (AJAX)
     */
    public function logs(Request $request)
    {
        $query = SecurityLog::with('user')->orderByDesc('created_at');

        // Filtres
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', "%{$request->ip}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    /**
     * API: Récupérer les statistiques en temps réel (AJAX)
     */
    public function stats()
    {
        return response()->json([
            'stats_24h' => SecurityLog::getStats24Hours(),
            'stats_by_type' => SecurityLog::getStatsByType(),
            'hourly_chart' => SecurityLog::getHourlyChart(),
            'top_ips' => SecurityLog::getTopAttackingIPs(10),
        ]);
    }

    /**
     * Page de gestion des logs
     */
    public function logsPage()
    {
        $types = [
            SecurityLog::TYPE_SQL_INJECTION => 'SQL Injection',
            SecurityLog::TYPE_XSS => 'Cross-Site Scripting (XSS)',
            SecurityLog::TYPE_PATH_TRAVERSAL => 'Path Traversal',
            SecurityLog::TYPE_BRUTE_FORCE => 'Brute Force',
            SecurityLog::TYPE_RATE_LIMIT => 'Rate Limit Exceeded',
            SecurityLog::TYPE_SUSPICIOUS => 'Activité Suspecte',
            SecurityLog::TYPE_UNAUTHORIZED => 'Accès Non Autorisé',
        ];

        $severities = [
            SecurityLog::SEVERITY_LOW => 'Faible',
            SecurityLog::SEVERITY_MEDIUM => 'Moyen',
            SecurityLog::SEVERITY_HIGH => 'Élevé',
            SecurityLog::SEVERITY_CRITICAL => 'Critique',
        ];

        return view('admin.security.logs', compact('types', 'severities'));
    }

    /**
     * Afficher un log spécifique
     */
    public function showLog($id)
    {
        $log = SecurityLog::with('user')->findOrFail($id);
        return view('admin.security.show-log', compact('log'));
    }

    /**
     * Bloquer manuellement une IP
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'is_permanent' => 'boolean',
        ]);

        BlockedIp::blockIp(
            $request->ip_address,
            $request->reason,
            $request->duration,
            $request->boolean('is_permanent')
        );

        return response()->json([
            'success' => true,
            'message' => "L'IP {$request->ip_address} a été bloquée avec succès.",
        ]);
    }

    /**
     * Débloquer une IP
     */
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $result = BlockedIp::unblockIp($request->ip_address);

        return response()->json([
            'success' => $result,
            'message' => $result
                ? "L'IP {$request->ip_address} a été débloquée."
                : "L'IP n'était pas bloquée.",
        ]);
    }

    /**
     * Page de gestion des IPs bloquées
     */
    public function blockedIpsPage()
    {
        $blockedIps = BlockedIp::with('blockedBy')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.security.blocked-ips', compact('blockedIps'));
    }

    /**
     * Supprimer des logs anciens
     */
    public function deleteLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $deleted = SecurityLog::where('created_at', '<', now()->subDays($request->days))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} log(s) supprimé(s) avec succès.",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Nettoyer les IP bloquées expirées
     */
    public function cleanExpiredBlocks()
    {
        $deleted = BlockedIp::cleanExpired();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} blocage(s) expiré(s) nettoyé(s).",
            'deleted' => $deleted,
        ]);
    }
}
