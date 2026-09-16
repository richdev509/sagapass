<?php

namespace App\Services\FaceVerification;

/**
 * Ne lève jamais côté appelant (voir FaceVerificationService) — même principe
 * que NiuLookupResult côté SwapLajan. ::completed() reflète le résultat métier
 * du moteur (même si liveness/correspondance échouent, ce n'est pas une
 * "erreur" technique) ; ::failed() est réservé aux pannes techniques (script
 * indisponible, JSON invalide, image illisible).
 */
final readonly class FaceVerificationResult
{
    /**
     * @param array{document_number: ?string, full_name: ?string, date_of_birth: ?string} $ocr
     * @param list<string> $warnings
     */
    private function __construct(
        public bool $ranSuccessfully,
        public array $ocr,
        public ?bool $livenessPassed,
        public ?float $faceMatchScore,
        public array $warnings,
        public ?string $errorMessage,
        public array $raw,
    ) {}

    /**
     * @param array{document_number: ?string, full_name: ?string, date_of_birth: ?string} $ocr
     * @param list<string> $warnings
     * @param array<string, mixed> $raw
     */
    public static function completed(array $ocr, ?bool $livenessPassed, ?float $faceMatchScore, array $warnings, array $raw): self
    {
        return new self(true, $ocr, $livenessPassed, $faceMatchScore, $warnings, null, $raw);
    }

    public static function failed(string $errorMessage): self
    {
        return new self(false, [], null, null, [], $errorMessage, []);
    }
}
