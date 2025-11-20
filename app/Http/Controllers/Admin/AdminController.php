<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'permission:manage-admins,admin']);
    }

    /**
     * Liste des administrateurs
     */
    public function index()
    {
        $admins = Admin::with('roles')->latest()->paginate(20);

        return view('admin.admins.index', compact('admins'));
    }

    /**
     * Formulaire de création d'admin
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.admins.create', compact('roles'));
    }

    /**
     * Créer un nouvel administrateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        $admin->assignRole($validated['role']);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'admin_created',
            'description' => "Administrateur créé : {$admin->name} ({$admin->email}) - Rôle: {$validated['role']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrateur créé avec succès !');
    }

    /**
     * Formulaire d'édition d'admin
     */
    public function edit(Admin $admin)
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    /**
     * Mettre à jour un administrateur
     */
    public function update(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ]);

        if ($request->filled('password')) {
            $admin->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Mettre à jour le rôle
        $admin->syncRoles([$validated['role']]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'admin_updated',
            'description' => "Administrateur modifié : {$admin->name} ({$admin->email})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrateur mis à jour avec succès !');
    }

    /**
     * Basculer le statut d'un admin (actif/inactif)
     */
    public function toggleStatus(Request $request, Admin $admin)
    {
        // Empêcher de se désactiver soi-même
        if ($admin->id === Auth::guard('admin')->id()) {
            return redirect()
                ->back()
                ->with('error', 'Vous ne pouvez pas modifier votre propre statut.');
        }

        $newStatus = $admin->status === 'active' ? 'inactive' : 'active';
        $admin->update(['status' => $newStatus]);

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'admin_status_changed',
            'description' => "Statut de {$admin->name} changé : {$newStatus}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Statut de l'administrateur mis à jour.");
    }

    /**
     * Supprimer un administrateur
     */
    public function destroy(Request $request, Admin $admin)
    {
        // Empêcher de se supprimer soi-même
        if ($admin->id === Auth::guard('admin')->id()) {
            return redirect()
                ->back()
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $adminName = $admin->name;
        $adminEmail = $admin->email;

        $admin->delete();

        // Log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'action' => 'admin_deleted',
            'description' => "Administrateur supprimé : {$adminName} ({$adminEmail})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Administrateur supprimé avec succès.');
    }

    /**
     * Afficher la page de gestion des permissions d'un admin
     */
    public function editPermissions(Admin $admin)
    {
        // Récupérer toutes les permissions disponibles
        $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->get();
        
        // Récupérer les permissions actuelles de l'admin
        $adminPermissions = $admin->permissions->pluck('name')->toArray();
        
        return view('admin.admins.permissions', compact('admin', 'allPermissions', 'adminPermissions'));
    }

    /**
     * Mettre à jour les permissions d'un admin
     */
    public function updatePermissions(Request $request, Admin $admin)
    {
        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        // Synchroniser les permissions
        $permissions = $request->input('permissions', []);
        $admin->syncPermissions($permissions);

        // Créer un log d'audit
        AuditLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'user_id' => null,
            'action' => 'admin_permissions_updated',
            'description' => "Permissions mises à jour pour {$admin->name}: " . implode(', ', $permissions),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Permissions mises à jour avec succès.');
    }
}

