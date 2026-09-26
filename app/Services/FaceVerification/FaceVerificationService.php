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

        return $this->toResult($decoded);
    }

    /**
     * Vivacité active 3-frames (flux session partenaire QR) — voir
     * FaceVerificationScriptClient::analyzeWithActiveLiveness().
     */
    public function analyzeWithActiveLiveness(
        string $documentType,
        string $frontPhotoPath,
        ?string $backPhotoPath,
        string $selfieCenterPath,
        string $selfieLeftPath,
        string $selfieRightPath,
    ): FaceVerificationResult {
        try {
            $decoded = $this->client->analyzeWithActiveLiveness(
                $documentType,
                $frontPhotoPath,
                $backPhotoPath,
                $selfieCenterPath,
                $selfieLeftPath,
                $selfieRightPath,
            );
        } catch (FaceVerificationUnavailableException $e) {
            Log::warning('FaceVerificationService: moteur indisponible (vivacité active)', ['message' => $e->getMessage()]);

            return FaceVerificationResult::failed($e->getMessage());
        }

        return $this->toResult($decoded);
    }

    private function toResult(array $decoded): FaceVerificationResult
    {
        // Tous les champs renvoyés par analyze.py, pas seulement les 3
        // historiques (document_number/full_name/date_of_birth) : le jeu de
        // clés dépend du type de document (voir _ocr_field_keys côté Python)
        // — nationalité/MRZ pour un passeport, adresse/groupe sanguin/
        // catégorie pour un permis, etc. Ces champs supplémentaires doivent
        // survivre jusqu'à PartnerSessionFinalizer pour être conservés sur
        // l'identité vérifiée (PartnerVerifiedIdentity::extracted_fields),
        // pas seulement dans le blob d'audit brut.
        $ocr = $decoded['ocr'] ?? [];

        // L'empreinte faciale est une donnée biométrique : jamais recopiée dans
        // $raw (persisté tel quel dans analysis_raw, en clair) — elle voyage
        // à part, dans $faceEmbedding, et est stockée chiffrée par
        // FaceDuplicateService/FaceEmbedding.
        $embedding = $decoded['face_embedding'] ?? null;
        unset($decoded['face_embedding']);

        return FaceVerificationResult::completed(
            ocr: $ocr,
            livenessPassed: $decoded['liveness_passed'] ?? null,
            faceMatchScore: isset($decoded['face_match_score']) ? (float) $decoded['face_match_score'] : null,
            warnings: $decoded['warnings'] ?? [],
            raw: $decoded,
            faceEmbedding: is_array($embedding) && $embedding !== [] ? array_map('floatval', $embedding) : null,
        );
    }
}
