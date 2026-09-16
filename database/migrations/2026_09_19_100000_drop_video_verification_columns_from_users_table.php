<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire les colonnes propres à la vérification vidéo (fonctionnalité
 * supprimée — recentrage sur le flux document+selfie automatisé). Ne touche
 * pas account_level/verification_level/verified_at/profile_picture, qui
 * restent utilisées par le reste du système de compte (voir
 * 2025_11_21_212947_add_account_levels_to_users_table.php, qui ajoutait ces
 * colonnes en même temps).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['video_status']);
            $table->dropColumn([
                'verification_video',
                'video_verified_at',
                'video_status',
                'video_rejection_reason',
                'video_consent_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_video')->nullable();
            $table->timestamp('video_verified_at')->nullable();
            $table->enum('video_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            $table->text('video_rejection_reason')->nullable();
            $table->timestamp('video_consent_at')->nullable();
            $table->index('video_status');
        });
    }
};
