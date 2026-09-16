<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partner_verification_sessions', function (Blueprint $table) {
            $table->id();

            // Jeton porteur, haute entropie — c'est la seule "authentification"
            // de la page publique de capture (pas de compte SagaID créé pour
            // ce flux). Traité comme un secret court terme, jamais loggé.
            $table->string('token', 64)->unique();

            $table->foreignId('developer_application_id')->constrained()->cascadeOnDelete();
            $table->string('partner_reference')->nullable(); // référence libre du partenaire (ex. id de son propre dossier KYC)
            $table->string('webhook_url');
            $table->string('document_type');

            // Photos de la pièce, envoyées par le partenaire à la création de
            // la session. Selfies remplis seulement une fois la capture faite
            // côté téléphone (mode vivacité active : 3 frames).
            $table->string('front_photo_path')->nullable();
            $table->string('back_photo_path')->nullable();
            $table->string('selfie_left_path')->nullable();
            $table->string('selfie_center_path')->nullable();
            $table->string('selfie_right_path')->nullable();

            // 'awaiting_capture' | 'processing' | 'completed' | 'failed' | 'expired'
            $table->string('status')->default('awaiting_capture');

            $table->float('face_match_score')->nullable();
            $table->boolean('liveness_passed')->nullable();
            $table->string('ocr_extracted_document_number')->nullable();
            $table->string('ocr_extracted_full_name')->nullable();
            $table->date('ocr_extracted_date_of_birth')->nullable();
            $table->json('analysis_raw')->nullable(); // payload complet du moteur, pour debug/support
            $table->json('warnings')->nullable();

            // Forensics, capturés à la création ET à la soumission du selfie
            // (mêmes colonnes que PartnerVerification).
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_verification_sessions');
    }
};
