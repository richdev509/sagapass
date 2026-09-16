<?php

namespace App\Jobs;

use App\Models\PartnerVerificationSession;
use App\Services\FaceVerification\FaceVerificationService;
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
 *
 * TODO (round "système en profondeur") : dispatcher NotifyPartnerSessionWebhook
 * pour notifier le partenaire — pas encore branché dans cette première passe.
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

    public function handle(FaceVerificationService $service): void
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

        $this->purgePhotos($session);

        if (! $result->ranSuccessfully) {
            $session->forceFill([
                'status' => 'failed',
                'analysis_raw' => ['error' => $result->errorMessage],
                'completed_at' => now(),
            ])->save();

            return;
        }

        $session->forceFill([
            'status' => 'completed',
            'ocr_extracted_document_number' => $result->ocr['document_number'],
            'ocr_extracted_full_name' => $result->ocr['full_name'],
            'ocr_extracted_date_of_birth' => $result->ocr['date_of_birth'],
            'face_match_score' => $result->faceMatchScore,
            'liveness_passed' => $result->livenessPassed,
            'analysis_raw' => $result->raw,
            'warnings' => $result->warnings,
            'completed_at' => now(),
        ])->save();
    }

    private function purgePhotos(PartnerVerificationSession $session): void
    {
        $disk = Storage::disk('private');

        foreach ([
            $session->front_photo_path,
            $session->back_photo_path,
            $session->selfie_left_path,
            $session->selfie_center_path,
            $session->selfie_right_path,
        ] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
    }
}
