<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class AssignSuperAdmin extends Command
{
    protected $signature = 'admin:make-super {email}';
    protected $description = 'Attribuer le rôle super-admin à un administrateur';

    public function handle()
    {
        $email = $this->argument('email');

        $admin = Admin::where('email', $email)->first();

        if (!$admin) {
            $this->error("Aucun administrateur trouvé avec l'email: {$email}");
            return 1;
        }

        $admin->assignRole('super-admin');

        $this->info("✅ Rôle super-admin attribué à: {$admin->name} ({$admin->email})");
        $this->info("Permissions totales: " . $admin->getAllPermissions()->count());

        return 0;
    }
}
