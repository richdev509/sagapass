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
            // Rendre le téléphone unique et obligatoire
            $table->string('phone', 20)->nullable(false)->unique()->change();

            // Rendre la date de naissance obligatoire
            $table->date('date_of_birth')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revenir à nullable
            $table->string('phone', 20)->nullable()->change();
            $table->dropUnique(['phone']);

            $table->date('date_of_birth')->nullable()->change();
        });
    }
};
