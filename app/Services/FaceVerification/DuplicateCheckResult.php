<?php

namespace App\Services\FaceVerification;

/**
 * Verdict de FaceDuplicateService. Ne déclenche jamais de rejet automatique :
 * un verdict suspect envoie la session en revue manuelle d'un admin SagaPass
 * (jumeaux et fausses correspondances possibles).
 */
final readonly class DuplicateCheckResult
{
    public const VERDICT_NONE = 'none';

    /** Même visage, même type de pièce, numéro différent (ou inconnu). */
    public const VERDICT_DUPLICATE_SAME_TYPE = 'duplicate_same_type';

    /** Même visage mais nom ou date de naissance différents. */
    public const VERDICT_IDENTITY_CONFLICT = 'identity_conflict';

    /** Même numéro de pièce déjà enregistré, mais avec un visage différent. */
    public const VERDICT_DOCUMENT_FACE_MISMATCH = 'document_face_mismatch';

    /**
     * @param list<array<string, mixed>> $matches correspondances suspectes, les plus proches d'abord
     */
    public function __construct(
        public string $verdict,
        public array $matches = [],
    ) {}

    public static function clean(): self
    {
        return new self(self::VERDICT_NONE);
    }

    public function isSuspicious(): bool
    {
        return $this->verdict !== self::VERDICT_NONE;
    }

    /** 'strong' : signal d'usurpation net ; 'review' : à vérifier (renouvellement possible). */
    public function severity(): ?string
    {
        return match ($this->verdict) {
            self::VERDICT_IDENTITY_CONFLICT, self::VERDICT_DOCUMENT_FACE_MISMATCH => 'strong',
            self::VERDICT_DUPLICATE_SAME_TYPE => 'review',
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'verdict' => $this->verdict,
            'severity' => $this->severity(),
            'matches' => $this->matches,
        ];
    }
}
