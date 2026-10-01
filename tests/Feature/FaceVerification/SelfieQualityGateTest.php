<?php

namespace Tests\Feature\FaceVerification;

use App\Services\FaceVerification\SelfieQualityGate;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Refus, à la capture, d'un selfie inexploitable avec la raison donnée au
 * client (flag désactivé par défaut, fail-open si le script est indisponible).
 * Pas de base de données : contrat d'appel du script uniquement.
 */
class SelfieQualityGateTest extends TestCase
{
    private function fakeQuality(bool $ok, array $reasons = []): void
    {
        Process::fake([
            '*check_quality.py*' => Process::result(output: json_encode(['images' => [
                ['path' => '/tmp/c.jpg', 'ok' => $ok, 'reasons' => $reasons],
            ]])),
        ]);
    }

    public function test_it_does_nothing_when_disabled(): void
    {
        config(['faceverification.quality_gate.enabled' => false]);
        Process::fake();

        $this->assertNull(app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg'));
        Process::assertNothingRan();
    }

    public function test_an_acceptable_photo_passes(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        $this->fakeQuality(true);

        $this->assertNull(app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg'));
        Process::assertRan(fn ($p) => in_array('check_quality.py', $p->command, true)
            && in_array('/tmp/c.jpg', $p->command, true));
    }

    public function test_a_dark_photo_is_refused_with_a_lighting_message(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        $this->fakeQuality(false, ['face_too_dark']);

        $message = app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg');

        $this->assertStringContainsString('sombre', $message);
    }

    public function test_the_most_important_reason_wins_when_several_apply(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        // no_face passe avant face_too_dark dans l'ordre de priorité.
        $this->fakeQuality(false, ['face_too_dark', 'no_face']);

        $message = app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg');

        $this->assertStringContainsString('Aucun visage', $message);
    }

    public function test_a_blurry_photo_gets_its_own_message(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        $this->fakeQuality(false, ['face_blurry']);

        $this->assertStringContainsString('floue', app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg'));
    }

    public function test_it_fails_open_when_the_script_crashes(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        Process::fake(['*check_quality.py*' => Process::result(output: '', errorOutput: 'boom', exitCode: 1)]);

        $this->assertNull(app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg'));
    }

    public function test_it_fails_open_when_the_script_reports_an_internal_error(): void
    {
        config(['faceverification.quality_gate.enabled' => true]);
        Process::fake(['*check_quality.py*' => Process::result(output: json_encode(['images' => [], 'error' => 'x']))]);

        $this->assertNull(app(SelfieQualityGate::class)->rejectionMessage('/tmp/c.jpg'));
    }
}
