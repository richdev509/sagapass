<?php

namespace App\Services;

use App\Models\BlacklistedIdentity;
use App\Models\BlacklistedIdentityDocument;

/**
 * Comparaison contre la liste de vigilance (voir BlacklistedIdentity) —
 * appelée par AnalyzePartnerSessionJob une fois l'OCR terminé.
 * Correspondance faciale volontairement hors scope pour l'instant, prévue
 * plus tard une fois ce premier niveau (nom + numéro de pièce) en place.
 */
class BlacklistScreeningService
{
    /**
     * @return array{hit: 'document_reject'|'name_alert'|null, identity: ?BlacklistedIdentity}
     */
    public function screen(?string $documentNumber, ?string $fullName): array
    {
        if ($documentNumber) {
            $document = BlacklistedIdentityDocument::query()
                ->where('document_number', $documentNumber)
                ->with('blacklistedIdentity')
                ->first();

            if ($document && $document->blacklistedIdentity) {
                return ['hit' => 'document_reject', 'identity' => $document->blacklistedIdentity];
            }
        }

        if ($fullName) {
            // Nom seul : signal faible (homonymes possibles), jamais un rejet
            // — juste une alerte pour le partenaire/l'admin, contrairement à
            // une correspondance de numéro de pièce.
            $identity = BlacklistedIdentity::query()
                ->get(['id', 'first_name', 'last_name'])
                ->first(fn (BlacklistedIdentity $candidate) => $candidate->matchesName($fullName));

            if ($identity) {
                return ['hit' => 'name_alert', 'identity' => $identity];
            }
        }

        return ['hit' => null, 'identity' => null];
    }
}
