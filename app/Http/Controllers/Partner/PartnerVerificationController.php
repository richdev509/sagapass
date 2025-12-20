<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PartnerCitizenService;
use App\Models\PartnerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class PartnerVerificationController extends Controller
{
    protected $citizenService;

    public function __construct(PartnerCitizenService $citizenService)
    {
        $this->citizenService = $citizenService;
    }

    /**
     * Créer un nouveau citoyen SAGAPASS
     *
     * POST /api/partner/v1/create-citizen
     */
    public function createCitizen(Request $request)
    {
        $partnerId = $request->partner_id;
        $partnerReference = $request->input('partner_reference');

        // Créer l'enregistrement de tracking
        $tracking = PartnerVerification::create([
            'partner_id' => $partnerId,
            'partner_reference' => $partnerReference,
            'status' => 'pending',
            'request_data' => $request->except(['profile_picture', 'verification_video', 'document']), // Pas stocker les base64 volumineux
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // VALIDATION DES DONNÉES
            $validator = Validator::make($request->all(), [
                // Informations personnelles
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'required|date|before:today|before:-18 years',
                'address' => 'nullable|string',

                // Photo et vidéo (base64)
                'profile_picture' => 'required|string',
                'verification_video' => 'required|string',

                // Document (optionnel)
                'document.document_type' => 'nullable|in:cni,passport',
                'document.document_number' => 'nullable|string|max:255',
                'document.card_number' => 'nullable|string|max:255',
                'document.issue_date' => 'nullable|date',
                'document.expiry_date' => 'nullable|date|after:today',
                'document.front_photo' => 'nullable|string',
                'document.back_photo' => 'nullable|string',

                // Référence partenaire
                'partner_reference' => 'nullable|string|max:255',
            ], [
                'date_of_birth.before' => 'Le citoyen doit avoir au moins 18 ans',
                'email.unique' => 'Un compte avec cet email existe déjà',
                'document.expiry_date.after' => 'Le document est expiré',
            ]);

            if ($validator->fails()) {
                $tracking->update([
                    'status' => 'failed',
                    'error_message' => $validator->errors()->first(),
                    'response_data' => ['errors' => $validator->errors()],
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Validation échouée',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // CRÉER LE CITOYEN
            $result = $this->citizenService->createCitizen(
                $request->all(),
                $partnerId,
                $partnerReference,
                $request->ip()
            );

            // Mettre à jour le tracking
            $tracking->update([
                'user_id' => $result['citizen_id'],
                'status' => 'completed',
                'response_data' => $result,
            ]);

            // Log succès
            Log::info('Partner citizen created', [
                'partner_id' => $partnerId,
                'citizen_id' => $result['citizen_id'],
                'email' => $result['email'],
            ]);

            return response()->json($result, 201);

        } catch (Exception $e) {
            // Mettre à jour le tracking avec l'erreur
            $tracking->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_data' => ['error' => $e->getMessage()],
            ]);

            // Log erreur
            Log::error('Partner citizen creation failed', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Vérifier le statut de vérification d'un citoyen
     *
     * GET /api/partner/v1/check-verification?email=xxx
     * GET /api/partner/v1/check-verification?reference=xxx
     */
    public function checkVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:reference|email',
            'reference' => 'required_without:email|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Email ou reference requis',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Recherche par email
            if ($request->has('email')) {
                $result = $this->citizenService->checkVerificationStatus($request->email);
                return response()->json($result);
            }

            // Recherche par référence partenaire
            if ($request->has('reference')) {
                $tracking = PartnerVerification::where('partner_reference', $request->reference)
                    ->where('partner_id', $request->partner_id)
                    ->latest()
                    ->first();

                if (!$tracking || !$tracking->user_id) {
                    return response()->json([
                        'found' => false,
                        'message' => 'Aucun citoyen trouvé avec cette référence',
                    ]);
                }

                $result = $this->citizenService->checkVerificationStatus($tracking->user->email);
                return response()->json($result);
            }

        } catch (Exception $e) {
            Log::error('Partner check verification failed', [
                'partner_id' => $request->partner_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtenir les détails d'un citoyen par référence partenaire
     *
     * GET /api/partner/v1/citizen/{reference}
     */
    public function getCitizen(Request $request, string $reference)
    {
        try {
            $tracking = PartnerVerification::where('partner_reference', $reference)
                ->where('partner_id', $request->partner_id)
                ->with('user')
                ->latest()
                ->first();

            if (!$tracking) {
                return response()->json([
                    'success' => false,
                    'error' => 'Citoyen non trouvé',
                ], 404);
            }

            if (!$tracking->user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Citoyen en cours de création ou création échouée',
                    'status' => $tracking->status,
                ], 404);
            }

            $result = $this->citizenService->checkVerificationStatus($tracking->user->email);

            return response()->json([
                'success' => true,
                'citizen' => $result,
                'partner_reference' => $reference,
                'created_at' => $tracking->created_at,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
