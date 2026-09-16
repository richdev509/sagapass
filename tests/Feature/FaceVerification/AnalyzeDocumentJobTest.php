<?php

namespace Tests\Feature\FaceVerification;

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * DatabaseTransactions plutôt que RefreshDatabase : une migration pré-existante
 * (2025_11_24_200118_fix_account_level_enum_add_pending) utilise du SQL MySQL
 * brut (ALTER TABLE ... MODIFY COLUMN) incompatible avec SQLite — bug
 * pré-existant qui casse toute migration fraîche dans ce projet (confirmé sur
 * tests/Feature/ExampleTest.php, qui a RefreshDatabase commenté pour cette
 * même raison). Pas corrigé ici, hors périmètre. DatabaseTransactions utilise
 * la base MySQL locale déjà migrée et annule simplement les écritures de
 * chaque test.
 */
class AnalyzeDocumentJobTest extends TestCase
{
    use DatabaseTransactions;

    private function makeDocumentWithSelfie(): Document
    {
        Storage::fake('private');

        // Pas de User::factory() : sa définition par défaut utilise un champ
        // 'name' qui n'existe pas sur ce schéma (User utilise first_name/
        // last_name) — bug pré-existant de la factory, non lié à ce test.
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        Storage::disk('private')->put('documents/1/front.jpg', 'front-bytes');
        Storage::disk('private')->put('documents/1/back.jpg', 'back-bytes');
        Storage::disk('private')->put('documents/1/selfie.jpg', 'selfie-bytes');

        return Document::query()->create([
            'user_id' => $user->id,
            'document_type' => 'cni',
            'card_number' => 'ABC123DEF',
            'document_number' => '1234567890',
            'issue_date' => now()->subYear(),
            'expiry_date' => now()->addYears(5),
            'front_photo_path' => 'documents/1/front.jpg',
            'back_photo_path' => 'documents/1/back.jpg',
            'selfie_path' => 'documents/1/selfie.jpg',
            'verification_status' => 'pending',
        ]);
    }

    public function test_it_writes_automated_analysis_fields_without_touching_verification_status(): void
    {
        $document = $this->makeDocumentWithSelfie();

        Process::fake([
            'python3 analyze.py*' => Process::result(output: json_encode([
                'ocr' => [
                    'document_number' => '1234567890',
                    'full_name' => 'JEAN BAPTISTE',
                    'date_of_birth' => '1998-05-12',
                ],
                'liveness_passed' => true,
                'face_match_score' => 0.91,
                'warnings' => [],
            ])),
        ]);

        (new AnalyzeDocumentJob($document->id))->handle(app(\App\Services\FaceVerification\FaceVerificationService::class));

        $document->refresh();

        $this->assertSame('completed', $document->automated_check_status);
        $this->assertSame('1234567890', $document->ocr_extracted_document_number);
        $this->assertSame('JEAN BAPTISTE', $document->ocr_extracted_full_name);
        $this->assertSame('1998-05-12', $document->ocr_extracted_date_of_birth->format('Y-m-d'));
        $this->assertTrue($document->liveness_passed);
        $this->assertEqualsWithDelta(0.91, $document->face_match_score, 0.0001);
        // Jamais touché automatiquement — reste décidé par un admin.
        $this->assertSame('pending', $document->verification_status);
    }

    public function test_it_marks_failed_without_crashing_when_the_script_errors(): void
    {
        $document = $this->makeDocumentWithSelfie();

        Process::fake([
            'python3 analyze.py*' => Process::result(output: '', errorOutput: 'ModuleNotFoundError: deepface', exitCode: 1),
        ]);

        (new AnalyzeDocumentJob($document->id))->handle(app(\App\Services\FaceVerification\FaceVerificationService::class));

        $document->refresh();

        $this->assertSame('failed', $document->automated_check_status);
        $this->assertSame('pending', $document->verification_status);
        $this->assertNull($document->face_match_score);
    }

    public function test_it_does_nothing_when_the_document_has_no_selfie(): void
    {
        $document = $this->makeDocumentWithSelfie();
        $document->forceFill(['selfie_path' => null])->save();

        Process::fake();

        (new AnalyzeDocumentJob($document->id))->handle(app(\App\Services\FaceVerification\FaceVerificationService::class));

        $document->refresh();

        $this->assertSame('not_run', $document->automated_check_status);
        Process::assertNothingRan();
    }
}
