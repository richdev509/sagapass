<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Admin;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Définir toutes les permissions du système
        $permissions = [
            // Gestion des utilisateurs (citoyens)
            'view-users' => 'Voir les utilisateurs',
            'search-users' => 'Rechercher des utilisateurs',
            'view-user-details' => 'Voir les détails complets d\'un utilisateur',
            'create-users' => 'Créer des utilisateurs',
            'edit-users' => 'Modifier les utilisateurs',
            'delete-users' => 'Supprimer les utilisateurs',
            'approve-users' => 'Approuver les utilisateurs',
            'suspend-users' => 'Suspendre les utilisateurs',
            'activate-users' => 'Activer les utilisateurs',
            'export-users' => 'Exporter les données utilisateurs',
            'reset-user-password' => 'Réinitialiser le mot de passe utilisateur',

            // Gestion des documents
            'view-documents' => 'Voir les documents',
            'verify-documents' => 'Vérifier les documents',
            'approve-documents' => 'Approuver les documents',
            'reject-documents' => 'Rejeter les documents',

            // Gestion des développeurs
            'view-developers' => 'Voir les développeurs',
            'create-developers' => 'Créer des développeurs',
            'edit-developers' => 'Modifier les développeurs',
            'delete-developers' => 'Supprimer les développeurs',
            'approve-developers' => 'Approuver les développeurs',
            'suspend-developers' => 'Suspendre les développeurs',

            // Gestion des applications OAuth
            'view-oauth-apps' => 'Voir les applications OAuth',
            'create-oauth-apps' => 'Créer des applications OAuth',
            'edit-oauth-apps' => 'Modifier les applications OAuth',
            'delete-oauth-apps' => 'Supprimer les applications OAuth',
            'approve-oauth-apps' => 'Approuver les applications OAuth',
            'suspend-oauth-apps' => 'Suspendre les applications OAuth',

            // Gestion des scopes
            'view-scope-requests' => 'Voir les demandes de scopes',
            'approve-scope-requests' => 'Approuver les demandes de scopes',
            'reject-scope-requests' => 'Rejeter les demandes de scopes',
            'manage-scopes' => 'Gérer les scopes des applications',

            // Gestion des admins
            'view-admins' => 'Voir les administrateurs',
            'create-admins' => 'Créer des administrateurs',
            'edit-admins' => 'Modifier les administrateurs',
            'delete-admins' => 'Supprimer les administrateurs',

            // Gestion des rôles et permissions
            'view-roles' => 'Voir les rôles',
            'create-roles' => 'Créer des rôles',
            'edit-roles' => 'Modifier les rôles',
            'delete-roles' => 'Supprimer les rôles',
            'assign-roles' => 'Attribuer des rôles',
            'view-permissions' => 'Voir les permissions',
            'assign-permissions' => 'Attribuer des permissions',

            // Audit et logs
            'view-audit-logs' => 'Voir les logs d\'audit',
            'view-connection-logs' => 'Voir les logs de connexion OAuth',

            // Statistiques
            'view-statistics' => 'Voir les statistiques',
            'view-reports' => 'Voir les rapports',

            // Paramètres système
            'manage-settings' => 'Gérer les paramètres système',
            'manage-emails' => 'Gérer les emails',

            // Sécurité et monitoring
            'view-security-logs' => 'Voir les logs de sécurité',
            'manage-security' => 'Gérer la sécurité (bloquer/débloquer IPs)',
            'view-blocked-ips' => 'Voir les IPs bloquées',
            'block-ips' => 'Bloquer des IPs',
            'unblock-ips' => 'Débloquer des IPs',
            'delete-security-logs' => 'Supprimer les logs de sécurité',
        ];

        // Créer les permissions
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                ['description' => $description]
            );
        }

        // Créer les rôles avec leurs permissions

        // 1. Super Admin - Tous les droits
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'admin'],
            ['description' => 'Administrateur principal avec tous les droits']
        );
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin - Gestion complète sauf les rôles/permissions
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            ['description' => 'Administrateur avec droits de gestion']
        );
        $admin->syncPermissions([
            'view-users', 'search-users', 'view-user-details', 'edit-users', 'approve-users', 'suspend-users', 'activate-users', 'reset-user-password', 'export-users',
            'view-documents', 'verify-documents', 'approve-documents', 'reject-documents',
            'view-developers', 'edit-developers', 'approve-developers', 'suspend-developers',
            'view-oauth-apps', 'edit-oauth-apps', 'approve-oauth-apps', 'suspend-oauth-apps',
            'view-scope-requests', 'approve-scope-requests', 'reject-scope-requests', 'manage-scopes',
            'view-audit-logs', 'view-connection-logs',
            'view-statistics', 'view-reports',
            'manage-settings', // ✅ Ajout permission gestion paramètres système
            'view-security-logs', 'view-blocked-ips', // Ajout permissions sécurité (lecture seule)
        ]);

        // 3. Modérateur - Validation des documents et utilisateurs
        $moderator = Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'admin'],
            ['description' => 'Modérateur - Validation des documents et utilisateurs']
        );
        $moderator->syncPermissions([
            'view-users', 'search-users', 'view-user-details', 'approve-users', 'suspend-users', 'activate-users',
            'view-documents', 'verify-documents', 'approve-documents', 'reject-documents',
            'view-developers', 'approve-developers',
            'view-oauth-apps', 'approve-oauth-apps',
            'view-audit-logs',
            'view-statistics',
        ]);

        // 4. Support - Consultation uniquement
        $support = Role::firstOrCreate(
            ['name' => 'support', 'guard_name' => 'admin'],
            ['description' => 'Support - Consultation et assistance']
        );
        $support->syncPermissions([
            'view-users', 'search-users', 'view-user-details',
            'view-documents',
            'view-developers',
            'view-oauth-apps',
            'view-scope-requests',
            'view-audit-logs',
            'view-connection-logs',
            'view-statistics',
        ]);

        // 5. OAuth Manager - Gestion des applications OAuth uniquement
        $oauthManager = Role::firstOrCreate(
            ['name' => 'oauth-manager', 'guard_name' => 'admin'],
            ['description' => 'Gestionnaire OAuth - Applications et scopes']
        );
        $oauthManager->syncPermissions([
            'view-developers', 'edit-developers',
            'view-oauth-apps', 'edit-oauth-apps', 'approve-oauth-apps', 'suspend-oauth-apps',
            'view-scope-requests', 'approve-scope-requests', 'reject-scope-requests', 'manage-scopes',
            'view-connection-logs',
            'view-statistics',
        ]);

        // 6. Cyber Security Admin - Gestion complète de la sécurité
        $cyberAdmin = Role::firstOrCreate(
            ['name' => 'cyber-admin', 'guard_name' => 'admin'],
            ['description' => 'Administrateur Cyber Sécurité - Gestion complète de la sécurité']
        );
        $cyberAdmin->syncPermissions([
            'view-security-logs',
            'manage-security',
            'view-blocked-ips',
            'block-ips',
            'unblock-ips',
            'delete-security-logs',
            'view-audit-logs',
            'view-connection-logs',
            'view-statistics',
        ]);

        $this->command->info('Rôles et permissions créés avec succès !');
        $this->command->info('');
        $this->command->info('Rôles créés :');
        $this->command->info('- super-admin : ' . $superAdmin->permissions->count() . ' permissions');
        $this->command->info('- admin : ' . $admin->permissions->count() . ' permissions');
        $this->command->info('- moderator : ' . $moderator->permissions->count() . ' permissions');
        $this->command->info('- support : ' . $support->permissions->count() . ' permissions');
        $this->command->info('- oauth-manager : ' . $oauthManager->permissions->count() . ' permissions');
        $this->command->info('- cyber-admin : ' . $cyberAdmin->permissions->count() . ' permissions');
    }
}
