<?php

namespace App\Jobs;

use App\Models\PartnerVerificationSession;
use App\Services\FaceVerification\FaceDuplicateService;
use App\Services\FaceVerification\FaceVerificationService;
use App\Services\FaceVerification\PartnerSessionFinalizer;
use App\Services\SubmittedDataConsistencyChecker;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Lance l'analyse (vivacité active 3-frames + correspondance + détection de
 * doublons de visage) d'une PartnerVerificationSession, sur la même queue
 * dédiée 'cv-analysis' que AnalyzeDocumentJob (worker déjà déployé). Les
 * photos sont conservées sans expiration (décision produit : preuve contre
 * l'usurpation d'identité), voir PartnerVerificationSession::purgePhotos().
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

    public function handle(
        FaceVerificationService $service,
        SubmittedDataConsistencyChecker $consistency,
        FaceDuplicateService $duplicates,
        PartnerSessionFinalizer $finalizer,
    ): void {
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
            // Échec technique (pas un verdict métier négatif) : un admin tranche
            // manuellement (voir Admin\PartnerSessionReviewController) plutôt que
            // de rejeter à l'aveugle une vraie personne à cause d'un problème
            // technique.
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

        // Détection de doublons de visage, globale (tous partenaires) : une
        // personne peut avoir une pièce de chaque type, jamais deux du même
        // type. Un cas suspect part en revue manuelle — jamais de rejet
        // automatique (jumeaux, fausses correspondances possibles).
        if ($result->faceEmbedding === null) {
            $duplicateCheck = ['verdict' => 'unchecked', 'severity' => null, 'matches' => []];
            $suspicious = false;
        } else {
            $check = $duplicates->check(
                $result->faceEmbedding,
                $session->document_type,
                $result->ocr['document_number'],
                $result->ocr['full_name'],
                $result->ocr['date_of_birth'],
            );
            $duplicateCheck = $check->toArray();
            $suspicious = $check->isSuspicious();
        }

        $analysis = [
            'analysis_raw' => $result->raw,
            'warnings' => $result->warnings,
            'duplicate_check' => $duplicateCheck,
        ];

        if ($suspicious) {
            $session->forceFill([
                ...$analysis,
                'status' => 'awaiting_manual_review',
                'ocr_extracted_document_number' => $result->ocr['document_number'],
                'ocr_extracted_full_name' => $result->ocr['full_name'],
                'ocr_extracted_date_of_birth' => $result->ocr['date_of_birth'],
                'face_match_score' => $result->faceMatchScore,
                'liveness_passed' => $result->livenessPassed,
                'pending_face_embedding' => $result->faceEmbedding,
            ])->save();

            return;
        }

        // L'empreinte n'est enregistrée dans le registre global que si la
        // vivacité est confirmée : jamais le visage d'une photo ou d'un écran.
        $finalizer->complete(
            $session,
            $result->ocr,
            $result->faceMatchScore,
            $result->livenessPassed,
            $result->faceEmbedding,
            enrollFace: $result->livenessPassed === true,
            attributes: $analysis,
        );
    }
}
