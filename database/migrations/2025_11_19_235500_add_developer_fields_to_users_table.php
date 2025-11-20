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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_developer')->default(false)->after('email');
            $table->string('company_name')->nullable()->after('is_developer');
            $table->text('developer_bio')->nullable()->after('company_name');
            $table->string('developer_website')->nullable()->after('developer_bio');
            $table->timestamp('developer_verified_at')->nullable()->after('developer_website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_developer',
                'company_name',
                'developer_bio',
                'developer_website',
                'developer_verified_at'
            ]);
        });
    }
};
