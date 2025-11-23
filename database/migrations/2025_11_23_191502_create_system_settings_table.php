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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clé du paramètre');
            $table->text('value')->nullable()->comment('Valeur du paramètre');
            $table->string('type')->default('string')->comment('Type: string, boolean, json');
            $table->text('description')->nullable()->comment('Description du paramètre');
            $table->timestamps();
        });

        // Insérer les paramètres par défaut
        DB::table('system_settings')->insert([
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Mode maintenance activé/désactivé',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'beta_mode',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Mode Beta/Early Access activé',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_support_link',
                'value' => 'https://wa.me/221000000000',
                'type' => 'string',
                'description' => 'Lien WhatsApp pour le support',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'Le système est actuellement en maintenance. Nous serons de retour dans quelques minutes.',
                'type' => 'string',
                'description' => 'Message affiché en mode maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
