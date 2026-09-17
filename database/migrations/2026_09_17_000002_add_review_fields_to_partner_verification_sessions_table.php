<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            // Court motif structuré (ex. 'data_mismatch', 'manual_rejection')
            // — complète analysis_raw (détail technique brut) et warnings.
            $table->string('rejection_reason')->nullable()->after('warnings');

            // Renseignés seulement pour une décision admin manuelle (voir
            // 'awaiting_manual_review' — échec technique de l'analyse auto,
            // photos conservées pour revue au lieu d'être purgées).
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['rejection_reason', 'reviewed_at']);
        });
    }
};
