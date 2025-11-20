<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;

class AssignPermissionsToExistingAdminsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Attribution des permissions aux administrateurs existants...');

        $admins = Admin::all();

        foreach ($admins as $admin) {
            // Donner toutes les permissions à chaque admin
            $admin->givePermissionTo([
                'verify-documents',
                'manage-admins',
                'view-audit-logs'
            ]);

            $this->command->info("✓ Permissions attribuées à: {$admin->name} ({$admin->email})");
        }

        $this->command->info("✓ {$admins->count()} administrateur(s) mis à jour avec succès!");
    }
}
