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
        Schema::create('developer_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Développeur propriétaire
            $table->string('name'); // Nom de l'application
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->uuid('client_id')->unique(); // UUID public
            $table->string('client_secret'); // Haché avec bcrypt
            $table->json('redirect_uris'); // Liste des URLs de callback autorisées
            $table->json('allowed_scopes')->nullable(); // Scopes autorisés par l'admin
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->boolean('is_trusted')->default(false); // Services gouvernementaux de confiance
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // Index pour recherche
            $table->index('client_id');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_applications');
    }
};
