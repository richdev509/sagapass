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
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20);
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->enum('message_type', ['text', 'interactive', 'button', 'list', 'document', 'image', 'status', 'other']);
            $table->string('message_id', 100)->nullable();
            $table->text('message_content')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('phone_number');
            $table->index('message_id');
            $table->index('direction');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
