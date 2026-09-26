<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Compte d'une entreprise partenaire (guard 'partner', voir config/auth.php)
 * — distinct des comptes citoyens (User) : pas de KYC personnel, juste une
 * identité de connexion pour gérer ses DeveloperApplication (demande de
 * partenariat + tableau de bord, voir Public\PartnerApplicationController /
 * Partner\DashboardController).
 */
class PartnerAccount extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function developerApplications(): HasMany
    {
        return $this->hasMany(DeveloperApplication::class);
    }
}
