<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nettoyer les doublons d'email avant d'ajouter l'index unique
        DB::statement('
            DELETE t1 FROM users t1
            INNER JOIN users t2
            WHERE t1.id > t2.id AND t1.email = t2.email
        ');

        // Nettoyer les doublons de document_number
        DB::statement('
            DELETE t1 FROM documents t1
            INNER JOIN documents t2
            WHERE t1.id > t2.id AND t1.document_number = t2.document_number
        ');

        // Nettoyer les doublons de card_number (sauf NULL)
        DB::statement('
            DELETE t1 FROM documents t1
            INNER JOIN documents t2
            WHERE t1.id > t2.id
            AND t1.card_number = t2.card_number
            AND t1.card_number IS NOT NULL
        ');

        // Ajouter les index uniques (ils échoueront silencieusement s'ils existent déjà)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        } catch (\Exception $e) {
            // Index existe déjà
        }

        try {
            Schema::table('documents', function (Blueprint $table) {
                $table->unique('document_number');
                $table->unique('card_number');
            });
        } catch (\Exception $e) {
            // Index existe déjà
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (\Exception $e) {
            // Index n'existe pas
        }

        try {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropUnique(['document_number']);
                $table->dropUnique(['card_number']);
            });
        } catch (\Exception $e) {
            // Index n'existe pas
        }
    }
};
