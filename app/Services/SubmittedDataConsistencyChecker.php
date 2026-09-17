<?php

namespace App\Services;

/**
 * Détecte une fraude par usurpation : les informations texte transmises par
 * le partenaire à la création de la session (voir
 * PartnerVerificationSession::partner_submitted_data) ne correspondent pas à
 * ce que l'OCR a réellement lu sur la pièce présentée — signe qu'une
 * personne tente de créer un compte avec la pièce de quelqu'un d'autre.
 * Ne se prononce que si l'OCR a effectivement lu quelque chose : pas de
 * comparaison possible = pas de signal, jamais traité comme une fraude.
 */
class SubmittedDataConsistencyChecker
{
    public function isMismatch(?array $submittedData, array $ocr): bool
    {
        if (! $submittedData) {
            return false;
        }

        if ($this->nameMismatch($submittedData, $ocr)) {
            return true;
        }

        return $this->dateOfBirthMismatch($submittedData, $ocr);
    }

    private function nameMismatch(array $submittedData, array $ocr): bool
    {
        $ocrFullName = $ocr['full_name'] ?? null;
        $firstName = $submittedData['first_name'] ?? null;
        $lastName = $submittedData['last_name'] ?? null;

        if (! $ocrFullName || (! $firstName && ! $lastName)) {
            return false;
        }

        $normalizedOcr = $this->normalize($ocrFullName);

        foreach (array_filter([$firstName, $lastName]) as $part) {
            if (! str_contains($normalizedOcr, $this->normalize($part))) {
                return true;
            }
        }

        return false;
    }

    private function dateOfBirthMismatch(array $submittedData, array $ocr): bool
    {
        $submitted = $submittedData['date_of_birth'] ?? null;
        $ocrDate = $ocr['date_of_birth'] ?? null;

        if (! $submitted || ! $ocrDate) {
            return false;
        }

        return substr($submitted, 0, 10) !== substr($ocrDate, 0, 10);
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($value)));
    }
}
