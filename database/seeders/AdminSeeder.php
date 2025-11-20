<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@sagaid.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $superAdmin->assignRole('Super Admin');

        // Create Admin Verifier
        $verifier = Admin::create([
            'name' => 'Admin Verifier',
            'email' => 'verifier@sagaid.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $verifier->assignRole('Admin Verifier');

        // Create Admin Support
        $support = Admin::create([
            'name' => 'Admin Support',
            'email' => 'support@sagaid.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $support->assignRole('Admin Support');

        $this->command->info('Default admins created successfully!');
        $this->command->info('Super Admin: admin@sagaid.com / password');
        $this->command->info('Verifier: verifier@sagaid.com / password');
        $this->command->info('Support: support@sagaid.com / password');
    }
}
