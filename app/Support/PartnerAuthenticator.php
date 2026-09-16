<?php

namespace App\Support;

use App\Models\DeveloperApplication;
use Illuminate\Http\Request;

/**
 * Authentification partenaire partagée entre les contrôleurs Api\Partner\*
 * (extrait de PartnerVerifyController::authenticatePartner() pour être
 * réutilisé par PartnerVerificationSessionController sans dupliquer).
 *
 * Authorization: Basic base64(client_id:client_secret), avec repli sur
 * client_id/client_secret dans le corps de la requête (dev uniquement).
 */
class PartnerAuthenticator
{
    public function authenticate(Request $request): ?DeveloperApplication
    {
        $clientId = null;
        $clientSecret = null;

        $authorization = $request->header('Authorization', '');
        if (str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$clientId, $clientSecret] = explode(':', $decoded, 2);
            }
        }

        if (! $clientId) {
            $clientId = $request->input('client_id');
            $clientSecret = $request->input('client_secret');
        }

        if (! $clientId || ! $clientSecret) {
            return null;
        }

        $app = DeveloperApplication::where('client_id', $clientId)
            ->where('status', 'approved')
            ->first();

        if (! $app || ! $app->verifySecret($clientSecret)) {
            return null;
        }

        return $app;
    }
}
