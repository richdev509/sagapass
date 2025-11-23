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
        Schema::create('digital_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique()->comment('Token crypté pour le QR code');
            $table->string('qr_code_path')->nullable()->comment('Chemin du fichier QR code');
            $table->timestamp('expires_at')->comment('Expiration 12h après génération');
            $table->timestamp('last_validated_at')->nullable()->comment('Dernière validation du QR');
            $table->ipAddress('validated_from_ip')->nullable()->comment('IP de la dernière validation');
            $table->boolean('is_active')->default(true)->comment('Badge actif ou révoqué');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index('token');
            $table->index('expires_at');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_badges');
    }
};
