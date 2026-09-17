<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerVerifiedIdentity;
use App\Support\PartnerAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Permet à un partenaire de revérifier plus tard, sans refaire toute la
 * capture, si un KYC ID durable (émis par AnalyzePartnerSessionJob à la fin
 * d'une PartnerVerificationSession) est toujours valide. Voir
 * PartnerVerifiedIdentity et NotifyPartnerKycExpiryWebhook pour l'alerte
 * proactive côté SagaID quand la validité se termine.
 */
class PartnerKycIdentityController extends Controller
{
    public function __construct(private readonly PartnerAuthenticator $authenticator) {}

    /**
     * GET /api/partner/v1/kyc-identities/{kycId}/status
     */
    public function status(Request $request, string $kycId): JsonResponse
    {
        $app = $this->authenticator->authenticate($request);
        if (! $app) {
            return response()->json(['success' => false, 'error' => 'invalid_client'], 401);
        }

        // Scope strict au partenaire authentifié : jamais de fuite d'existence
        // d'un KYC ID appartenant à un autre partenaire.
        $identity = PartnerVerifiedIdentity::where('kyc_id', $kycId)
            ->where('developer_application_id', $app->id)
            ->first();

        if (! $identity) {
            return response()->json(['success' => false, 'error' => 'not_found'], 404);
        }

        // Recalculé en direct, indépendant du passage du cron
        // kyc-identities:notify-expired.
        $status = $identity->status === 'valid' && now()->gt($identity->valid_until)
            ? 'expired'
            : $identity->status;

        return response()->json([
            'success' => true,
            'kyc_id' => $identity->kyc_id,
            'status' => $status,
            'verified_at' => $identity->verified_at->toIso8601String(),
            'valid_until' => $identity->valid_until->toIso8601String(),
            'document_type' => $identity->document_type,
            'document_number' => $identity->document_number,
            'full_name' => $identity->full_name,
            'date_of_birth' => $identity->date_of_birth?->format('Y-m-d'),
            'face_match_score' => $identity->face_match_score,
            'liveness_passed' => $identity->liveness_passed,
        ]);
    }
}
