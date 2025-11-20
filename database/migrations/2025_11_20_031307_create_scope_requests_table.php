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
        Schema::create('scope_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('developer_applications')->onDelete('cascade');
            $table->json('requested_scopes'); // Scopes demandés
            $table->text('justification'); // Justification de la demande
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->text('admin_comment')->nullable(); // Commentaire de l'admin
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Index
            $table->index(['application_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_requests');
    }
};
