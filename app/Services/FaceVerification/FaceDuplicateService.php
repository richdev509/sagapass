<?php

namespace App\Services\FaceVerification;

use App\Models\DeveloperApplication;
use App\Models\FaceEmbedding;
use App\Models\PartnerVerificationSession;
use App\Models\PartnerVerifiedIdentity;
use Illuminate\Support\Str;

/**
 * Détection de doublons de visage, GLOBALE (tous partenaires).
 *
 * Règle métier : une personne peut avoir une pièce de chaque type (passeport,
 * carte nationale, permis) mais jamais deux du même type. Comparaison de
 * l'empreinte du selfie à toutes les empreintes déjà enregistrées :
 *
 *  - même visage, même type ET même numéro  -> même pièce revérifiée (OK)
 *  - même visage, type différent, identité cohérente -> OK
 *  - même visage, même type, numéro différent -> duplicate_same_type (revue)
 *  - même visage, nom ou date de naissance différents -> identity_conflict (revue, fort)
 *  - même numéro de pièce, visage différent -> document_face_mismatch (revue, fort)
 *
 * Aucun rejet automatique : le verdict envoie la session en revue manuelle.
 */
class FaceDuplicateService
{
    /** Ordre de gravité — le pire verdict l'emporte. */
    private const SEVERITY_ORDER = [
        DuplicateCheckResult::VERDICT_DUPLICATE_SAME_TYPE => 1,
        DuplicateCheckResult::VERDICT_IDENTITY_CONFLICT => 2,
        DuplicateCheckResult::VERDICT_DOCUMENT_FACE_MISMATCH => 2,
    ];

    private const MAX_MATCHES = 5;

    /**
     * @param list<float> $embedding
     */
    public function check(array $embedding, ?string $documentType, ?string $documentNumber, ?string $fullName, ?string $dateOfBirth): DuplicateCheckResult
    {
        $threshold = (float) config('faceverification.duplicate_similarity_threshold');
        $type = $this->normalizeType($documentType);
        $number = $this->normalizeNumber($documentNumber);

        $suspects = [];

        foreach (FaceEmbedding::query()->cursor() as $row) {
            $stored = $row->embedding;
            if (! is_array($stored) || count($stored) !== count($embedding)) {
                continue;
            }

            $similarity = $this->cosineSimilarity($embedding, $stored);
            $faceMatches = $similarity >= $threshold;

            $sameType = $type !== null && $this->normalizeType($row->document_type) === $type;
            $sameNumber = $number !== null && $this->normalizeNumber($row->document_number) === $number;

            $verdict = null;

            if ($faceMatches) {
                if ($sameType && $sameNumber) {
                    continue; // même pièce revérifiée
                }

                if ($this->identityConflicts($row, $fullName, $dateOfBirth)) {
                    $verdict = DuplicateCheckResult::VERDICT_IDENTITY_CONFLICT;
                } elseif ($sameType) {
                    $verdict = DuplicateCheckResult::VERDICT_DUPLICATE_SAME_TYPE;
                }
                // type différent + identité cohérente : légitime, rien à signaler.
            } elseif ($sameType && $sameNumber) {
                $verdict = DuplicateCheckResult::VERDICT_DOCUMENT_FACE_MISMATCH;
            }

            if ($verdict !== null) {
                $suspects[] = ['row' => $row, 'verdict' => $verdict, 'similarity' => $similarity];
            }
        }

        if ($suspects === []) {
            return DuplicateCheckResult::clean();
        }

        usort($suspects, fn (array $a, array $b) => $b['similarity'] <=> $a['similarity']);

        $worst = collect($suspects)
            ->sortByDesc(fn (array $s) => self::SEVERITY_ORDER[$s['verdict']])
            ->first()['verdict'];

        $suspects = array_slice($suspects, 0, self::MAX_MATCHES);
        $partnerNames = DeveloperApplication::query()
            ->whereIn('id', collect($suspects)->map(fn (array $s) => $s['row']->developer_application_id)->filter()->unique())
            ->pluck('name', 'id');

        $matches = array_map(fn (array $s) => [
            'face_embedding_id' => $s['row']->id,
            'verdict' => $s['verdict'],
            'similarity' => round($s['similarity'], 4),
            'partner_name' => $partnerNames[$s['row']->developer_application_id] ?? null,
            'full_name' => $s['row']->full_name,
            'date_of_birth' => $s['row']->date_of_birth?->toDateString(),
            'document_type' => $s['row']->document_type,
            'document_number' => $s['row']->document_number,
            'partner_verification_session_id' => $s['row']->partner_verification_session_id,
            'enrolled_at' => $s['row']->created_at?->toDateTimeString(),
        ], $suspects);

        return new DuplicateCheckResult($worst, $matches);
    }

