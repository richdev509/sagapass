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
            // Gestion des utilisateurs
            'view-users' => 'Voir les utilisateurs',
            'create-users' => 'Créer des utilisateurs',
            'edit-users' => 'Modifier les utilisateurs',
            'delete-users' => 'Supprimer les utilisateurs',
            'approve-users' => 'Approuver les utilisateurs',
            'suspend-users' => 'Suspendre les utilisateurs',

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
            'view-users', 'edit-users', 'approve-users', 'suspend-users',
            'view-documents', 'verify-documents', 'approve-documents', 'reject-documents',
            'view-developers', 'edit-developers', 'approve-developers', 'suspend-developers',
            'view-oauth-apps', 'edit-oauth-apps', 'approve-oauth-apps', 'suspend-oauth-apps',
            'view-scope-requests', 'approve-scope-requests', 'reject-scope-requests', 'manage-scopes',
            'view-audit-logs', 'view-connection-logs',
            'view-statistics', 'view-reports',
        ]);

        // 3. Modérateur - Validation des documents et utilisateurs
        $moderator = Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'admin'],
            ['description' => 'Modérateur - Validation des documents et utilisateurs']
        );
        $moderator->syncPermissions([
            'view-users', 'approve-users', 'suspend-users',
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
            'view-users',
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

        $this->command->info('Rôles et permissions créés avec succès !');
        $this->command->info('');
        $this->command->info('Rôles créés :');
        $this->command->info('- super-admin : ' . $superAdmin->permissions->count() . ' permissions');
        $this->command->info('- admin : ' . $admin->permissions->count() . ' permissions');
        $this->command->info('- moderator : ' . $moderator->permissions->count() . ' permissions');
        $this->command->info('- support : ' . $support->permissions->count() . ' permissions');
        $this->command->info('- oauth-manager : ' . $oauthManager->permissions->count() . ' permissions');
    }
}
