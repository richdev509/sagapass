<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class ResetAdminPermissions extends Command
{
    protected $signature = 'admin:reset-permissions';
    protected $description = 'Retirer tous les rôles des admins sauf le super-admin';

    public function handle()
    {
        $this->info('🔄 Réinitialisation des permissions des administrateurs...');
        $this->newLine();

        // Récupérer tous les admins
        $admins = Admin::all();

        $resetCount = 0;
        $superAdminCount = 0;

        foreach ($admins as $admin) {
            // Vérifier si c'est un super-admin
            if ($admin->hasRole('super-admin')) {
                $this->line("👑 <fg=yellow>{$admin->name}</> ({$admin->email}) - Super Admin préservé");
                $superAdminCount++;
                continue;
            }

            // Récupérer les rôles et permissions avant suppression
            $oldRoles = $admin->roles->pluck('name')->toArray();
            $oldDirectPermissions = $admin->permissions->pluck('name')->toArray();

            $hasChanges = false;

            // Retirer tous les rôles
            if (!empty($oldRoles)) {
                $admin->syncRoles([]);
                $hasChanges = true;
            }

            // Retirer toutes les permissions directes
            if (!empty($oldDirectPermissions)) {
                $admin->syncPermissions([]);
                $hasChanges = true;
            }

            if ($hasChanges) {
                $details = [];
                if (!empty($oldRoles)) {
                    $details[] = "Rôles: " . implode(', ', $oldRoles);
                }
                if (!empty($oldDirectPermissions)) {
                    $details[] = "Permissions directes: " . count($oldDirectPermissions);
                }

                $this->line("✅ <fg=green>{$admin->name}</> ({$admin->email}) - " . implode(' | ', $details));
                $resetCount++;
            } else {
                $this->line("⚪ <fg=gray>{$admin->name}</> ({$admin->email}) - Aucun rôle/permission à retirer");
            }
        }

        $this->newLine();
        $this->info("✨ Réinitialisation terminée !");
        $this->info("   Super-admins préservés: {$superAdminCount}");
        $this->info("   Admins réinitialisés: {$resetCount}");

        return 0;
    }
}
