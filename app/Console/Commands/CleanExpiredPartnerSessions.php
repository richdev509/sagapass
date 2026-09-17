<?php

namespace App\Console\Commands;

use App\Jobs\NotifyPartnerSessionWebhook;
use App\Models\PartnerVerificationSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanExpiredPartnerSessions extends Command
{
    protected $signature = 'sessions:clean-expired
                            {--dry-run : Show what would be cleaned without actually cleaning}';

    protected $description = 'Marque les PartnerVerificationSession abandonnées comme expirées et envoie les webhooks';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Recherche des sessions de vérification expirées...');

        $expiredSessions = PartnerVerificationSession::whereIn('status', [
            'awaiting_id_capture',
            'awaiting_selfie_capture',
            'processing',
        ])->where('expires_at', '<', now())->get();

        if ($expiredSessions->isEmpty()) {
            $this->info('✅ Aucune session expirée trouvée.');

            return Command::SUCCESS;
        }

        $this->warn("⚠️  {$expiredSessions->count()} session(s) expirée(s) trouvée(s)");

        $webhooksSent = 0;

        foreach ($expiredSessions as $session) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Session #{$session->id} ({$session->status}) serait marquée comme expirée");

                if (! empty($session->webhook_url)) {
                    $this->line('    → Webhook serait envoyé vers: ' . parse_url($session->webhook_url, PHP_URL_HOST));
                    $webhooksSent++;
                }

                continue;
            }

            $session->purgePhotos();

            $session->forceFill([
                'status' => 'expired',
                'completed_at' => now(),
            ])->save();

            Log::info('Session de vérification partenaire expirée automatiquement', [
                'session_id' => $session->id,
                'developer_application_id' => $session->developer_application_id,
                'expired_at' => $session->expires_at,
            ]);

            if (! empty($session->webhook_url)) {
                NotifyPartnerSessionWebhook::dispatch($session, 'verification.expired');

                Log::info('Webhook expiration dispatché', [
                    'session_id' => $session->id,
                    'webhook_url' => parse_url($session->webhook_url, PHP_URL_HOST),
                ]);

                $webhooksSent++;
            }
        }

        $this->newLine();
        $this->info('📊 Résumé :');

        if ($dryRun) {
            $this->line("  📨 Webhooks qui seraient envoyés: {$webhooksSent}");
            $this->newLine();
            $this->warn('⚠️  DRY RUN - Aucune modification effectuée');
        } else {
            $this->line("  ✅ Sessions marquées expirées: {$expiredSessions->count()}");
            $this->line("  📨 Webhooks envoyés: {$webhooksSent}");
        }

        return Command::SUCCESS;
    }
}
