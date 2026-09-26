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
        Schema::table('developer_applications', function (Blueprint $table) {
            // Une DeveloperApplication créée via la demande de partenariat
            // publique (Public\PartnerApplicationController) appartient à un
            // PartnerAccount, pas à un User citoyen - user_id reste pour les
            // applications créées autrement (compte OAuth "Login with
            // SagaID" côté citoyen), d'où les deux colonnes nullable en
            // parallèle plutôt qu'un remplacement.
            $table->foreignId('partner_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // user_id était NOT NULL à la création de la table - une entreprise
        // partenaire n'a pas de compte citoyen, donc ce champ doit devenir
        // optionnel pour permettre une DeveloperApplication rattachée
        // uniquement à un partner_account_id.
        Schema::table('developer_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('developer_applications', function (Blueprint $table) {
            $table->dropForeign(['partner_account_id']);
            $table->dropColumn('partner_account_id');
        });

        Schema::table('developer_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
