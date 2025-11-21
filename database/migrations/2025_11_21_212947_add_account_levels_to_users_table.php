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
        Schema::table('users', function (Blueprint $table) {
            // Niveaux de compte (Basic / Vérifié)
            $table->enum('account_level', ['basic', 'verified'])
                  ->default('verified')
                  ->after('email_verified_at')
                  ->comment('Niveau du compte: basic (avec photo/vidéo) ou verified (avec documents)');

            $table->enum('verification_level', ['none', 'email', 'video', 'document'])
                  ->default('email')
                  ->after('account_level')
                  ->comment('Niveau de vérification atteint');

            $table->timestamp('verified_at')->nullable()
                  ->after('verification_level')
                  ->comment('Date de passage en compte vérifié');

            // Photo de profil
            $table->string('profile_picture')->nullable()
                  ->after('verified_at')
                  ->comment('Chemin de la photo de profil');

            // Vidéo de vérification
            $table->string('verification_video')->nullable()
                  ->after('profile_picture')
                  ->comment('Chemin de la vidéo de vérification');

            $table->timestamp('video_verified_at')->nullable()
                  ->after('verification_video')
                  ->comment('Date de validation de la vidéo');

            $table->enum('video_status', ['none', 'pending', 'approved', 'rejected'])
                  ->default('none')
                  ->after('video_verified_at')
                  ->comment('Statut de la vidéo de vérification');

            $table->text('video_rejection_reason')->nullable()
                  ->after('video_status')
                  ->comment('Raison du rejet de la vidéo');

            $table->timestamp('video_consent_at')->nullable()
                  ->after('video_rejection_reason')
                  ->comment('Date de consentement RGPD pour stockage photo/vidéo');

            // Index pour optimiser les requêtes
            $table->index('account_level');
            $table->index('video_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_level']);
            $table->dropIndex(['video_status']);

            $table->dropColumn([
                'account_level',
                'verification_level',
                'verified_at',
                'profile_picture',
                'verification_video',
                'video_verified_at',
                'video_status',
                'video_rejection_reason',
                'video_consent_at',
            ]);
        });
    }
};
