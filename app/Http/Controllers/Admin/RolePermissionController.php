<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Liste des rôles
     */
    public function roles()
    {
        $this->authorize('view-roles');

        $roles = Role::where('guard_name', 'admin')
            ->withCount('permissions')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Créer un rôle
     */
    public function createRole()
    {
        $this->authorize('create-roles');

        $permissions = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->get()
            ->groupBy(function($permission) {
                // Grouper par catégorie (premier mot avant le tiret)
                $parts = explode('-', $permission->name);
                return $parts[0];
            });

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Enregistrer un nouveau rôle
     */
    public function storeRole(Request $request)
    {
        $this->authorize('create-roles');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name' => 'admin',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Audit log
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'action' => 'create_role',
            'description' => "Rôle créé : {$role->name} avec " . count($validated['permissions'] ?? []) . " permissions",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle créé avec succès');
    }

    /**
     * Modifier un rôle
     */
    public function editRole(Role $role)
    {
        $this->authorize('edit-roles');

        $permissions = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->get()
            ->groupBy(function($permission) {
                $parts = explode('-', $permission->name);
                return $parts[0];
            });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Mettre à jour un rôle
     */
    public function updateRole(Request $request, Role $role)
    {
        $this->authorize('edit-roles');

        // Empêcher la modification du super-admin
        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', 'Le rôle Super Admin ne peut pas être modifié');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $oldData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        // Audit log
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'action' => 'update_role',
            'description' => "Rôle modifié : {$role->name} - Permissions mises à jour (" . count($validated['permissions'] ?? []) . " permissions)",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle mis à jour avec succès');
    }

    /**
     * Supprimer un rôle
     */
    public function deleteRole(Request $request, Role $role)
    {
        $this->authorize('delete-roles');

        // Empêcher la suppression du super-admin
        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', 'Le rôle Super Admin ne peut pas être supprimé');
        }

        // Vérifier si des admins ont ce rôle
        $adminsCount = Admin::role($role->name)->count();
        if ($adminsCount > 0) {
            return redirect()->back()->with('error', "Ce rôle est attribué à {$adminsCount} administrateur(s)");
        }

        $roleName = $role->name;
        $role->delete();

        // Audit log
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'action' => 'delete_role',
            'description' => "Rôle supprimé : {$roleName}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle supprimé avec succès');
    }

    /**
     * Liste des permissions
     */
    public function permissions()
    {
        $this->authorize('view-permissions');

        $permissions = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->get()
            ->groupBy(function($permission) {
                $parts = explode('-', $permission->name);
                return $parts[0];
            });

        return view('admin.roles.permissions', compact('permissions'));
    }

    /**
     * Gérer les permissions d'un admin
     */
    public function manageAdminRoles(Admin $admin)
    {
        $this->authorize('assign-roles');

        $roles = Role::where('guard_name', 'admin')->get();
        $adminRoles = $admin->roles->pluck('name')->toArray();

        return view('admin.roles.assign', compact('admin', 'roles', 'adminRoles'));
    }

    /**
     * Attribuer des rôles à un admin
     */
    public function assignRoles(Request $request, Admin $admin)
    {
        $this->authorize('assign-roles');

        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $oldRoles = $admin->roles->pluck('name')->toArray();
        $admin->syncRoles($validated['roles'] ?? []);

        // Audit log
        AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'action' => 'assign_roles',
            'description' => "Rôles attribués à {$admin->name} : " . implode(', ', $validated['roles'] ?? ['aucun']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Rôles attribués avec succès');
    }
}
