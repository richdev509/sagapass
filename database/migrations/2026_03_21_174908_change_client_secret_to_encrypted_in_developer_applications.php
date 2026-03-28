<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change client_secret from bcrypt (one-way) to encrypt() (reversible)
     * so admins can view the secret.
     */
    public function up(): void
    {
        // Changer le type de colonne pour supporter les valeurs encrypt() plus longues
        Schema::table('developer_applications', function (Blueprint $table) {
            $table->text('client_secret')->change();
        });

        // Régénérer les secrets existants avec encrypt()
        $apps = DB::table('developer_applications')->get();
        foreach ($apps as $app) {
            $newSecret = \Illuminate\Support\Str::random(64);
            DB::table('developer_applications')
                ->where('id', $app->id)
                ->update(['client_secret' => encrypt($newSecret)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('developer_applications', function (Blueprint $table) {
            $table->string('client_secret')->change();
        });
    }
};
