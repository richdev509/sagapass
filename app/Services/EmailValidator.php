<?php

namespace App\Services;

class EmailValidator
{
    /**
     * Domaines autorisés (whitelist)
     */
    private const ALLOWED_DOMAINS = [
        // Fournisseurs internationaux majeurs
        'gmail.com', 'googlemail.com',
        'yahoo.com', 'yahoo.fr',
        'outlook.com', 'hotmail.com', 'hotmail.fr',
        'live.com', 'live.fr', 'msn.com',
        'icloud.com', 'me.com', 'mac.com',
        'aol.com',
        'protonmail.com', 'proton.me', 'pm.me',
        'zoho.com', 'zohomail.com',

        // Fournisseurs français
        'orange.fr', 'wanadoo.fr',
        'free.fr',
        'sfr.fr',
        'laposte.net',
        'bbox.fr', 'bouygtel.fr',
        'neuf.fr',
        'cegetel.net',
        'club-internet.fr',
        'numericable.fr',
        'aliceadsl.fr',
    ];

    /**
     * Domaines bloqués (blacklist - emails jetables)
     */
    private const BLOCKED_DOMAINS = [
        'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        'guerrillamail.com', 'guerrillamailblock.com',
        '10minutemail.com', '10minutemail.net',
        'mailinator.com', 'maildrop.cc',
        'throwaway.email', 'trashmail.com',
        'yopmail.com', 'yopmail.fr', 'yopmail.net',
        'getnada.com', 'mohmal.com',
        'sharklasers.com', 'grr.la',
        'mailnesia.com', 'mintemail.com',
        'discard.email', 'fakeinbox.com',
        'tmpeml.info', 'mytemp.email',
        'spam4.me', 'getairmail.com',
        'mvrht.com', 'mailcatch.com',
    ];

    /**
     * Valider un email avec stratégie multi-niveaux
     *
     * @param string $email
     * @return array ['valid' => bool, 'reason' => string|null]
     */
    public static function validate(string $email): array
    {
        // 1. Vérification format de base
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'reason' => 'Le format de l\'adresse email est invalide.'
            ];
        }

        // 2. Extraire le domaine
        $domain = strtolower(substr(strrchr($email, "@"), 1));

        if (empty($domain)) {
            return [
                'valid' => false,
                'reason' => 'Le domaine de l\'email est invalide.'
            ];
        }

        // 3. Vérifier la blacklist (priorité haute)
        if (in_array($domain, self::BLOCKED_DOMAINS)) {
            return [
                'valid' => false,
                'reason' => 'Les adresses email temporaires ou jetables ne sont pas autorisées. Veuillez utiliser un email permanent.'
            ];
        }

        // 4. Vérifier la whitelist
        if (in_array($domain, self::ALLOWED_DOMAINS)) {
            return [
                'valid' => true,
                'reason' => null
            ];
        }

        // 5. Domaine inconnu → Vérifier MX record (emails professionnels)
        if (self::hasValidMxRecord($domain)) {
            return [
                'valid' => true,
                'reason' => null
            ];
        }

        // 6. Échec final
        return [
            'valid' => false,
            'reason' => 'Ce domaine email n\'est pas reconnu ou ne peut pas recevoir d\'emails. Veuillez utiliser un fournisseur email standard (Gmail, Yahoo, Outlook, etc.).'
        ];
    }

    /**
     * Vérifier si le domaine a un enregistrement MX valide
     *
     * @param string $domain
     * @return bool
     */
    private static function hasValidMxRecord(string $domain): bool
    {
        try {
            // Vérifier l'existence d'un enregistrement MX
            return checkdnsrr($domain, 'MX');
        } catch (\Exception $e) {
            // En cas d'erreur DNS, rejeter par sécurité
            return false;
        }
    }

    /**
     * Obtenir la liste des domaines autorisés (pour affichage)
     *
     * @return array
     */
    public static function getAllowedDomains(): array
    {
        return self::ALLOWED_DOMAINS;
    }

    /**
     * Vérifier si un domaine est dans la blacklist
     *
     * @param string $email
     * @return bool
     */
    public static function isBlockedDomain(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, self::BLOCKED_DOMAINS);
    }

    /**
     * Vérifier si un domaine est dans la whitelist
     *
     * @param string $email
     * @return bool
     */
    public static function isWhitelistedDomain(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, self::ALLOWED_DOMAINS);
    }
}
