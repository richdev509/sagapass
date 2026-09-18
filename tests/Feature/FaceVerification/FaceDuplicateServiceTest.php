<?php

namespace Tests\Feature\FaceVerification;

use App\Models\FaceEmbedding;
use App\Services\FaceVerification\DuplicateCheckResult;
use App\Services\FaceVerification\FaceDuplicateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Matrice de règles du contrôle de doublons : une personne peut avoir une pièce
 * de chaque type (passeport, carte nationale, permis), jamais deux du même type.
 * Voir FaceDuplicateService. DatabaseTransactions (pas RefreshDatabase) pour la
 * même raison que AnalyzeDocumentJobTest.
 */
class FaceDuplicateServiceTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsFaceVerificationSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFaceVerificationSchema();
    }

    /** Empreinte de test : vecteur unitaire sur l'axe $axis (128 valeurs). */
    private function face(int $axis): array
    {
        $vector = array_fill(0, 128, 0.0);
        $vector[$axis] = 1.0;

        return $vector;
    }

    private function enroll(int $axis, string $type, ?string $number, ?string $name = 'JEAN BAPTISTE', ?string $dob = '1990-01-15'): FaceEmbedding
    {
        return FaceEmbedding::query()->create([
            'embedding' => $this->face($axis),
            'document_type' => $type,
            'document_number' => $number,
            'full_name' => $name,
            'date_of_birth' => $dob,
        ]);
    }

    private function check(int $axis, string $type, ?string $number, ?string $name = 'JEAN BAPTISTE', ?string $dob = '1990-01-15'): DuplicateCheckResult
    {
        return app(FaceDuplicateService::class)->check($this->face($axis), $type, $number, $name, $dob);
    }

    public function test_an_unknown_face_is_clean(): void
    {
        $this->enroll(0, 'national_id', 'AAA111');

        $this->assertFalse($this->check(5, 'national_id', 'BBB222', 'MARIE LOUIS', '1985-03-02')->isSuspicious());
    }

    public function test_the_same_document_reverified_is_clean(): void
    {
        $this->enroll(0, 'national_id', 'AAA-111');

        // Même pièce, numéro écrit autrement par l'OCR (tirets, casse).
        $this->assertFalse($this->check(0, 'national_id', 'aaa111')->isSuspicious());
        // Alias historique 'cni' = national_id.
        $this->assertFalse($this->check(0, 'cni', 'AAA111')->isSuspicious());
    }

    public function test_the_same_person_with_another_document_type_is_clean(): void
    {
        $this->enroll(0, 'national_id', 'AAA111');

        $this->assertFalse($this->check(0, 'passport', 'PP999')->isSuspicious());
        $this->assertFalse($this->check(0, 'drivers_license', 'DL777')->isSuspicious());
    }

    public function test_two_national_ids_for_the_same_face_go_to_review(): void
    {
        $this->enroll(0, 'national_id', 'AAA111');

        $result = $this->check(0, 'national_id', 'BBB222');

        $this->assertSame(DuplicateCheckResult::VERDICT_DUPLICATE_SAME_TYPE, $result->verdict);
        $this->assertSame('review', $result->severity());
        $this->assertSame('AAA111', $result->matches[0]['document_number']);
    }

    public function test_two_drivers_licenses_for_the_same_face_go_to_review(): void
    {
        $this->enroll(0, 'drivers_license', 'DL1');

        $this->assertSame(
            DuplicateCheckResult::VERDICT_DUPLICATE_SAME_TYPE,
            $this->check(0, 'drivers_license', 'DL2')->verdict,
        );
    }

    public function test_the_same_face_with_another_identity_is_a_strong_conflict_even_across_types(): void
    {
        $this->enroll(0, 'national_id', 'AAA111', 'JEAN BAPTISTE', '1990-01-15');

        $differentName = $this->check(0, 'passport', 'PP999', 'PIERRE DUPONT', '1990-01-15');
        $this->assertSame(DuplicateCheckResult::VERDICT_IDENTITY_CONFLICT, $differentName->verdict);
        $this->assertSame('strong', $differentName->severity());

        $differentBirth = $this->check(0, 'passport', 'PP999', 'JEAN BAPTISTE', '1988-07-01');
        $this->assertSame(DuplicateCheckResult::VERDICT_IDENTITY_CONFLICT, $differentBirth->verdict);
    }

    public function test_name_order_accents_and_one_ocr_typo_do_not_count_as_a_conflict(): void
    {
        $this->enroll(0, 'national_id', 'AAA111', 'JEAN BAPTISTE', '1990-01-15');

        $this->assertFalse($this->check(0, 'passport', 'PP999', 'Baptiste Jean', '1990-01-15')->isSuspicious());
        $this->assertFalse($this->check(0, 'passport', 'PP999', 'JEAN BAPTISTÉ', '1990-01-15')->isSuspicious());
        $this->assertFalse($this->check(0, 'passport', 'PP999', 'JEAN BAPTISTF', '1990-01-15')->isSuspicious());
    }

    public function test_the_same_document_number_with_another_face_is_a_strong_signal(): void
    {
        $this->enroll(0, 'national_id', 'AAA111');

        $result = $this->check(9, 'national_id', 'AAA111');

        $this->assertSame(DuplicateCheckResult::VERDICT_DOCUMENT_FACE_MISMATCH, $result->verdict);
        $this->assertSame('strong', $result->severity());
    }

    public function test_the_comparison_is_global_and_does_not_depend_on_the_partner(): void
    {
        // Aucune notion de partenaire dans la recherche : une empreinte
        // enregistrée pour un partenaire est visible pour tous les autres.
        $this->enroll(0, 'national_id', 'AAA111');

        $this->assertTrue($this->check(0, 'national_id', 'ZZZ000')->isSuspicious());
    }

    public function test_a_missing_document_number_of_the_same_type_is_reviewed_rather_than_ignored(): void
    {
        $this->enroll(0, 'national_id', 'AAA111');

        $this->assertSame(
            DuplicateCheckResult::VERDICT_DUPLICATE_SAME_TYPE,
            $this->check(0, 'national_id', null)->verdict,
        );
    }

    public function test_the_stored_embedding_is_encrypted_at_rest(): void
    {
        $row = $this->enroll(0, 'national_id', 'AAA111');

        $raw = DB::table('face_embeddings')->where('id', $row->id)->value('embedding');

        $this->assertStringNotContainsString('1.0', (string) $raw);
        $this->assertEquals($this->face(0), $row->fresh()->embedding);
    }
}
