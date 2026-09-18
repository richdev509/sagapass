<?php

namespace App\Services\FaceVerification;

use App\Jobs\NotifyPartnerSessionWebhook;
use App\Models\PartnerVerificationSession;
use App\Models\PartnerVerifiedIdentity;
use App\Services\BlacklistScreeningService;

/**
 * Clôture positive d'une PartnerVerificationSession : criblage liste de
 * vigilance, émission/renouvellement du KYC ID durable, enregistrement de
 * l'empreinte faciale (registre global de doublons), webhook au partenaire.
 * Partagé entre AnalyzePartnerSessionJob (cas sans doublon suspect) et
 * l'approbation d'un admin (cas revu manuellement).
 */
class PartnerSessionFinalizer
{
    public function __construct(
        private readonly BlacklistScreeningService $blacklist,
        private readonly FaceDuplicateService $duplicates,
    ) {}

    /**
     * @param array{document_number: ?string, full_name: ?string, date_of_birth: ?string} $ocr
     * @param ?list<float> $faceEmbedding
     * @param bool $enrollFace enregistrer l'empreinte dans le registre de doublons
     * @param array<string, mixed> $attributes attributs de session supplémentaires (analyse brute, relecteur…)
     */
    public function complete(
        PartnerVerificationSession $session,
        array $ocr,
        ?float $faceMatchScore,
        ?bool $livenessPassed,
        ?array $faceEmbedding,
        bool $enrollFace,
        array $attributes = [],
    ): void {
        $screening = $this->blacklist->screen($ocr['document_number'] ?? null, $ocr['full_name'] ?? null);
        $identity = $this->upsertVerifiedIdentity($session, $ocr, $faceMatchScore, $livenessPassed);

        if ($enrollFace && $faceEmbedding) {
            $this->duplicates->remember($faceEmbedding, $session, $identity, $ocr);
        }

        $session->forceFill([
            ...$attributes,
            'status' => 'completed',
            'ocr_extracted_document_number' => $ocr['document_number'] ?? null,
            'ocr_extracted_full_name' => $ocr['full_name'] ?? null,
            'ocr_extracted_date_of_birth' => $ocr['date_of_birth'] ?? null,
            'face_match_score' => $faceMatchScore,
            'liveness_passed' => $livenessPassed,
            'partner_verified_identity_id' => $identity?->id,
            'blacklist_hit' => $screening['hit'],
            'blacklist_matched_identity_id' => $screening['identity']?->id,
            'pending_face_embedding' => null,
            'completed_at' => now(),
        ])->save();

        NotifyPartnerSessionWebhook::dispatch($session, 'verification.completed');
    }

    /**
     * Émet ou renouvelle le KYC ID durable de cette personne — dès qu'une
     * session se termine avec une analyse exécutée sans erreur, quel que soit
     * le résultat (score de correspondance / vivacité indicatifs, laissés au
     * partenaire). Une seule ligne par (partenaire, numéro de document) :
     * une nouvelle session pour le même document prolonge sa validité au lieu
     * d'en créer une autre. Aucun KYC ID n'est émis si l'OCR n'a pas réussi à
     * extraire de numéro de document — limite connue de l'OCR, pas un bug.
     *
     * @param array{document_number: ?string, full_name: ?string, date_of_birth: ?string} $ocr
     */
    private function upsertVerifiedIdentity(PartnerVerificationSession $session, array $ocr, ?float $faceMatchScore, ?bool $livenessPassed): ?PartnerVerifiedIdentity
    {
        $documentNumber = $ocr['document_number'] ?? null;

        if (! $documentNumber) {
            return null;
        }

        return PartnerVerifiedIdentity::updateOrCreate(
            [
                'developer_application_id' => $session->developer_application_id,
                'document_number' => $documentNumber,
            ],
            [
                'document_type' => $session->document_type,
                'full_name' => $ocr['full_name'] ?? null,
                'date_of_birth' => $ocr['date_of_birth'] ?? null,
                'face_match_score' => $faceMatchScore,
                'liveness_passed' => $livenessPassed,
                'status' => 'valid',
                'verified_at' => now(),
                'valid_until' => now()->addDays((int) config('faceverification.kyc_validity_days', 90)),
                'webhook_url' => $session->webhook_url,
                'last_partner_verification_session_id' => $session->id,
                'expired_notified_at' => null,
            ],
        );
    }
}
