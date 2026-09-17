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
        Schema::create('partner_verified_identities', function (Blueprint $table) {
            $table->id();

            // Identifiant public exposé au partenaire ("kyc_id") — distinct de
            // l'id interne, même convention que DeveloperApplication::client_id.
            $table->uuid('kyc_id')->unique();

            $table->foreignId('developer_application_id')->constrained()->cascadeOnDelete();

            // Clé de déduplication : une seule identité durable par (partenaire,
            // numéro de document) — une nouvelle session complétée pour le même
            // document met à jour cette ligne plutôt que d'en créer une autre.
            $table->string('document_type');
            $table->string('document_number');

            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->float('face_match_score')->nullable();
            $table->boolean('liveness_passed')->nullable();

            // 'valid' | 'expired' | 'revoked' (revoked : usage admin futur, non géré pour l'instant)
            $table->string('status')->default('valid');

            $table->timestamp('verified_at');
            $table->timestamp('valid_until');

            // Copié depuis la session qui (re)confirme l'identité à chaque fois
            // — sert de destination pour l'alerte d'expiration (kyc.expired),
            // DeveloperApplication n'ayant pas de webhook par défaut.
            $table->string('webhook_url')->nullable();

            // Nom de contrainte explicite et court : le nom par défaut généré
            // par Laravel (table+colonne+"_foreign") dépasse la limite MySQL
            // de 64 caractères pour ces deux noms longs.
            $table->foreignId('last_partner_verification_session_id')
                ->nullable()
                ->constrained('partner_verification_sessions', 'id', 'pvi_last_session_fk')
                ->nullOnDelete();

            // Empêche d'envoyer l'alerte d'expiration plus d'une fois.
            $table->timestamp('expired_notified_at')->nullable();

            $table->timestamps();

            // Nom explicite : le nom par défaut dépasse aussi la limite MySQL de 64 caractères.
            $table->unique(['developer_application_id', 'document_number'], 'pvi_partner_document_unique');
            $table->index(['status', 'valid_until']);
        });

        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->foreignId('partner_verified_identity_id')
                ->nullable()
                ->after('warnings')
                ->constrained('partner_verified_identities', 'id', 'pvs_verified_identity_fk')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->dropForeign('pvs_verified_identity_fk');
            $table->dropColumn('partner_verified_identity_id');
        });

        Schema::dropIfExists('partner_verified_identities');
    }
};
