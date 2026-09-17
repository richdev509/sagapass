<?php

namespace App\Console\Commands;

use App\Models\BlacklistedIdentity;
use App\Models\BlacklistedIdentityDocument;
use Illuminate\Console\Command;

/**
 * Gestion en ligne de commande de la liste de vigilance — pas d'interface
 * admin dédiée pour l'instant (voir BlacklistScreeningService pour la
 * comparaison faite automatiquement à l'analyse).
 */
class BlacklistIdentity extends Command
{
    protected $signature = 'blacklist:add
                            {first_name : Prénom}
                            {last_name : Nom}
                            {--dob= : Date de naissance (YYYY-MM-DD)}
                            {--reason= : Motif}
                            {--document=* : Pièce(s) au format type:numero, ex. national_id:1315632632 (répétable)}';

    protected $description = 'Ajoute une personne à la liste de vigilance, avec ses pièces connues';

    public function handle(): int
    {
        $identity = BlacklistedIdentity::create([
            'first_name' => $this->argument('first_name'),
            'last_name' => $this->argument('last_name'),
            'date_of_birth' => $this->option('dob'),
            'reason' => $this->option('reason'),
        ]);

        foreach ($this->option('document') as $entry) {
            [$type, $number] = array_pad(explode(':', $entry, 2), 2, null);

            if (! $type || ! $number) {
                $this->warn("Ignoré (format attendu type:numero) : {$entry}");
                continue;
            }

            BlacklistedIdentityDocument::create([
                'blacklisted_identity_id' => $identity->id,
                'document_type' => $type,
                'document_number' => $number,
            ]);
        }

        $this->info("Ajouté : {$identity->first_name} {$identity->last_name} (id {$identity->id}), " . count($this->option('document')) . ' pièce(s).');

        return Command::SUCCESS;
    }
}
