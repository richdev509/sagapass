<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Document permissions
            'verify-documents',
            'view-documents',

            // Citizen permissions
            'manage-citizens',
            'view-citizens',
            'suspend-citizens',

            // Admin permissions
            'manage-admins',
            'view-admins',

            // OAuth client permissions
            'manage-oauth-clients',
            'view-oauth-clients',

            // Audit log permissions
            'view-audit-logs',
            'export-audit-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'admin']);
        }

        // Create roles and assign permissions

        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin Verifier - only document verification
        $verifier = Role::create(['name' => 'Admin Verifier', 'guard_name' => 'admin']);
        $verifier->givePermissionTo([
            'verify-documents',
            'view-documents',
        ]);

        // Admin Support - citizen management
        $support = Role::create(['name' => 'Admin Support', 'guard_name' => 'admin']);
        $support->givePermissionTo([
            'view-documents',
            'manage-citizens',
            'view-citizens',
            'suspend-citizens',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
