<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\FaceVerification\FaceVerificationService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Lance l'analyse OCR + vivacité + correspondance faciale d'un Document (voir
 * scripts/face-verification/), en tâche de fond sur une queue dédiée
 * ('cv-analysis' — jamais 'default', même rationale que la queue 'kyc-lookup'
 * côté SwapLajan : un subprocess lent ne doit jamais retarder les autres
 * jobs).
 *
 * Écrit uniquement les champs d'analyse automatisée (automated_check_status,
 * ocr_extracted_*, face_match_score, liveness_passed, automated_analysis_raw)
 * — ne touche JAMAIS verification_status, qui reste entièrement décidé par un
 * admin (voir Admin\VerificationController). Ce résultat est un signal
 * indicatif pour l'admin, pas une approbation automatique.
 *
 * Aucun retry automatique de CE job (voir $tries) — un nouvel essai après un
 * échec technique est toujours un geste humain explicite (bouton admin, à
 * ajouter dans un round ultérieur), même principe que LookupNiuJob côté
 * SwapLajan.
 */
class AnalyzeDocumentJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public readonly int $documentId)
    {
        $this->onQueue('cv-analysis');
        $this->timeout = (int) config('faceverification.timeout_seconds') + 30;
    }

    public function uniqueId(): string
    {
        return (string) $this->documentId;
    }

    public function handle(FaceVerificationService $service): void
    {
        $document = Document::query()->find($this->documentId);

        if (! $document || ! $document->selfie_path) {
            return;
        }

        $document->forceFill(['automated_check_status' => 'processing'])->save();

        $result = $service->analyze(
            documentType: $document->document_type,
            frontPhotoPath: Storage::disk('private')->path($document->front_photo_path),
            backPhotoPath: $document->back_photo_path ? Storage::disk('private')->path($document->back_photo_path) : null,
            selfiePath: Storage::disk('private')->path($document->selfie_path),
        );

        if (! $result->ranSuccessfully) {
            $document->forceFill([
                'automated_check_status' => 'failed',
                'automated_analysis_raw' => ['error' => $result->errorMessage],
            ])->save();

            return;
        }

        $document->forceFill([
            'automated_check_status' => 'completed',
            'ocr_extracted_document_number' => $result->ocr['document_number'],
            'ocr_extracted_full_name' => $result->ocr['full_name'],
            'ocr_extracted_date_of_birth' => $result->ocr['date_of_birth'],
            'face_match_score' => $result->faceMatchScore,
            'liveness_passed' => $result->livenessPassed,
            'automated_analysis_raw' => $result->raw,
        ])->save();
    }
}
