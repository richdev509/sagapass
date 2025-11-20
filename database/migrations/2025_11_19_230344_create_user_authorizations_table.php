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
        Schema::create('user_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained('developer_applications')->onDelete('cascade');
            $table->json('scopes'); // Scopes autorisés par l'utilisateur
            $table->timestamp('granted_at'); // Date d'autorisation
            $table->timestamp('revoked_at')->nullable(); // Date de révocation (null = actif)
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Index
            $table->index(['user_id', 'application_id']);
            $table->index('revoked_at');

            // Contrainte unique : un utilisateur ne peut avoir qu'une seule autorisation active par app
            $table->unique(['user_id', 'application_id', 'revoked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_authorizations');
    }
};
