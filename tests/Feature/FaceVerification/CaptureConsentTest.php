<?php

namespace Tests\Feature\FaceVerification;

use App\Http\Controllers\Public\FaceCaptureController;
use App\Models\DeveloperApplication;
use App\Models\PartnerVerificationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Le consentement (conditions d'utilisation + données biométriques) est exigé
 * côté serveur à l'envoi de la pièce, pas seulement par le bouton désactivé de
 * la page — et sa preuve (date, version) est enregistrée avec la session.
 */
class CaptureConsentTest extends TestCase
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
            'status' => 'awaiting_id_capture',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * Appel direct du contrôleur : les tests HTTP complets traversent des
     * middlewares globaux qui exigent d'autres tables (system_settings…) que ce
     * schéma minimal ne crée pas.
     */
    private function submitId(PartnerVerificationSession $session, array $input): void
    {
        $request = Request::create('/capture/'.$session->token.'/id', 'POST', $input, [], [
            'id_front' => UploadedFile::fake()->image('front.jpg'),
        ]);

        app(FaceCaptureController::class)->submitId($request, $session->token);
    }

    public function test_submitting_the_id_without_consent_is_refused_and_nothing_is_stored(): void
    {
        Storage::fake('private');
        $session = $this->makeSession();

        $this->submitId($session, []);

        $session->refresh();
        $this->assertSame('awaiting_id_capture', $session->status);
        $this->assertNull($session->front_photo_path);
        $this->assertNull($session->consent_accepted_at);
    }

    public function test_submitting_the_id_with_consent_records_the_proof_of_consent(): void
    {
        Storage::fake('private');
        $session = $this->makeSession();

        $this->submitId($session, ['consent' => '1']);

        $session->refresh();
        $this->assertSame('awaiting_selfie_capture', $session->status);
        $this->assertNotNull($session->consent_accepted_at);
        $this->assertSame(config('faceverification.consent_terms_version'), $session->consent_terms_version);
        $this->assertNotNull($session->front_photo_path);
    }
}
