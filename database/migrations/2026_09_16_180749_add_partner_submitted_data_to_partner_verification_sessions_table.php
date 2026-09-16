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
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            // Champs texte du formulaire du partenaire (nom, date de
            // naissance, téléphone, adresse...) — gardés pour recoupement
            // ultérieur contre l'OCR (webhook/admin), jamais comparés
            // automatiquement pour l'instant. Le partenaire n'envoie plus
            // aucun fichier à la création de session (voir le plan associé) :
            // ces champs remplacent les anciennes pièces jointes front_photo/
            // back_photo comme seule donnée transmise à ce moment-là.
            $table->json('partner_submitted_data')->nullable()->after('partner_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->dropColumn('partner_submitted_data');
        });
    }
};
