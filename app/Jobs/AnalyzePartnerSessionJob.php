<?php

namespace App\Jobs;

use App\Models\PartnerVerificationSession;
use App\Models\PartnerVerifiedIdentity;
use App\Services\BlacklistScreeningService;
use App\Services\FaceVerification\FaceVerificationService;
use App\Services\SubmittedDataConsistencyChecker;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Lance l'analyse (vivacité active 3-frames + correspondance) d'une
 * PartnerVerificationSession, sur la même queue dédiée 'cv-analysis' que
 * AnalyzeDocumentJob (worker déjà déployé). Contrairement à AnalyzeDocumentJob
 * (couplé à un Document/User SagaID permanent), les photos sont purgées du
 * disque immédiatement après analyse — aucun compte SagaID n'est créé ici,
 * aucune raison de les conserver.
 */
class AnalyzePartnerSessionJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public readonly int $sessionId)
    {
        $this->onQueue('cv-analysis');
        $this->timeout = (int) config('faceverification.timeout_seconds') + 30;
    }

    public function uniqueId(): string
    {
        return (string) $this->sessionId;
    }

    public function handle(FaceVerificationService $service, BlacklistScreeningService $blacklist, SubmittedDataConsistencyChecker $consistency): void
    {
        $session = PartnerVerificationSession::query()->find($this->sessionId);

        if (! $session || ! $session->selfie_center_path) {
            return;
        }

        $disk = Storage::disk('private');

        $result = $service->analyzeWithActiveLiveness(
            documentType: $session->document_type,
            frontPhotoPath: $disk->path($session->front_photo_path),
            backPhotoPath: $session->back_photo_path ? $disk->path($session->back_photo_path) : null,
            selfieCenterPath: $disk->path($session->selfie_center_path),
            selfieLeftPath: $disk->path($session->selfie_left_path),
            selfieRightPath: $disk->path($session->selfie_right_path),
        );

        if (! $result->ranSuccessfully) {
            // Échec technique (pas un verdict métier négatif) : les photos
            // sont volontairement CONSERVÉES — jamais de purge ici — pour
            // qu'un admin puisse trancher manuellement (voir
            // Admin\PartnerSessionReviewController) plutôt que de rejeter à
            // l'aveugle une vraie personne à cause d'un problème technique.
            $session->forceFill([
                'status' => 'awaiting_manual_review',
                'analysis_raw' => ['error' => $result->errorMessage],
            ])->save();

            return;
        }

        // Les informations transmises par le partenaire à la création de la
        // session ne correspondent pas à ce que l'OCR a réellement lu sur la
        // pièce présentée : signe qu'une personne tente de créer un compte
        // avec la pièce de quelqu'un d'autre — rejet direct, sans passer par
        // le criblage liste de vigilance ni l'émission d'un KYC ID.
        if ($consistency->isMismatch($session->partner_submitted_data, $result->ocr)) {
            $session->purgePhotos();

            $session->forceFill([
                'status' => 'failed',
                'ocr_extracted_document_number' => $result->ocr['document_number'],
                'ocr_extracted_full_name' => $result->ocr['full_name'],
                'ocr_extracted_date_of_birth' => $result->ocr['date_of_birth'],
                'analysis_raw' => $result->raw,
                'warnings' => $result->warnings,
                'rejection_reason' => 'data_mismatch',
                'completed_at' => now(),
            ])->save();

            NotifyPartnerSessionWebhook::dispatch($session, 'verification.failed');

            return;
        }

        $session->purgePhotos();

        $screening = $blacklist->screen($result->ocr['document_number'], $result->ocr['full_name']);

        $session->forceFill([
            'status' => 'completed',
            'ocr_extracted_document_number' => $result->ocr['document_number'],
            'ocr_extracted_full_name' => $result->ocr['full_name'],
            'ocr_extracted_date_of_birth' => $result->ocr['date_of_birth'],
            'face_match_score' => $result->faceMatchScore,
            'liveness_passed' => $result->livenessPassed,
            'analysis_raw' => $result->raw,
            'warnings' => $result->warnings,
            'partner_verified_identity_id' => $this->upsertVerifiedIdentity($session, $result)?->id,
            'blacklist_hit' => $screening['hit'],
            'blacklist_matched_identity_id' => $screening['identity']?->id,
            'completed_at' => now(),
        ])->save();

        NotifyPartnerSessionWebhook::dispatch($session, 'verification.completed');
    }

    /**
     * Émet ou renouvelle le KYC ID durable de cette personne — dès qu'une
     * session se termine avec une analyse exécutée sans erreur, quel que soit
     * le résultat (score de correspondance / vivacité indicatifs, laissés au
     * partenaire). Une seule ligne par (partenaire, numéro de document) :
     * une nouvelle session pour le même document prolonge sa validité au lieu
     * d'en créer une autre. Aucun KYC ID n'est émis si l'OCR n'a pas réussi à
     * extraire de numéro de document — limite connue de l'OCR, pas un bug.
     */
    private function upsertVerifiedIdentity(PartnerVerificationSession $session, $result): ?PartnerVerifiedIdentity
    {
        $documentNumber = $result->ocr['document_number'] ?? null;

        if (! $documentNumber) {
            return null;
        }

        return PartnerVerifiedIdentity::updateOrCreate(
            [
                'developer_application_id' => $session->developer_application_id,
                'document_number' => $documentNumber,
            ],
            [
                'document_type' => $session->document_type,
                'full_name' => $result->ocr['full_name'] ?? null,
                'date_of_birth' => $result->ocr['date_of_birth'] ?? null,
                'face_match_score' => $result->faceMatchScore,
                'liveness_passed' => $result->livenessPassed,
                'status' => 'valid',
                'verified_at' => now(),
                'valid_until' => now()->addDays((int) config('faceverification.kyc_validity_days', 90)),
                'webhook_url' => $session->webhook_url,
                'last_partner_verification_session_id' => $session->id,
                'expired_notified_at' => null,
            ],
        );
    }
}
