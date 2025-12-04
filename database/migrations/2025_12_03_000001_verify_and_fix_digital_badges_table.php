<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Vérifie et corrige la structure de la table digital_badges
     * pour s'assurer que toutes les colonnes nécessaires existent.
     */
    public function up(): void
    {
        // Si la table n'existe pas, la créer complètement
        if (!Schema::hasTable('digital_badges')) {
            Schema::create('digital_badges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('badge_token', 64)->unique()->comment('Token crypté unique pour le QR code');
                $table->text('qr_code_data')->comment('Données encodées dans le QR code');
                $table->timestamp('expires_at')->comment('Expiration du badge (12h)');
                $table->boolean('is_active')->default(true)->comment('Badge actif ou révoqué');
                $table->string('ip_address', 45)->nullable()->comment('IP de génération');
                $table->text('user_agent')->nullable()->comment('User agent de génération');
                $table->timestamp('last_scanned_at')->nullable()->comment('Dernière scan du QR code');
                $table->integer('scan_count')->default(0)->comment('Nombre de scans');
                $table->timestamps();

                // Index pour optimiser les requêtes
                $table->index('badge_token');
                $table->index('expires_at');
                $table->index(['user_id', 'is_active']);
            });

            echo "✅ Table digital_badges créée avec succès.\n";
            return;
        }

        echo "🔍 Vérification de la structure de la table digital_badges...\n";

        // SUPPRIMER D'ABORD les anciennes colonnes pour éviter les conflits
        if (Schema::hasColumn('digital_badges', 'token')) {
            Schema::table('digital_badges', function (Blueprint $table) {
                echo "🗑️  Suppression de l'ancienne colonne 'token'...\n";
                $table->dropColumn('token');
            });
        }

        if (Schema::hasColumn('digital_badges', 'qr_code_path')) {
            Schema::table('digital_badges', function (Blueprint $table) {
                echo "🗑️  Suppression de l'ancienne colonne 'qr_code_path'...\n";
                $table->dropColumn('qr_code_path');
            });
        }

        if (Schema::hasColumn('digital_badges', 'last_validated_at')) {
            Schema::table('digital_badges', function (Blueprint $table) {
                echo "🗑️  Suppression de l'ancienne colonne 'last_validated_at'...\n";
                $table->dropColumn('last_validated_at');
            });
        }

        if (Schema::hasColumn('digital_badges', 'validated_from_ip')) {
            Schema::table('digital_badges', function (Blueprint $table) {
                echo "🗑️  Suppression de l'ancienne colonne 'validated_from_ip'...\n";
                $table->dropColumn('validated_from_ip');
            });
        }

        // Vérifier et ajouter les colonnes manquantes
        Schema::table('digital_badges', function (Blueprint $table) {

            // Vérifier badge_token (colonne principale qui manque souvent)
            if (!Schema::hasColumn('digital_badges', 'badge_token')) {
                echo "➕ Ajout de la colonne 'badge_token'...\n";
                $table->string('badge_token', 64)->unique()->after('user_id')->comment('Token crypté unique pour le QR code');
                $table->index('badge_token');
            }            // Vérifier qr_code_data
            if (!Schema::hasColumn('digital_badges', 'qr_code_data')) {
                echo "➕ Ajout de la colonne 'qr_code_data'...\n";
                $table->text('qr_code_data')->after('badge_token')->comment('Données encodées dans le QR code');
            }

            // Vérifier expires_at
            if (!Schema::hasColumn('digital_badges', 'expires_at')) {
                echo "➕ Ajout de la colonne 'expires_at'...\n";
                $table->timestamp('expires_at')->after('qr_code_data')->comment('Expiration du badge (12h)');
                $table->index('expires_at');
            }

            // Vérifier is_active
            if (!Schema::hasColumn('digital_badges', 'is_active')) {
                echo "➕ Ajout de la colonne 'is_active'...\n";
                $table->boolean('is_active')->default(true)->after('expires_at')->comment('Badge actif ou révoqué');
            }

            // Vérifier ip_address
            if (!Schema::hasColumn('digital_badges', 'ip_address')) {
                echo "➕ Ajout de la colonne 'ip_address'...\n";
                $table->string('ip_address', 45)->nullable()->after('is_active')->comment('IP de génération');
            }

            // Vérifier user_agent
            if (!Schema::hasColumn('digital_badges', 'user_agent')) {
                echo "➕ Ajout de la colonne 'user_agent'...\n";
                $table->text('user_agent')->nullable()->after('ip_address')->comment('User agent de génération');
            }

            // Vérifier last_scanned_at
            if (!Schema::hasColumn('digital_badges', 'last_scanned_at')) {
                echo "➕ Ajout de la colonne 'last_scanned_at'...\n";
                $table->timestamp('last_scanned_at')->nullable()->after('user_agent')->comment('Dernière scan du QR code');
            }

            // Vérifier scan_count
            if (!Schema::hasColumn('digital_badges', 'scan_count')) {
                echo "➕ Ajout de la colonne 'scan_count'...\n";
                $table->integer('scan_count')->default(0)->after('last_scanned_at')->comment('Nombre de scans');
            }
        });

        echo "✅ Structure de la table digital_badges vérifiée et corrigée !\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire en rollback pour éviter de supprimer des données
        echo "⚠️  Rollback désactivé pour préserver les données.\n";
    }
};
