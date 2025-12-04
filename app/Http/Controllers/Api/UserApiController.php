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
            $data['account_level'] = $user->account_level; // pending, basic, verified
            $data['verification_level'] = $user->verification_level; // none, email, video, document
            $data['verification_status'] = $user->verification_status;
            $data['video_status'] = $user->video_status; // none, pending, approved, rejected
            $data['video_verified_at'] = $user->video_verified_at?->toDateString();
            $data['verified_at'] = $user->verified_at?->toDateString();
            $data['is_verified'] = $user->account_level === 'verified'; // Compte Verified
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

        // Birthdate scope
        if ($token->can('birthdate')) {
            $data['date_of_birth'] = $user->date_of_birth?->toDateString();
        }

        // Photo scope
        if ($token->can('photo')) {
            $data['profile_photo_path'] = $user->profile_photo;
            $data['profile_photo_url'] = $user->profile_photo
                ? asset('storage/' . $user->profile_photo)
                : null;
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

        // Contexte du compte (toujours présent)
        $accountContext = [
            'level' => $user->account_level,
            'verification_level' => $user->verification_level,
            'can_access_documents' => $user->account_level === 'verified',
        ];

        // Si compte non-Verified, retourner infos d'upgrade
        if ($user->account_level !== 'verified') {
            return response()->json([
                'account' => $accountContext,
                'document' => null,
                'upgrade_required' => [
                    'next_level' => $user->account_level === 'basic' ? 'verified' : 'basic',
                    'requirements' => $this->getUpgradeRequirements($user),
                    'progress' => [
                        'video_submitted' => !empty($user->verification_video),
                        'video_approved' => $user->video_status === 'approved',
                        'document_verified' => false,
                    ],
                ],
            ]);
        }

        // Récupérer le dernier document vérifié
        $document = $user->documents()->where('verification_status', 'verified')->latest()->first();

        if (!$document) {
            return response()->json([
                'account' => $accountContext,
                'document' => null,
                'message' => 'Aucun document vérifié trouvé.',
            ]);
        }

        // Masquer les numéros sensibles (4 derniers caractères visibles)
        $maskedNiu = '****' . substr($document->document_number, -4);
        $maskedCardNumber = $document->card_number
            ? ('****' . substr($document->card_number, -4))
            : null;

        // Réponse structurée cohérente
        return response()->json([
            'account' => $accountContext,
            'document' => [
                'verified' => true,
                'type' => $document->document_type,
                'numbers' => [
                    'niu' => $maskedNiu,
                    'card_number' => $maskedCardNumber,
                ],
                'dates' => [
                    'issue' => $document->issue_date?->toDateString(),
                    'expiry' => $document->expiry_date?->toDateString(),
                    'verified_at' => $document->verified_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Déterminer les exigences pour passer au niveau supérieur
     */
    private function getUpgradeRequirements($user): array
    {
        if ($user->account_level === 'pending') {
            return ['Soumettre une vidéo de vérification faciale'];
        }

        if ($user->account_level === 'basic') {
            return ['Soumettre et faire vérifier un document d\'identité (CNI ou Passeport)'];
        }

        return [];
    }
}
