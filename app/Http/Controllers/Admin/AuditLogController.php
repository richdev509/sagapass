<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:view-audit-logs,admin']);
    }

    /**
     * Afficher la liste des logs d'audit
     */
    public function index(Request $request)
    {
        $query = AuditLog::with(['admin', 'user'])->latest();

        // Filtrer par action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(50);

        // Statistiques
        $stats = [
            'verified' => AuditLog::where('action', 'document_verified')->count(),
            'rejected' => AuditLog::where('action', 'document_rejected')->count(),
            'admins_created' => AuditLog::where('action', 'admin_created')->count(),
            'total' => AuditLog::count(),
        ];

        return view('admin.audit-logs.index', compact('logs', 'stats'));
    }
}
