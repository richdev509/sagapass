<?php

namespace Tests\Feature\FaceVerification;

use App\Jobs\AnalyzePartnerSessionJob;
use App\Jobs\NotifyPartnerSessionWebhook;
use App\Models\DeveloperApplication;
use App\Models\FaceEmbedding;
use App\Models\PartnerVerificationSession;
use App\Models\User;
use App\Services\FaceVerification\PartnerSessionFinalizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Intégration du contrôle de doublons dans AnalyzePartnerSessionJob : sans
 * doublon la session se termine comme avant ; avec un doublon suspect elle part
 * en revue manuelle SANS notifier le partenaire ; les photos sont conservées.
 */
class AnalyzePartnerSessionDuplicateTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsFaceVerificationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFaceVerificationSchema();
    }

    private function face(int $axis): array
    {
        $vector = array_fill(0, 128, 0.0);
        $vector[$axis] = 1.0;

        return $vector;
    }

    private function makeSession(string $documentType = 'national_id'): PartnerVerificationSession
    {
        Storage::fake('private');

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

        $paths = [];
        foreach (['front', 'back', 'selfie_left', 'selfie_center', 'selfie_right'] as $name) {
            $paths[$name] = "partner-sessions/test/{$name}.jpg";
            Storage::disk('private')->put($paths[$name], "{$name}-bytes");
        }

        return PartnerVerificationSession::query()->create([
            'token' => Str::random(40),
            'developer_application_id' => $application->id,
            'webhook_url' => 'https://partner.test/webhook',
            'document_type' => $documentType,
            'front_photo_path' => $paths['front'],
            'back_photo_path' => $paths['back'],
            'selfie_left_path' => $paths['selfie_left'],
            'selfie_center_path' => $paths['selfie_center'],
            'selfie_right_path' => $paths['selfie_right'],
            'status' => 'processing',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    private function fakeEngine(array $embedding, string $number, string $name = 'JEAN BAPTISTE', string $dob = '1990-01-15', ?bool $liveness = true): void
    {
        Process::fake([
            '*analyze.py*' => Process::result(output: json_encode([
                'ocr' => ['document_number' => $number, 'full_name' => $name, 'date_of_birth' => $dob],
                'liveness_passed' => $liveness,
                'face_match_score' => 0.9,
                'face_embedding' => $embedding,
                'warnings' => [],
            ])),
        ]);
    }

    private function runJob(PartnerVerificationSession $session): PartnerVerificationSession
    {
        app()->call([new AnalyzePartnerSessionJob($session->id), 'handle']);

        return $session->refresh();
    }

    public function test_a_clean_session_completes_stores_the_embedding_and_keeps_the_photos(): void
    {
        Bus::fake([NotifyPartnerSessionWebhook::class]);
        $session = $this->makeSession();
        $this->fakeEngine($this->face(0), 'AAA111');

        $session = $this->runJob($session);

        $this->assertSame('completed', $session->status);
        $this->assertSame('none', $session->duplicate_check['verdict']);
        $this->assertSame(1, FaceEmbedding::query()->where('document_number', 'AAA111')->count());
        $this->assertTrue(Storage::disk('private')->exists($session->front_photo_path));
        $this->assertTrue(Storage::disk('private')->exists($session->selfie_center_path));
        Bus::assertDispatched(NotifyPartnerSessionWebhook::class);
        // L'empreinte biométrique ne doit jamais atterrir en clair dans analysis_raw.
        $this->assertArrayNotHasKey('face_embedding', $session->analysis_raw);
    }

    public function test_a_second_national_id_for_the_same_face_goes_to_manual_review_without_notifying_the_partner(): void
    {
        Bus::fake([NotifyPartnerSessionWebhook::class]);
        FaceEmbedding::query()->create([
            'embedding' => $this->face(0),
            'document_type' => 'national_id',
            'document_number' => 'AAA111',
            'full_name' => 'JEAN BAPTISTE',
            'date_of_birth' => '1990-01-15',
        ]);
        $session = $this->makeSession('national_id');
        $this->fakeEngine($this->face(0), 'BBB222');

        $session = $this->runJob($session);

        $this->assertSame('awaiting_manual_review', $session->status);
        $this->assertTrue($session->isDuplicateReview());
        $this->assertSame('duplicate_same_type', $session->duplicate_check['verdict']);
        $this->assertNotNull($session->pending_face_embedding);
        // Pas encore enregistrée comme visage vérifié, pas de webhook, photos gardées.
        $this->assertSame(0, FaceEmbedding::query()->where('document_number', 'BBB222')->count());
        Bus::assertNotDispatched(NotifyPartnerSessionWebhook::class);
        $this->assertTrue(Storage::disk('private')->exists($session->front_photo_path));
    }

    public function test_approving_a_reviewed_duplicate_completes_the_session_and_enrolls_the_face(): void
    {
        Bus::fake([NotifyPartnerSessionWebhook::class]);
        FaceEmbedding::query()->create([
            'embedding' => $this->face(0),
            'document_type' => 'national_id',
            'document_number' => 'AAA111',
            'full_name' => 'JEAN BAPTISTE',
            'date_of_birth' => '1990-01-15',
        ]);
        $session = $this->runJobAfterFake('BBB222');

        app(PartnerSessionFinalizer::class)->complete(
            $session,
            [
                'document_number' => $session->ocr_extracted_document_number,
                'full_name' => $session->ocr_extracted_full_name,
                'date_of_birth' => $session->ocr_extracted_date_of_birth?->toDateString(),
            ],
            $session->face_match_score,
            $session->liveness_passed,
            $session->pending_face_embedding,
            enrollFace: true,
        );

        $session->refresh();
        $this->assertSame('completed', $session->status);
        $this->assertNull($session->pending_face_embedding);
        $this->assertSame(1, FaceEmbedding::query()->where('document_number', 'BBB222')->count());
        Bus::assertDispatched(NotifyPartnerSessionWebhook::class);
    }

    public function test_a_failed_liveness_never_enrolls_the_face_in_the_registry(): void
    {
        Bus::fake([NotifyPartnerSessionWebhook::class]);
        $session = $this->makeSession();
        $this->fakeEngine($this->face(3), 'CCC333', liveness: false);

        $session = $this->runJob($session);

        $this->assertSame('completed', $session->status);
        $this->assertSame(0, FaceEmbedding::query()->where('document_number', 'CCC333')->count());
    }

    public function test_a_missing_embedding_does_not_block_the_session(): void
    {
        Bus::fake([NotifyPartnerSessionWebhook::class]);
        $session = $this->makeSession();
        Process::fake([
            '*analyze.py*' => Process::result(output: json_encode([
                'ocr' => ['document_number' => 'DDD444', 'full_name' => 'JEAN BAPTISTE', 'date_of_birth' => '1990-01-15'],
                'liveness_passed' => true,
                'face_match_score' => 0.9,
                'face_embedding' => null,
                'warnings' => ['face_embedding_error: boom'],
            ])),
        ]);

        $session = $this->runJob($session);

        $this->assertSame('completed', $session->status);
        $this->assertSame('unchecked', $session->duplicate_check['verdict']);
    }

    private function runJobAfterFake(string $number): PartnerVerificationSession
    {
        $session = $this->makeSession('national_id');
        $this->fakeEngine($this->face(0), $number);

        return $this->runJob($session);
    }
}
