<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('selfie_path')->nullable()->after('back_photo_path');

            // Résultat brut du moteur Python, pour audit/debug (même principe que
            // PartnerVerification.request_data/response_data, cast array).
            $table->json('automated_analysis_raw')->nullable()->after('selfie_path');

            // Champs extraits par OCR — jamais utilisés pour écraser automatiquement
            // ce que l'utilisateur a saisi ; affichés à l'admin pour comparaison.
            $table->string('ocr_extracted_document_number')->nullable()->after('automated_analysis_raw');
            $table->string('ocr_extracted_full_name')->nullable()->after('ocr_extracted_document_number');
            $table->date('ocr_extracted_date_of_birth')->nullable()->after('ocr_extracted_full_name');

            $table->float('face_match_score')->nullable()->after('ocr_extracted_date_of_birth');
            $table->boolean('liveness_passed')->nullable()->after('face_match_score');

            // 'not_run' | 'processing' | 'completed' | 'failed' — décrit uniquement
            // si le moteur a tourné, jamais le verdict d'approbation (qui reste
            // verification_status, décidé par un admin).
            $table->string('automated_check_status')->default('not_run')->after('liveness_passed');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'selfie_path',
                'automated_analysis_raw',
                'ocr_extracted_document_number',
                'ocr_extracted_full_name',
                'ocr_extracted_date_of_birth',
                'face_match_score',
                'liveness_passed',
                'automated_check_status',
            ]);
        });
    }
};
