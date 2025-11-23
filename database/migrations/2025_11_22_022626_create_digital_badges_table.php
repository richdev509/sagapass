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
            $table->string('badge_token', 64)->unique()->comment('Token crypté unique pour le QR code');
            $table->text('qr_code_data')->comment('Données encodées dans le QR code');
            $table->timestamp('expires_at')->comment('Expiration du badge (12h)');
            $table->boolean('is_active')->default(true)->comment('Badge actif ou révoqué');
            $table->string('ip_address', 45)->nullable()->comment('IP de génération');
            $table->text('user_agent')->nullable()->comment('User agent de génération');
            $table->timestamp('last_scanned_at')->nullable()->comment('Dernière scan du QR code');
            $table->integer('scan_count')->default(0)->comment('Nombre de scans');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index('badge_token');
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
