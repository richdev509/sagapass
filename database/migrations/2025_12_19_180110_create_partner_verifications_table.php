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
        Schema::create('partner_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('partner_id')->index(); // ID de l'application OAuth partenaire
            $table->string('partner_reference')->nullable()->index(); // ID du client chez le partenaire
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // User créé
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('request_data')->nullable(); // Données reçues du partenaire
            $table->json('response_data')->nullable(); // Réponse envoyée
            $table->string('error_message')->nullable(); // Message d'erreur si échec
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Index composé pour recherche rapide
            $table->index(['partner_id', 'partner_reference']);
            $table->index(['partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_verifications');
    }
};
