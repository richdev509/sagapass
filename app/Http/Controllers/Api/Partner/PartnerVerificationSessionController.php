<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerVerificationSession;
use App\Support\PartnerAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * Flux "session partenaire QR" — un partenaire (ex. SwapLajan) crée une
 * session pour SON utilisateur final, qui n'a pas de compte SagaID. La page
 * publique de capture (Public\FaceCaptureController) s'en sert ensuite, sans
 * authentification, uniquement via le jeton. Voir le plan associé pour le
 * pourquoi de la séparation d'avec Document/PartnerVerifyController.
 */
class PartnerVerificationSessionController extends Controller
{
    public function __construct(private readonly PartnerAuthenticator $authenticator) {}

    /**
     * POST /api/partner/v1/verification-sessions
     */
    public function store(Request $request): JsonResponse
    {
        $app = $this->authenticator->authenticate($request);
        if (! $app) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_client',
                'message' => 'Authentification invalide. Vérifiez votre client_id et client_secret.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'partner_reference' => ['nullable', 'string', 'max:255'],
            'webhook_url' => ['required', 'url'],
            'document_type' => ['required', 'string', 'max:50'],
            'front_photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
            'back_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Idempotent sur partner_reference : évite les doublons si le
        // partenaire retente (ex. refresh de page côté SwapLajan).
        if (! empty($data['partner_reference'])) {
            $existing = PartnerVerificationSession::where('developer_application_id', $app->id)
                ->where('partner_reference', $data['partner_reference'])
                ->where('status', 'awaiting_capture')
                ->first();

            if ($existing && ! $existing->isExpired()) {
                return response()->json([
                    'success' => true,
                    'session_token' => $existing->token,
                    'capture_url' => route('capture.show', $existing->token),
                    'expires_at' => $existing->expires_at->toIso8601String(),
                ]);
            }
        }

        $token = Str::random(64);
        $folder = "partner-sessions/{$token}";

        $frontPath = $request->file('front_photo')->store($folder, 'private');
        $backPath = $request->hasFile('back_photo')
            ? $request->file('back_photo')->store($folder, 'private')
            : null;

        $session = PartnerVerificationSession::query()->create([
            'token' => $token,
            'developer_application_id' => $app->id,
            'partner_reference' => $data['partner_reference'] ?? null,
            'webhook_url' => $data['webhook_url'],
            'document_type' => $data['document_type'],
            'front_photo_path' => $frontPath,
            'back_photo_path' => $backPath,
            'status' => 'awaiting_capture',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addMinutes((int) config('faceverification.session_ttl_minutes')),
        ]);

        return response()->json([
            'success' => true,
            'session_token' => $session->token,
            'capture_url' => route('capture.show', $session->token),
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    /**
     * GET /api/partner/v1/verification-sessions/{token}/status
     */
    public function status(Request $request, string $token): JsonResponse
    {
        $app = $this->authenticator->authenticate($request);
        if (! $app) {
            return response()->json(['success' => false, 'error' => 'invalid_client'], 401);
        }

        $session = PartnerVerificationSession::where('token', $token)
            ->where('developer_application_id', $app->id)
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'error' => 'not_found'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $session->status,
            'face_match_score' => $session->face_match_score,
            'liveness_passed' => $session->liveness_passed,
            'ocr' => [
                'document_number' => $session->ocr_extracted_document_number,
                'full_name' => $session->ocr_extracted_full_name,
                'date_of_birth' => $session->ocr_extracted_date_of_birth?->format('Y-m-d'),
            ],
        ]);
    }
}