    /**
     * Enregistre l'empreinte d'une session vérifiée. Ne double pas une ligne
     * existante pour la même pièce et le même visage (revérification).
     *
     * @param list<float> $embedding
     * @param array{document_number?: ?string, full_name?: ?string, date_of_birth?: ?string} $ocr
     */
    public function remember(array $embedding, PartnerVerificationSession $session, ?PartnerVerifiedIdentity $identity, array $ocr): void
    {
        $threshold = (float) config('faceverification.duplicate_similarity_threshold');
        $type = $this->normalizeType($session->document_type);
        $number = $this->normalizeNumber($ocr['document_number'] ?? null);

        if ($number !== null) {
            $existing = FaceEmbedding::query()
                ->where('document_number', $ocr['document_number'])
                ->get()
                ->first(fn (FaceEmbedding $row) => $this->normalizeType($row->document_type) === $type
                    && is_array($row->embedding)
                    && count($row->embedding) === count($embedding)
                    && $this->cosineSimilarity($embedding, $row->embedding) >= $threshold);

            if ($existing) {
                $existing->update(['partner_verified_identity_id' => $identity?->id ?? $existing->partner_verified_identity_id]);

                return;
            }
        }

        FaceEmbedding::query()->create([
            'embedding' => $embedding,
            'document_type' => $type ?? (string) $session->document_type,
            'document_number' => $ocr['document_number'] ?? null,
            'full_name' => $ocr['full_name'] ?? null,
            'date_of_birth' => $ocr['date_of_birth'] ?? null,
            'developer_application_id' => $session->developer_application_id,
            'partner_verification_session_id' => $session->id,
            'partner_verified_identity_id' => $identity?->id,
        ]);
    }

    private function identityConflicts(FaceEmbedding $row, ?string $fullName, ?string $dateOfBirth): bool
    {
        if ($row->date_of_birth && $dateOfBirth
            && $row->date_of_birth->toDateString() !== substr($dateOfBirth, 0, 10)) {
            return true;
        }

        return ! $this->namesCompatible($row->full_name, $fullName);
    }

    /**
     * Compatibles si tous les mots du nom le plus court se retrouvent dans
     * l'autre (ordre libre, accents ignorés, une faute d'OCR tolérée sur les
     * mots de 5 lettres et plus). Un nom manquant d'un côté = pas de signal.
     */
    private function namesCompatible(?string $a, ?string $b): bool
    {
        $tokensA = $this->nameTokens($a);
        $tokensB = $this->nameTokens($b);

        if ($tokensA === [] || $tokensB === []) {
            return true;
        }

        [$shorter, $longer] = count($tokensA) <= count($tokensB) ? [$tokensA, $tokensB] : [$tokensB, $tokensA];

        foreach ($shorter as $token) {
            $found = false;
            foreach ($longer as $candidate) {
                if ($token === $candidate
                    || (strlen($token) >= 5 && strlen($candidate) >= 5 && levenshtein($token, $candidate) <= 1)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private function nameTokens(?string $name): array
    {
        if (! $name) {
            return [];
        }

        $ascii = strtoupper(Str::ascii($name));

        return array_values(array_filter(preg_split('/[^A-Z]+/', $ascii) ?: []));
    }

    private function normalizeType(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        $type = strtolower(trim($type));

        return $type === 'cni' ? 'national_id' : $type;
    }

    private function normalizeNumber(?string $number): ?string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $number));

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $other = (float) $b[$i];
            $dot += $value * $other;
            $normA += $value * $value;
            $normB += $other * $other;
        }

        return ($normA > 0 && $normB > 0) ? $dot / (sqrt($normA) * sqrt($normB)) : 0.0;
    }
}
