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
            // S'assurer que tous les champs optionnels sont nullable
            $table->string('phone', 20)->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('profile_photo')->nullable()->change();
            $table->timestamp('email_verified_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('profile_photo')->nullable(false)->change();
            $table->timestamp('email_verified_at')->nullable(false)->change();
        });
    }
};
