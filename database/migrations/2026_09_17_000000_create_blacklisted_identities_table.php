<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklisted_identities', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // Comparaison par nom normalisé (voir BlacklistedIdentity::normalizedName())
            // — jamais une clé d'unicité, plusieurs entrées peuvent partager un nom.
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('blacklisted_identity_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blacklisted_identity_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_number');
            $table->timestamps();

            // Une même personne en liste noire peut avoir plusieurs pièces
            // enregistrées (voir la demande d'origine) — jamais deux fois la
            // même pièce en revanche.
            $table->unique(['document_type', 'document_number'], 'bid_type_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklisted_identity_documents');
        Schema::dropIfExists('blacklisted_identities');
    }
};
