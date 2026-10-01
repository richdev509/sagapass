<?php

namespace App\Services\FaceVerification;

use App\Exceptions\FaceVerificationUnavailableException;
use Illuminate\Support\Facades\Log;

/**
 * Refuse à la capture un selfie inexploitable (trop sombre, flou, visage
 * absent ou trop petit, plusieurs visages) et donne la raison au client, pour
 * qu'il refasse la photo tout de suite plutôt que d'échouer plus tard.
 *
 * Contrôle uniquement le frame "centre" : les frames gauche/droite sont des
 * têtes tournées, où le détecteur de visage échoue légitimement.
 *
 * Fail-open : si le script est indisponible ou plante, la capture passe (la
 * vérification complète fait ensuite son propre travail), ce filtre améliore
 * la qualité, il ne doit jamais bloquer tout le monde en cas de panne.
 */
class SelfieQualityGate
{
    /** Ordre de priorité du message affiché quand plusieurs motifs se cumulent. */
    private const MESSAGES = [
        'unreadable_image' => "La photo n'a pas pu être lue. Réessayez.",
        'no_face' => 'Aucun visage détecté. Placez votre visage bien au centre du cadre, face à la caméra.',
        'multiple_faces' => 'Plusieurs visages détectés. Vous devez être seul(e) devant la caméra.',
        'face_too_dark' => 'Photo trop sombre. Placez-vous face à une source de lumière (fenêtre ou lampe) et évitez les ombres sur le visage.',
        'face_low_contrast' => "Photo trop terne ou sombre. Placez-vous face à une lumière plus forte.",
        'face_blurry' => 'Photo floue. Tenez le téléphone stable et nettoyez l\'objectif, puis réessayez.',
        'face_too_small' => 'Visage trop petit. Rapprochez-vous de la caméra.',
    ];

    public function __construct(private readonly FaceVerificationScriptClient $client) {}

    public function isEnabled(): bool
    {
        return (bool) config('faceverification.quality_gate.enabled', false);
    }

    /**
     * @return string|null message à afficher au client si la photo est refusée, null si acceptée
     */
    public function rejectionMessage(string $centerSelfiePath): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $output = $this->client->checkPhotoQuality([$centerSelfiePath]);
        } catch (FaceVerificationUnavailableException $e) {
            Log::warning('SelfieQualityGate indisponible, capture acceptée : '.$e->getMessage());

            return null;
        }

        $image = $output['images'][0] ?? null;

        if ($image === null || isset($output['error'])) {
            Log::warning('SelfieQualityGate sans résultat exploitable, capture acceptée.', ['error' => $output['error'] ?? null]);

            return null;
        }

        if ($image['ok'] ?? false) {
            return null;
        }

        $reasons = $image['reasons'] ?? [];

        foreach (self::MESSAGES as $reason => $message) {
            if (in_array($reason, $reasons, true)) {
                return $message;
            }
        }

        return null;
    }
}
