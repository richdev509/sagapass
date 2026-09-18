<?php

namespace Tests\Feature\FaceVerification;

use App\Exceptions\FaceVerificationUnavailableException;
use App\Services\FaceVerification\FaceVerificationScriptClient;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Mode diagnostic de la page de test admin (analyze.py --compare). Pas de base
 * de données : uniquement le contrat d'appel du script.
 */
class CompareFacesClientTest extends TestCase
{
    public function test_it_returns_the_compare_payload_and_passes_the_compare_flag(): void
    {
        Process::fake([
            '*analyze.py*' => Process::result(output: json_encode(['compare' => [
                'cosine_similarity' => 0.71,
                'liveness_passed' => true,
                'warnings' => [],
            ]])),
        ]);

        $result = app(FaceVerificationScriptClient::class)->compareFaces('/tmp/ref.jpg', '/tmp/probe.jpg');

        $this->assertSame(0.71, $result['cosine_similarity']);
        Process::assertRan(fn ($process) => in_array('--compare', $process->command, true)
            && in_array('/tmp/ref.jpg', $process->command, true)
            && in_array('/tmp/probe.jpg', $process->command, true));
    }

    public function test_it_throws_when_the_output_has_no_compare_key(): void
    {
        Process::fake([
            '*analyze.py*' => Process::result(output: json_encode(['ocr' => []])),
        ]);

        $this->expectException(FaceVerificationUnavailableException::class);

        app(FaceVerificationScriptClient::class)->compareFaces('/tmp/ref.jpg', '/tmp/probe.jpg');
    }
}
