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
        // Modifier l'ENUM pour ajouter 'pending'
        DB::statement("ALTER TABLE users MODIFY COLUMN account_level ENUM('pending', 'basic', 'verified') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à l'ancien ENUM sans 'pending'
        DB::statement("ALTER TABLE users MODIFY COLUMN account_level ENUM('basic', 'verified') NOT NULL DEFAULT 'verified'");
    }
};
