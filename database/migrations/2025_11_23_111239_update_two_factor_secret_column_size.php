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
        Schema::table('admins', function (Blueprint $table) {
            // Agrandir la colonne pour contenir le secret crypté (environ 200 caractères)
            $table->text('two_factor_secret')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Remettre en VARCHAR(255) si nécessaire
            $table->string('two_factor_secret')->nullable()->change();
        });
    }
};
