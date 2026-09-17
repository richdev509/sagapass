<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            // 'document_reject' (numéro de pièce trouvé en liste noire) |
            // 'name_alert' (nom+prénom correspondant, signal faible — homonymes
            // possibles) | null. Voir BlacklistScreeningService.
            $table->string('blacklist_hit')->nullable()->after('partner_verified_identity_id');
            $table->foreignId('blacklist_matched_identity_id')
                ->nullable()
                ->after('blacklist_hit')
                ->constrained('blacklisted_identities', 'id', 'pvs_blacklist_identity_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partner_verification_sessions', function (Blueprint $table) {
            $table->dropForeign('pvs_blacklist_identity_fk');
            $table->dropColumn(['blacklist_matched_identity_id', 'blacklist_hit']);
        });
    }
};
