<?php

namespace App\Exceptions;

use RuntimeException;

class FaceVerificationUnavailableException extends RuntimeException
{
    public static function fromProcessFailure(string $errorOutput): self
    {
        $message = trim($errorOutput) !== '' ? trim($errorOutput) : 'Aucune sortie d\'erreur.';

        return new self("Le script d'analyse de document/visage a échoué : {$message}");
    }

    public static function fromUnparsableOutput(string $output): self
    {
        $preview = trim($output) !== '' ? substr(trim($output), 0, 200) : 'sortie vide';

        return new self("Le script d'analyse a renvoyé une sortie JSON invalide : {$preview}");
    }
}
