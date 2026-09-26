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
        // Compte "entreprise partenaire" - séparé des comptes citoyens
        // (users) : une entreprise n'a pas de KYC personnel, pas de selfie,
        // etc. Guard Laravel dédié ('partner', voir config/auth.php),
        // authentifiable comme n'importe quel guard.
        Schema::create('partner_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_accounts');
    }
};
