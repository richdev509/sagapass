<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour les utilisateurs existants sans téléphone
        $usersWithoutPhone = User::whereNull('phone')->get();
        foreach ($usersWithoutPhone as $index => $user) {
            $user->update([
                'phone' => '509' . str_pad(37000000 + $index, 8, '0', STR_PAD_LEFT)
            ]);
        }

        // Mettre à jour les utilisateurs existants sans date de naissance
        User::whereNull('date_of_birth')->update([
            'date_of_birth' => '1990-01-01'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas besoin de revenir en arrière
    }
};
