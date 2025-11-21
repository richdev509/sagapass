<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateExistingUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Ce seeder marque tous les utilisateurs existants comme "verified"
     * pour maintenir la compatibilité avec l'ancien système.
     */
    public function run(): void
    {
        $this->command->info('🔄 Mise à jour des utilisateurs existants...');
        
        // Compter les utilisateurs existants
        $count = DB::table('users')->count();
        
        if ($count === 0) {
            $this->command->warn('Aucun utilisateur trouvé.');
            return;
        }
        
        // Mettre à jour tous les utilisateurs existants
        $updated = DB::table('users')->update([
            'account_level' => 'verified',
            'verification_level' => 'document',
            'video_status' => 'approved',
            'verified_at' => now(),
            'video_verified_at' => now(),
        ]);
        
        $this->command->info("✅ {$updated} utilisateur(s) mis à jour comme 'verified'");
        $this->command->info('Ces comptes gardent leur accès complet au système.');
    }
}
