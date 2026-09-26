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
        Schema::table('partner_verified_identities', function (Blueprint $table) {
            // Tous les champs OCR extraits (voir analyze.py::_ocr_field_keys),
            // pas seulement document_number/full_name/date_of_birth — support
            // du passeport (nationalité, MRZ, numéro personnel) et du permis
            // de conduire (adresse, groupe sanguin, catégorie de véhicule,
            // etc.). Générique par type plutôt qu'une colonne dédiée par
            // champ, pour ne pas migrer à chaque nouveau type de pièce.
            $table->json('extracted_fields')->nullable()->after('date_of_birth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_verified_identities', function (Blueprint $table) {
            $table->dropColumn('extracted_fields');
        });
    }
};
