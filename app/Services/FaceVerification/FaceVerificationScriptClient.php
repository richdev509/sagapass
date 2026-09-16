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
    ) {}

    /**
     * @return array{ocr: array{document_number: ?string, full_name: ?string, date_of_birth: ?string}, liveness_passed: ?bool, face_match_score: ?float, warnings: list<string>}
     */
    public function analyze(string $documentType, string $frontPhotoPath, ?string $backPhotoPath, string $selfiePath): array
    {
        $result = Process::path($this->scriptPath)
            ->timeout($this->timeoutSeconds)
            ->env([
                'DEEPFACE_HOME' => $this->modelCacheDir,
            ])
            ->run([
                $this->pythonBinary,
                'analyze.py',
                $documentType,
                $frontPhotoPath,
                $backPhotoPath ?? '',
                $selfiePath,
            ]);

        if (! $result->successful()) {
            throw FaceVerificationUnavailableException::fromProcessFailure($result->errorOutput());
        }

        $output = trim($result->output());

        if ($output === '') {
            throw FaceVerificationUnavailableException::fromProcessFailure('Le script a réussi mais n\'a renvoyé aucun contenu.');
        }

        $decoded = json_decode($output, true);

        if (! is_array($decoded) || ! array_key_exists('ocr', $decoded)) {
            throw FaceVerificationUnavailableException::fromUnparsableOutput($output);
        }

        return $decoded;
    }
}
