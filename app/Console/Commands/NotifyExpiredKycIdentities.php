<?php

namespace App\Console\Commands;

use App\Jobs\NotifyPartnerKycExpiryWebhook;
use App\Models\PartnerVerifiedIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyExpiredKycIdentities extends Command
{
    protected $signature = 'kyc-identities:notify-expired
                            {--dry-run : Show what would be notified without actually notifying}';

    protected $description = 'Marque les KYC ID durables expirés et alerte le partenaire par webhook (kyc.expired)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Recherche des KYC ID expirés...');

        $expiredIdentities = PartnerVerifiedIdentity::where('status', 'valid')
            ->where('valid_until', '<', now())
            ->whereNull('expired_notified_at')
            ->get();

        if ($expiredIdentities->isEmpty()) {
            $this->info('✅ Aucun KYC ID expiré trouvé.');

            return Command::SUCCESS;
        }

        $this->warn("⚠️  {$expiredIdentities->count()} KYC ID expiré(s) trouvé(s)");

        $webhooksSent = 0;

        foreach ($expiredIdentities as $identity) {
            if ($dryRun) {
                $this->line("  [DRY RUN] KYC ID {$identity->kyc_id} serait marqué comme expiré");

                if (! empty($identity->webhook_url)) {
                    $this->line('    → Webhook serait envoyé vers: ' . parse_url($identity->webhook_url, PHP_URL_HOST));
                    $webhooksSent++;
                }

                continue;
            }

            $identity->forceFill(['status' => 'expired'])->save();

            Log::info('KYC ID durable expiré automatiquement', [
                'kyc_id' => $identity->kyc_id,
                'developer_application_id' => $identity->developer_application_id,
                'valid_until' => $identity->valid_until,
            ]);

            if (! empty($identity->webhook_url)) {
                NotifyPartnerKycExpiryWebhook::dispatch($identity);
                $identity->forceFill(['expired_notified_at' => now()])->save();

                Log::info('Webhook kyc.expired dispatché', [
                    'kyc_id' => $identity->kyc_id,
                    'webhook_url' => parse_url($identity->webhook_url, PHP_URL_HOST),
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
            $this->line("  ✅ KYC ID marqués expirés: {$expiredIdentities->count()}");
            $this->line("  📨 Webhooks envoyés: {$webhooksSent}");
        }

        return Command::SUCCESS;
    }
}
