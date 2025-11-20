<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SyncUserVerificationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-verification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise le statut de vérification des utilisateurs selon leurs documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation des statuts de vérification...');

        $users = User::with('documents')->get();
        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $totalDocuments = $user->documents()->count();

            if ($totalDocuments === 0) {
                $skipped++;
                continue;
            }

            $verifiedDocuments = $user->documents()->where('verification_status', 'verified')->count();
            $rejectedDocuments = $user->documents()->where('verification_status', 'rejected')->count();

            $oldStatus = $user->verification_status;
            $newStatus = $oldStatus;

            // Si tous les documents sont vérifiés
            if ($totalDocuments === $verifiedDocuments) {
                $newStatus = 'verified';
                $user->update([
                    'verification_status' => 'verified',
                    'account_status' => 'active',
                ]);
            }
            // Si au moins un document est rejeté
            elseif ($rejectedDocuments > 0) {
                $newStatus = 'rejected';
                $user->update([
                    'verification_status' => 'rejected',
                ]);
            }
            // Sinon, reste en pending
            else {
                $newStatus = 'pending';
                $user->update([
                    'verification_status' => 'pending',
                ]);
            }

            if ($oldStatus !== $newStatus) {
                $updated++;
                $this->line("✅ User #{$user->id} ({$user->email}): {$oldStatus} → {$newStatus}");
            } else {
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("✨ Synchronisation terminée !");
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['Mis à jour', $updated],
                ['Inchangés', $skipped],
                ['Total', $users->count()],
            ]
        );

        return Command::SUCCESS;
    }
}
