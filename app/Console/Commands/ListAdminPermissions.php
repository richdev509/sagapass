<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class ListAdminPermissions extends Command
{
    protected $signature = 'admin:list-permissions';
    protected $description = 'Afficher les rôles et permissions de tous les administrateurs';

    public function handle()
    {
        $this->info('📋 Liste des administrateurs et leurs permissions');
        $this->newLine();

        $admins = Admin::with('roles.permissions')->get();

        if ($admins->isEmpty()) {
            $this->warn('Aucun administrateur trouvé.');
            return 0;
        }

        foreach ($admins as $admin) {
            $roles = $admin->roles;
            $permissionsCount = $admin->getAllPermissions()->count();

            // En-tête admin
            $this->line("<fg=cyan>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");

            if ($admin->hasRole('super-admin')) {
                $this->line("👑 <fg=yellow;options=bold>{$admin->name}</> <fg=gray>({$admin->email})</>");
            } else {
                $this->line("👤 <fg=white;options=bold>{$admin->name}</> <fg=gray>({$admin->email})</>");
            }

            // Rôles
            if ($roles->isEmpty()) {
                $this->line("   <fg=red>Aucun rôle attribué</>");
            } else {
                $this->line("   <fg=green>Rôles:</> " . $roles->pluck('name')->map(function($role) {
                    return "<fg=blue>{$role}</>";
                })->implode(', '));
            }

            // Nombre de permissions
            $this->line("   <fg=green>Permissions totales:</> <fg=yellow>{$permissionsCount}</>");

            $this->newLine();
        }

        $this->line("<fg=cyan>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
        $this->newLine();
        $this->info("Total: {$admins->count()} administrateur(s)");

        return 0;
    }
}
