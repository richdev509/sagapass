<?php

namespace Tests\Feature\FaceVerification;

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\FaceEmbedding;
use App\Models\User;
use App\Services\FaceVerification\FaceDuplicateService;
use App\Services\FaceVerification\FaceVerificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contrôle de doublons dans le flux Document (comptes SagaPass) : indicatif —
 * il ne touche jamais verification_status, l'admin décide. L'empreinte n'entre
 * dans le registre qu'à l'approbation (rememberDocument).
 */
class AnalyzeDocumentDuplicateTest extends TestCase
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

    private function makeDocument(string $type = 'cni', string $number = 'NEW-123'): Document
    {
        Storage::fake('private');

        $user = User::query()->create([
            'first_name' => 'Jean',
            'last_name' => 'Baptiste',
            'date_of_birth' => '1990-01-15',
            'email' => 'u-'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        foreach (['front', 'back', 'selfie'] as $name) {
            Storage::disk('private')->put("documents/t/{$name}.jpg", "{$name}-bytes");
        }

        return Document::query()->create([
            'user_id' => $user->id,
            'document_type' => $type,
            'document_number' => $number,
            'front_photo_path' => 'documents/t/front.jpg',
            'back_photo_path' => 'documents/t/back.jpg',
            'selfie_path' => 'documents/t/selfie.jpg',
            'verification_status' => 'pending',
        ]);
    }

    private function fakeEngine(?array $embedding, ?string $number = 'NEW-123'): void
    {
        Process::fake([
            '*analyze.py*' => Process::result(output: json_encode([
                'ocr' => ['document_number' => $number, 'full_name' => 'JEAN BAPTISTE', 'date_of_birth' => '1990-01-15'],
                'liveness_passed' => true,
                'face_match_score' => 0.9,
                'face_embedding' => $embedding,
                'warnings' => [],
            ])),
        ]);
    }

    private function runJob(Document $document): Document
    {
        (new AnalyzeDocumentJob($document->id))->handle(
            app(FaceVerificationService::class),
            app(FaceDuplicateService::class),
        );

        return $document->refresh();
    }

    public function test_a_clean_document_stores_a_pending_embedding_and_stays_pending(): void
    {
        $document = $this->makeDocument();
        $this->fakeEngine($this->face(0));

        $document = $this->runJob($document);

        $this->assertSame('none', $document->duplicate_check['verdict']);
        $this->assertNotNull($document->pending_face_embedding);
        $this->assertSame('pending', $document->verification_status);
        // Rien dans le registre tant qu'un admin n'a pas approuvé.
        $this->assertSame(0, FaceEmbedding::query()->count());
    }

    public function test_a_second_cni_for_a_known_face_is_flagged_but_never_blocked(): void
    {
        FaceEmbedding::query()->create([
            'embedding' => $this->face(0),
            'document_type' => 'national_id',
            'document_number' => 'OLD-999',
            'full_name' => 'JEAN BAPTISTE',
            'date_of_birth' => '1990-01-15',
        ]);
        $document = $this->makeDocument('cni', 'NEW-123');
        $this->fakeEngine($this->face(0));

        $document = $this->runJob($document);

        // 'cni' (flux Document) et 'national_id' (flux partenaire) sont le même type.
        $this->assertSame('duplicate_same_type', $document->duplicate_check['verdict']);
        $this->assertSame('OLD-999', $document->duplicate_check['matches'][0]['document_number']);
        $this->assertSame('pending', $document->verification_status);
    }

    public function test_a_passport_for_a_face_known_by_a_cni_is_clean(): void
    {
        FaceEmbedding::query()->create([
            'embedding' => $this->face(0),
            'document_type' => 'national_id',
            'document_number' => 'OLD-999',
            'full_name' => 'JEAN BAPTISTE',
            'date_of_birth' => '1990-01-15',
        ]);
        $document = $this->makeDocument('passport', 'PP-777');
        $this->fakeEngine($this->face(0), 'PP-777');

        $this->assertSame('none', $this->runJob($document)->duplicate_check['verdict']);
    }

    public function test_a_missing_embedding_is_marked_unchecked_without_failing_the_analysis(): void
    {
        $document = $this->makeDocument();
        $this->fakeEngine(null);

        $document = $this->runJob($document);

        $this->assertSame('completed', $document->automated_check_status);
        $this->assertSame('unchecked', $document->duplicate_check['verdict']);
        $this->assertNull($document->pending_face_embedding);
    }

    public function test_an_approved_document_enters_the_registry_and_is_then_seen_by_other_flows(): void
    {
        $document = $this->makeDocument('cni', 'NEW-123');
        $service = app(FaceDuplicateService::class);

        $service->rememberDocument($this->face(2), $document, $document->identityForDuplicateCheck());

        $row = FaceEmbedding::query()->first();
        $this->assertSame($document->id, $row->document_id);
        $this->assertSame('national_id', $row->document_type);
        // Sans OCR, l'identité retombe sur ce que l'utilisateur a déclaré.
        $this->assertSame('Jean Baptiste', $row->full_name);
        $this->assertSame('1990-01-15', $row->date_of_birth->toDateString());

        // Le flux partenaire voit ce visage : deuxième carte nationale => à vérifier,
        // et la correspondance indique qu'elle vient d'un compte SagaPass.
        $check = $service->check($this->face(2), 'national_id', 'OTHER-1', 'JEAN BAPTISTE', '1990-01-15');
        $this->assertSame('duplicate_same_type', $check->verdict);
        $this->assertSame($document->id, $check->matches[0]['document_id']);
        $this->assertNull($check->matches[0]['partner_name']);
    }

    public function test_re_approving_the_same_document_does_not_duplicate_the_registry_row(): void
    {
        $document = $this->makeDocument('cni', 'NEW-123');
        $service = app(FaceDuplicateService::class);
        $identity = ['document_number' => 'NEW-123', 'full_name' => 'JEAN BAPTISTE', 'date_of_birth' => '1990-01-15'];

        $service->rememberDocument($this->face(2), $document, $identity);
        $service->rememberDocument($this->face(2), $document, $identity);

        $this->assertSame(1, FaceEmbedding::query()->count());
    }
}
