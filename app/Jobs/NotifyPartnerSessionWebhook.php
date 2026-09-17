<?php

namespace App\Jobs;

use App\Models\PartnerVerificationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifie le partenaire du résultat final d'une PartnerVerificationSession
 * (completed/failed/expired) — même convention HMAC que NotifyPartnerWebhook
 * (challenges), mais signée avec le secret dédié 'services.sagaid_webhook.secret'
 * plutôt que le client_secret du partenaire, pour ne pas coupler la rotation
 * de l'un à celle de l'autre.
 */
class NotifyPartnerSessionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    /**
     * @param string $event 'verification.completed' | 'verification.failed' | 'verification.expired'
     */
    public function __construct(
        protected PartnerVerificationSession $session,
        protected string $event,
    ) {
        // Réutilise le worker 'cv-analysis' déjà déployé (queue:work redis
        // --queue=cv-analysis) plutôt que la queue 'default', qu'aucun worker
        // ne consomme actuellement en production.
        $this->onQueue('cv-analysis');
    }

    public function handle(): void
    {
        if (empty($this->session->webhook_url)) {
            Log::info('NotifyPartnerSessionWebhook - Aucun webhook_url défini', [
                'session_id' => $this->session->id,
                'event' => $this->event,
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
                    'X-Saga-Event' => $this->event,
                    'X-Saga-Session-Token' => $this->session->token,
                    'User-Agent' => 'SagaPass-Webhook/1.0',
                ])
                ->post($this->session->webhook_url, $payload);

            if ($response->successful()) {
                Log::info('NotifyPartnerSessionWebhook - Webhook envoyé avec succès', [
                    'session_id' => $this->session->id,
                    'event' => $this->event,
                    'webhook_url' => $this->maskUrl($this->session->webhook_url),
                    'status_code' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::warning('NotifyPartnerSessionWebhook - Webhook réponse non-200', [
                    'session_id' => $this->session->id,
                    'event' => $this->event,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'attempt' => $this->attempts(),
                ]);

                if ($response->status() >= 500) {
                    throw new \Exception("Webhook server error: {$response->status()}");
                }
            }
        } catch (\Exception $e) {
            Log::error('NotifyPartnerSessionWebhook - Erreur envoi webhook', [
                'session_id' => $this->session->id,
                'event' => $this->event,
                'webhook_url' => $this->maskUrl($this->session->webhook_url),
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
        $ocr = $this->session->analysis_raw['ocr'] ?? [];

        return [
            'event' => $this->event,
            'session_token' => $this->session->token,
            'partner_reference' => $this->session->partner_reference,
            'kyc_id' => $this->session->partnerVerifiedIdentity?->kyc_id,
            'kyc_valid_until' => $this->session->partnerVerifiedIdentity?->valid_until?->toIso8601String(),
            // 'document_reject' (numéro de pièce en liste de vigilance) |
            // 'name_alert' (nom correspondant, signal faible) | null.
            'blacklist_hit' => $this->session->blacklist_hit,
            'status' => $this->session->status,
            'face_match_score' => $this->session->face_match_score,
            'liveness_passed' => $this->session->liveness_passed,
            'ocr' => [
                'document_number' => $this->session->ocr_extracted_document_number,
                'full_name' => $this->session->ocr_extracted_full_name,
                'date_of_birth' => $this->session->ocr_extracted_date_of_birth?->format('Y-m-d'),
                'sex' => $ocr['sex'] ?? null,
                'place_of_birth' => $ocr['place_of_birth'] ?? null,
                'date_of_issue' => $ocr['date_of_issue'] ?? null,
                'date_of_expiry' => $ocr['date_of_expiry'] ?? null,
            ],
            'warnings' => $this->session->warnings,
            'completed_at' => $this->session->completed_at?->toIso8601String(),
            'expires_at' => $this->session->expires_at->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function generateSignature(array $payload): string
    {
        $secret = config('services.sagaid_webhook.secret');

        if (empty($secret)) {
            Log::warning('NotifyPartnerSessionWebhook - services.sagaid_webhook.secret non configuré, fallback app.key', [
                'session_id' => $this->session->id,
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
        Log::error('NotifyPartnerSessionWebhook - Job échoué après tous les retries', [
            'session_id' => $this->session->id,
            'event' => $this->event,
            'webhook_url' => $this->maskUrl($this->session->webhook_url),
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
    }
}
