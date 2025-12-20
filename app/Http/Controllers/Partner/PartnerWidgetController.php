<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PartnerCitizenService;
use App\Models\OAuthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class PartnerWidgetController extends Controller
{
    protected $citizenService;

    public function __construct(PartnerCitizenService $citizenService)
    {
        $this->citizenService = $citizenService;
    }

    /**
     * Afficher le widget de vérification (popup)
     *
     * GET /partner/widget/verify?partner_id=xxx&token=xxx&email=xxx&...
     */
    public function showWidget(Request $request)
    {
        // Valider les paramètres
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|string',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'callback_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return view('partner.widget.error', [
                'error' => 'Paramètres invalides : ' . $validator->errors()->first()
            ]);
        }

        // Vérifier que le partner_id existe
        $partner = OAuthClient::where('client_id', $request->partner_id)->first();

        if (!$partner) {
            return view('partner.widget.error', [
                'error' => 'Partenaire non reconnu'
            ]);
        }

        // Vérifier que le partenaire a le scope partner:create-citizen
        if (!in_array('partner:create-citizen', $partner->allowed_scopes ?? [])) {
            return view('partner.widget.error', [
                'error' => 'Ce partenaire n\'est pas autorisé à créer des citoyens'
            ]);
        }

        // Afficher le widget de capture
        return view('partner.widget.capture', [
            'partner' => $partner,
            'email' => $request->email,
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'callbackUrl' => $request->callback_url ?? '',
            'token' => $request->token ?? '',
        ]);
    }

    /**
     * Traiter la soumission du widget (photo + vidéo + document)
     *
     * POST /partner/widget/submit
     */
    public function submitWidget(Request $request)
    {
        try {
            // Validation complète selon les règles SAGAPASS
            $validator = Validator::make($request->all(), [
                'partner_id' => 'required|string',
                'email' => 'required|email',
                'first_name' => 'required|string|min:2|max:50',
                'last_name' => 'required|string|min:2|max:50',
                'date_of_birth' => 'required|date|before:today|before:-18 years',
                'phone' => 'nullable|string',
                'address' => 'nullable|string',
                'photo' => 'required|string', // Base64 - Photo de profil
                'video' => 'required|string', // Base64 - Vidéo de vérification

                // DOCUMENT D'IDENTITÉ (OBLIGATOIRE)
                'document.document_type' => 'required|in:CNI',
                'document.document_number' => 'required|string|size:10|regex:/^[0-9]{10}$/', // NINU: 10 chiffres
                'document.card_number' => 'required_if:document.document_type,CNI|nullable|string|size:9|regex:/^[A-Za-z0-9]{9}$/', // Numéro carte: 9 caractères
                'document.issue_date' => 'required|date|before:today',
                'document.expiry_date' => 'required|date|after:today',
                'document.front_photo' => 'required|string', // Base64 - Photo recto
                'document.back_photo' => 'required_if:document.document_type,CNI|nullable|string', // Base64 - Photo verso (obligatoire pour CNI)

                'callback_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            // Vérifier le partenaire
            $partner = OAuthClient::where('client_id', $request->partner_id)->first();

            if (!$partner || !in_array('partner:create-citizen', $partner->allowed_scopes ?? [])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Partenaire non autorisé'
                ], 403);
            }

            // Créer le citoyen via le service avec TOUTES les données
            $result = $this->citizenService->createCitizen([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'profile_picture' => $request->photo,
                'verification_video' => $request->video,
                'document' => [
                    'document_type' => $request->input('document.document_type'),
                    'document_number' => $request->input('document.document_number'),
                    'card_number' => $request->input('document.card_number'),
                    'issue_date' => $request->input('document.issue_date'),
                    'expiry_date' => $request->input('document.expiry_date'),
                    'front_photo' => $request->input('document.front_photo'),
                    'back_photo' => $request->input('document.back_photo'),
                ],
            ], $partner->client_id, null, $request->ip());

            // Log succès
            Log::info('Widget citizen created', [
                'partner_id' => $partner->client_id,
                'citizen_id' => $result['citizen_id'],
                'email' => $result['email'],
            ]);

            return response()->json([
                'success' => true,
                'citizen_id' => $result['citizen_id'],
                'message' => 'Vérification réussie ! Un email a été envoyé au citoyen.',
                'callback_url' => $request->callback_url,
            ]);

        } catch (Exception $e) {
            Log::error('Widget submission failed', [
                'error' => $e->getMessage(),
                'partner_id' => $request->partner_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Générer un token de widget pour le partenaire
     * Cette méthode peut être appelée par le partenaire côté serveur
     *
     * POST /api/partner/v1/widget/generate-token
     */
    public function generateWidgetToken(Request $request)
    {
        // Le partenaire est déjà authentifié via middleware partner.auth
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'callback_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Générer un token sécurisé avec les données
        $payload = [
            'partner_id' => $request->partner_id,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'callback_url' => $request->callback_url,
            'timestamp' => time(),
        ];

        $token = base64_encode(json_encode($payload));

        // URL du widget
        $widgetUrl = route('partner.widget.show', [
            'partner_id' => $request->partner_id,
            'token' => $token,
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'callback_url' => $request->callback_url,
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'widget_url' => $widgetUrl,
            'expires_in' => 3600, // 1 heure
        ]);
    }
}
