<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registre GLOBAL (tous partenaires confondus) des visages déjà vérifiés,
        // pour détecter qu'une même personne se présente avec deux pièces du même
        // type, ou avec une identité différente. Volontairement non rattaché à un
        // partenaire : SagaPass est un service partagé, la comparaison doit
        // traverser les partenaires. Conservé sans expiration (décision produit).
        Schema::create('face_embeddings', function (Blueprint $table) {
            $table->id();

            // Empreinte SFace (128 valeurs), chiffrée par le cast 'encrypted:array'.
            $table->text('embedding');

            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();

            // Noms de contrainte explicites : les noms par défaut dépassent la
            // limite MySQL de 64 caractères.
            $table->foreignId('developer_application_id')
                ->nullable()
                ->constrained('developer_applications', 'id', 'fe_developer_app_fk')
                ->nullOnDelete();
            $table->foreignId('partner_verification_session_id')
                ->nullable()
                ->constrained('partner_verification_sessions', 'id', 'fe_session_fk')
                ->nullOnDelete();
            $table->foreignId('partner_verified_identity_id')
                ->nullable()
                ->constrained('partner_verified_identities', 'id', 'fe_verified_identity_fk')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['document_type', 'document_number'], 'fe_document_index');
        });

        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            // Verdict du contrôle de doublons (voir FaceDuplicateService) — null
            // tant que le contrôle n'a pas tourné.
            $table->json('duplicate_check')->nullable()->after('warnings');

            // Empreinte en attente de décision admin (chiffrée) — enregistrée dans
            // face_embeddings seulement à l'approbation, puis vidée.
            $table->text('pending_face_embedding')->nullable()->after('duplicate_check');

            // Preuve du consentement affiché sur la page de capture.
            $table->timestamp('consent_accepted_at')->nullable();
            $table->string('consent_terms_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'duplicate_check',
                'pending_face_embedding',
                'consent_accepted_at',
                'consent_terms_version',
            ]);
        });

        Schema::dropIfExists('face_embeddings');
    }
};
