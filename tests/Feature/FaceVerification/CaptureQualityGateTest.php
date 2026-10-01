<?php

namespace Tests\Feature\FaceVerification;

use App\Http\Controllers\Public\FaceCaptureController;
use App\Jobs\AnalyzePartnerSessionJob;
use App\Models\DeveloperApplication;
use App\Models\PartnerVerificationSession;
use App\Models\User;
use App\Services\FaceVerification\SelfieQualityGate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Un selfie refusé par le contrôle de qualité laisse la session intacte
 * (statut inchangé, rien de stocké, aucune analyse lancée) pour que le client
 * puisse refaire la photo ; un selfie accepté suit le flux normal.
 */
class CaptureQualityGateTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsFaceVerificationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFaceVerificationSchema();
    }

    private function makeSession(): PartnerVerificationSession
    {
        $owner = User::query()->create([
            'first_name' => 'Dev',
            'last_name' => 'Owner',
            'email' => 'dev-'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $application = DeveloperApplication::query()->create([
            'user_id' => $owner->id,
            'name' => 'Partenaire Test',
            'client_id' => (string) Str::uuid(),
            'client_secret' => bcrypt('secret'),
            'redirect_uris' => [],
        ]);

        return PartnerVerificationSession::query()->create([
            'token' => Str::random(40),
            'developer_application_id' => $application->id,
            'webhook_url' => 'https://partner.test/webhook',
            'document_type' => 'passport',
            'status' => 'awaiting_selfie_capture',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    /** @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse */
    private function submitSelfie(PartnerVerificationSession $session, ?string $rejection)
    {
        $gate = Mockery::mock(SelfieQualityGate::class);
        $gate->shouldReceive('rejectionMessage')->once()->andReturn($rejection);

        $request = Request::create('/capture/'.$session->token.'/selfie', 'POST', [], [], [
            'selfie_left' => UploadedFile::fake()->image('l.jpg'),
            'selfie_center' => UploadedFile::fake()->image('c.jpg'),
            'selfie_right' => UploadedFile::fake()->image('r.jpg'),
        ]);

        return app(FaceCaptureController::class)->submitSelfie($request, $session->token, $gate);
    }

    public function test_a_rejected_selfie_leaves_the_session_untouched_and_shows_the_reason(): void
    {
        Storage::fake('private');
        Bus::fake();
        $session = $this->makeSession();

        $response = $this->submitSelfie($session, 'Photo trop sombre.');

        $session->refresh();
        $this->assertSame('awaiting_selfie_capture', $session->status);
        $this->assertNull($session->selfie_center_path);
        Bus::assertNotDispatched(AnalyzePartnerSessionJob::class);
        // Redirection vers la page de capture, raison portée par le flash.
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('capture.show', $session->token), $response->getTargetUrl());
        $this->assertSame('Photo trop sombre.', $response->getSession()->get('capture_error'));
    }

    public function test_the_capture_page_shows_the_refusal_reason_in_a_banner(): void
    {
        $session = $this->makeSession();

        $view = app(FaceCaptureController::class)->show($session->token);

        $this->assertNull($view->getData()['captureError']);

        session()->flash('capture_error', 'Photo trop sombre.');
        $html = app(FaceCaptureController::class)->show($session->token)->render();

        $this->assertStringContainsString('capture-alert', $html);
        $this->assertStringContainsString('Photo refusée.', $html);
        $this->assertStringContainsString('Photo trop sombre.', $html);
    }

    public function test_an_accepted_selfie_follows_the_normal_flow(): void
    {
        Storage::fake('private');
        Bus::fake();
        $session = $this->makeSession();

        $view = $this->submitSelfie($session, null);

        $session->refresh();
        $this->assertSame('processing', $session->status);
        $this->assertNotNull($session->selfie_center_path);
        Bus::assertDispatched(AnalyzePartnerSessionJob::class);
        $this->assertSame('public.face-capture-complete', $view->name());
    }
}
