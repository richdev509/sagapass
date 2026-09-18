<?php

namespace App\Services\FaceVerification;

use App\Exceptions\FaceVerificationUnavailableException;
use Illuminate\Support\Facades\Process;

/**
 * Invoque le script Python dédié (scripts/face-verification/analyze.py) via la
 * façade Process de Laravel — même principe que PuppeteerNiuLookupClient
 * (Node/Puppeteer) et TronSignatureVerifier (Node/TronWeb) côté SwapLajan :
 * interceptable en test via Process::fake(), une exception dédiée par échec
 * technique, jamais de logique métier ici (juste appeler et décoder).
 */
class FaceVerificationScriptClient
{
    public function __construct(
        private readonly string $scriptPath,
        private readonly string $modelCacheDir,
        private readonly int $timeoutSeconds,
        private readonly string $pythonBinary = 'python3',
        private readonly string $anthropicApiKey = '',
        private readonly string $anthropicModel = 'claude-sonnet-5',
    ) {}

    /**
     * Mode single-frame (flux Document/AnalyzeDocumentJob).
     *
     * @return array{ocr: array{document_number: ?string, full_name: ?string, date_of_birth: ?string}, liveness_passed: ?bool, face_match_score: ?float, warnings: list<string>}
     */
    public function analyze(string $documentType, string $frontPhotoPath, ?string $backPhotoPath, string $selfiePath): array
    {
        return $this->runScript([
            $documentType,
            $frontPhotoPath,
            $backPhotoPath ?? '',
            $selfiePath,
        ]);
    }

    /**
     * Mode vivacité active 3-frames (flux session partenaire QR — voir
     * PartnerVerificationSession/AnalyzePartnerSessionJob). Le frame "centre"
     * sert de référence pour la correspondance visage, comme dans analyze().
     *
     * @return array{ocr: array{document_number: ?string, full_name: ?string, date_of_birth: ?string}, liveness_passed: ?bool, face_match_score: ?float, warnings: list<string>}
     */
    public function analyzeWithActiveLiveness(
        string $documentType,
        string $frontPhotoPath,
        ?string $backPhotoPath,
        string $selfieCenterPath,
        string $selfieLeftPath,
        string $selfieRightPath,
    ): array {
        return $this->runScript([
            $documentType,
            $frontPhotoPath,
            $backPhotoPath ?? '',
            $selfieCenterPath,
            $selfieLeftPath,
            $selfieRightPath,
        ]);
    }

    /**
     * Mode diagnostic (page de test admin) : compare deux photos et détaille
     * similarité, verdict DeepFace et vivacité passive de la seconde. Ne stocke
     * rien, ne touche à aucune session.
     *
     * @return array<string, mixed>
     */
    public function compareFaces(string $referencePhotoPath, string $probePhotoPath): array
    {
        return $this->runScript(['--compare', $referencePhotoPath, $probePhotoPath], 'compare')['compare'];
    }

    /**
     * @param list<string> $arguments
     * @param string $requiredKey clé de premier niveau que la sortie JSON doit contenir
     * @return array<string, mixed>
     */
    private function runScript(array $arguments, string $requiredKey = 'ocr'): array
    {
        $result = Process::path($this->scriptPath)
            ->timeout($this->timeoutSeconds)
            ->env([
                'DEEPFACE_HOME' => $this->modelCacheDir,
                // matplotlib (dépendance de mediapipe) essaie par défaut
                // d'écrire son cache dans le home de l'utilisateur système
                // (souvent non accessible en écriture pour www-data) —
                // pointé vers le même dossier cache que DeepFace plutôt que
                // de laisser matplotlib échouer puis retomber sur /tmp à
                // chaque appel.
                'MPLCONFIGDIR' => $this->modelCacheDir,
                // Extraction OCR via l'API de vision Claude, en test face à
                // EasyOCR — vide = comportement inchangé (EasyOCR seul), voir
                // extract_ocr_fields() dans analyze.py.
                'ANTHROPIC_API_KEY' => $this->anthropicApiKey,
                'ANTHROPIC_OCR_MODEL' => $this->anthropicModel,
            ])
            ->run([
                $this->pythonBinary,
                'analyze.py',
                ...$arguments,
            ]);

        if (! $result->successful()) {
            throw FaceVerificationUnavailableException::fromProcessFailure($result->errorOutput());
        }

        $output = trim($result->output());

        if ($output === '') {
            throw FaceVerificationUnavailableException::fromProcessFailure('Le script a réussi mais n\'a renvoyé aucun contenu.');
        }

        $decoded = json_decode($output, true);

        if (! is_array($decoded) || ! array_key_exists($requiredKey, $decoded)) {
            throw FaceVerificationUnavailableException::fromUnparsableOutput($output);
        }

        return $decoded;
    }
}
