<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    /**
     * Obtenir le profil de l'utilisateur authentifié
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = [];

        // Profile scope
        if ($token->can('profile')) {
            $data['first_name'] = $user->first_name;
            $data['last_name'] = $user->last_name;
            $data['verification_status'] = $user->verification_status;
            $data['verification_date'] = $user->verification_date?->toDateString();
            $data['is_verified'] = $user->isVerified();
        }

        // Email scope
        if ($token->can('email')) {
            $data['email'] = $user->email;
            $data['email_verified_at'] = $user->email_verified_at?->toDateString();
        }

        // Phone scope
        if ($token->can('phone')) {
            $data['phone'] = $user->phone;
        }

        // Address scope
        if ($token->can('address')) {
            $data['address'] = $user->address;
        }

        if (empty($data)) {
            return response()->json(['error' => 'No permissions granted'], 403);
        }

        return response()->json($data);
    }

    /**
     * Obtenir les informations sur les documents vérifiés
     */
    public function documents(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        // Vérifier le scope documents
        if (!$token || !$token->can('documents')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Vérifier que l'utilisateur est vérifié
        if (!$user->isVerified()) {
            return response()->json([
                'verified' => false,
                'message' => 'L\'utilisateur n\'a pas de documents vérifiés.'
            ]);
        }

        // Récupérer le dernier document vérifié
        $document = $user->documents()->where('status', 'approved')->latest()->first();

        if (!$document) {
            return response()->json([
                'verified' => false,
                'message' => 'Aucun document vérifié trouvé.'
            ]);
        }

        // Masquer le numéro de document (afficher seulement les 4 derniers chiffres)
        $maskedNumber = '****' . substr($document->document_number, -4);

        return response()->json([
            'verified' => true,
            'document_type' => $document->document_type,
            'document_number' => $maskedNumber,
            'issue_date' => $document->issue_date?->toDateString(),
            'expiry_date' => $document->expiry_date?->toDateString(),
            'verified_at' => $document->verified_at?->toDateTimeString(),
        ]);
    }
}
