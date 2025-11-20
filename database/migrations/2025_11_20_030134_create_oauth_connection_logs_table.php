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
        Schema::create('oauth_connection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained('developer_applications')->onDelete('cascade');
            $table->foreignId('authorization_id')->constrained('user_authorizations')->onDelete('cascade');
            $table->enum('action', ['authorized', 'reconnected', 'token_issued', 'token_revoked'])->default('reconnected');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // Windows, Linux, macOS, Android, iOS
            $table->json('scopes')->nullable(); // Scopes demandés lors de cette connexion
            $table->timestamp('connected_at');
            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['user_id', 'connected_at']);
            $table->index(['application_id', 'connected_at']);
            $table->index('connected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_connection_logs');
    }
};
