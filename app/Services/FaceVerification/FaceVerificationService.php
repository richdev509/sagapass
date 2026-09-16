<?php

namespace App\Services\FaceVerification;

use App\Exceptions\FaceVerificationUnavailableException;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration : appelle FaceVerificationScriptClient et convertit tout échec
 * technique en FaceVerificationResult::failed() — même principe que
 * NiuLookupService côté SwapLajan. Ne lève jamais.
 */
class FaceVerificationService
{
    public function __construct(private readonly FaceVerificationScriptClient $client) {}

    public function analyze(string $documentType, string $frontPhotoPath, ?string $backPhotoPath, string $selfiePath): FaceVerificationResult
    {
        try {
            $decoded = $this->client->analyze($documentType, $frontPhotoPath, $backPhotoPath, $selfiePath);
        } catch (FaceVerificationUnavailableException $e) {
            Log::warning('FaceVerificationService: moteur indisponible', ['message' => $e->getMessage()]);

            return FaceVerificationResult::failed($e->getMessage());
        }

        $ocr = [
            'document_number' => $decoded['ocr']['document_number'] ?? null,
            'full_name' => $decoded['ocr']['full_name'] ?? null,
            'date_of_birth' => $decoded['ocr']['date_of_birth'] ?? null,
        ];

        return FaceVerificationResult::completed(
            ocr: $ocr,
            livenessPassed: $decoded['liveness_passed'] ?? null,
            faceMatchScore: isset($decoded['face_match_score']) ? (float) $decoded['face_match_score'] : null,
            warnings: $decoded['warnings'] ?? [],
            raw: $decoded,
        );
    }
}
