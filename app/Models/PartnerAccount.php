<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * Compte d'une entreprise partenaire (guard 'partner', voir config/auth.php)
 * - distinct des comptes citoyens (User) : pas de KYC personnel, juste une
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
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function developerApplications(): HasMany
    {
        return $this->hasMany(DeveloperApplication::class);
    }

    /**
     * Genere un code a 6 chiffres, valide 10 minutes, envoye par email a
     * chaque connexion (voir Public\PartnerAuthController). Hache comme un
     * mot de passe, jamais stocke en clair - le code retourne ici est la
     * seule fois ou il existe en clair, pour l'email.
     */
    public function generateTwoFactorCode(): string
    {
        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    /**
     * Verifie le code et le consomme (usage unique) - un code deja verifie
     * ou expire ne peut jamais etre reutilise.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_code || ! $this->two_factor_expires_at || $this->two_factor_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($code, $this->two_factor_code)) {
            return false;
        }

        $this->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        return true;
    }
}
