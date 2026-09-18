<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flux Document (comptes SagaPass) : même contrôle de doublons que le flux
        // partenaire par QR (voir FaceDuplicateService), à titre INDICATIF — le
        // verdict n'empêche rien, l'admin décide toujours.
        Schema::table('documents', function (Blueprint $table) {
            $table->json('duplicate_check')->nullable();

            // Empreinte en attente de la décision admin (chiffrée) : enregistrée
            // dans face_embeddings seulement à l'approbation, puis vidée.
            $table->text('pending_face_embedding')->nullable();
        });

        Schema::table('face_embeddings', function (Blueprint $table) {
            $table->foreignId('document_id')
                ->nullable()
                ->after('partner_verified_identity_id')
                ->constrained('documents', 'id', 'fe_document_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('face_embeddings', function (Blueprint $table) {
            $table->dropForeign('fe_document_fk');
            $table->dropColumn('document_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['duplicate_check', 'pending_face_embedding']);
        });
    }
};
