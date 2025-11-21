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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('type')->index(); // sql_injection, xss, brute_force, etc.
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('method', 10); // GET, POST, etc.
            $table->text('url');
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable(); // Données de la requête
            $table->text('description')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_until')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            // Index pour performance
            $table->index('created_at');
            $table->index(['ip_address', 'is_blocked']);
        });

        // Table pour gérer les IP bloquées
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->foreignId('blocked_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('security_logs');
    }
};
