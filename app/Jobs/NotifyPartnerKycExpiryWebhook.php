<?php

namespace App\Jobs;

use App\Models\PartnerVerifiedIdentity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alerte proactive du partenaire quand un KYC ID durable (PartnerVerifiedIdentity)
 * atteint sa fin de validité — dispatché par la commande
 * kyc-identities:notify-expired, jamais directement. Même convention HMAC que
 * NotifyPartnerSessionWebhook (secret dédié 'services.sagaid_webhook.secret').
 */
class NotifyPartnerKycExpiryWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(protected PartnerVerifiedIdentity $identity) {}

    public function handle(): void
    {
        if (empty($this->identity->webhook_url)) {
            Log::info('NotifyPartnerKycExpiryWebhook - Aucun webhook_url défini', [
                'kyc_id' => $this->identity->kyc_id,
            ]);

            return;
        }

        $payload = $this->buildPayload();
        $signature = $this->generateSignature($payload);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Saga-Signature' => $signature,
                    'X-Saga-Event' => 'kyc.expired',
                    'User-Agent' => 'SagaPass-Webhook/1.0',
                ])
                ->post($this->identity->webhook_url, $payload);

            if ($response->successful()) {
                Log::info('NotifyPartnerKycExpiryWebhook - Webhook envoyé avec succès', [
                    'kyc_id' => $this->identity->kyc_id,
                    'webhook_url' => $this->maskUrl($this->identity->webhook_url),
                    'status_code' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::warning('NotifyPartnerKycExpiryWebhook - Webhook réponse non-200', [
                    'kyc_id' => $this->identity->kyc_id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'attempt' => $this->attempts(),
                ]);

                if ($response->status() >= 500) {
                    throw new \Exception("Webhook server error: {$response->status()}");
                }
            }
        } catch (\Exception $e) {
            Log::error('NotifyPartnerKycExpiryWebhook - Erreur envoi webhook', [
                'kyc_id' => $this->identity->kyc_id,
                'webhook_url' => $this->maskUrl($this->identity->webhook_url),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    private function buildPayload(): array
    {
        return [
            'event' => 'kyc.expired',
            'kyc_id' => $this->identity->kyc_id,
            'document_type' => $this->identity->document_type,
            'document_number' => $this->identity->document_number,
            'verified_at' => $this->identity->verified_at->toIso8601String(),
            'expired_at' => $this->identity->valid_until->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function generateSignature(array $payload): string
    {
        $secret = config('services.sagaid_webhook.secret');

        if (empty($secret)) {
            Log::warning('NotifyPartnerKycExpiryWebhook - services.sagaid_webhook.secret non configuré, fallback app.key', [
                'kyc_id' => $this->identity->kyc_id,
            ]);
            $secret = config('app.key');
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
    }

    private function maskUrl(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'unknown';
        $path = $parsed['path'] ?? '/';

        return $host . $path;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyPartnerKycExpiryWebhook - Job échoué après tous les retries', [
            'kyc_id' => $this->identity->kyc_id,
            'webhook_url' => $this->maskUrl($this->identity->webhook_url),
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
    }
}
