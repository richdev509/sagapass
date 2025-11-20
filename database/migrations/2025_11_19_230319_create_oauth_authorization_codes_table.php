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
        Schema::create('oauth_authorization_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('developer_applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 80)->unique(); // Code d'autorisation unique
            $table->string('redirect_uri'); // URL de redirection spécifique
            $table->json('scopes'); // Scopes demandés
            $table->string('state')->nullable(); // CSRF protection
            $table->string('code_challenge')->nullable(); // PKCE
            $table->string('code_challenge_method')->nullable(); // PKCE method (S256)
            $table->timestamp('expires_at'); // Expire après 10 minutes
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Index
            $table->index('code');
            $table->index(['application_id', 'user_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_authorization_codes');
    }
};
