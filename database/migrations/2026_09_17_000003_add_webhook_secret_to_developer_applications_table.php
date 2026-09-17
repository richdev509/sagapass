<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developer_applications', function (Blueprint $table) {
            // Chiffré comme client_secret/app_key — dédié à la signature des
            // webhooks sortants (voir NotifyPartnerSessionWebhook), distinct
            // de client_secret pour ne pas coupler leurs rotations. Remplace
            // l'ancien secret global unique partagé par tous les partenaires
            // (services.sagaid_webhook.secret, gardé en repli).
            $table->text('webhook_secret')->nullable()->after('app_key');
        });

        // Rétro-remplissage des partenaires existants — sans ça, ils
        // resteraient sur l'ancien secret global partagé jusqu'à une
        // régénération manuelle.
        DB::table('developer_applications')->whereNull('webhook_secret')->get(['id'])->each(function ($app) {
            DB::table('developer_applications')->where('id', $app->id)->update([
                'webhook_secret' => encrypt(Str::random(64)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('developer_applications', function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });
    }
};
