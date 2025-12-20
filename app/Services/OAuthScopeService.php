<?php

namespace App\Services;

class OAuthScopeService
{
    /**
     * Liste des scopes disponibles dans SAGAPASS OAuth
     *
     * Les scopes standard sont accessibles à tous les développeurs.
     * Les scopes "partner:" sont réservés aux partenaires approuvés.
     */
    public const AVAILABLE_SCOPES = [
        // Scopes standard (tous les développeurs)
        'profile' => [
            'name' => 'Profil de base',
            'description' => 'Nom, prénom, photo, statut de vérification',
            'category' => 'standard',
        ],
        'email' => [
            'name' => 'Adresse email',
            'description' => 'Adresse email et statut de vérification',
            'category' => 'standard',
        ],
        'phone' => [
            'name' => 'Numéro de téléphone',
            'description' => 'Numéro de téléphone et statut de vérification',
            'category' => 'standard',
        ],
        'address' => [
            'name' => 'Adresse postale',
            'description' => 'Adresse complète du citoyen',
            'category' => 'standard',
        ],
        'birthdate' => [
            'name' => 'Date de naissance',
            'description' => 'Date de naissance du citoyen',
            'category' => 'standard',
        ],
        'photo' => [
            'name' => 'Photo de profil',
            'description' => 'URL de la photo de profil',
            'category' => 'standard',
        ],
        'documents' => [
            'name' => 'Documents d\'identité',
            'description' => 'Informations sur les documents vérifiés (sans images)',
            'category' => 'standard',
        ],

        // Scopes partenaire (réservés)
        'partner:create-citizen' => [
            'name' => 'Création de citoyens',
            'description' => 'Créer automatiquement des comptes citoyens SAGAPASS via API',
            'category' => 'partner',
            'requires_approval' => true,
        ],
        'partner:verify-citizen' => [
            'name' => 'Vérification de citoyens',
            'description' => 'Vérifier le statut et les informations d\'un citoyen',
            'category' => 'partner',
            'requires_approval' => true,
        ],
    ];

    /**
     * Obtenir tous les scopes disponibles
     */
    public static function getAllScopes(): array
    {
        return array_keys(self::AVAILABLE_SCOPES);
    }

    /**
     * Obtenir les scopes standard (pour développeurs normaux)
     */
    public static function getStandardScopes(): array
    {
        return array_filter(self::AVAILABLE_SCOPES, function ($scope) {
            return $scope['category'] === 'standard';
        });
    }

    /**
     * Obtenir les scopes partenaire (réservés)
     */
    public static function getPartnerScopes(): array
    {
        return array_filter(self::AVAILABLE_SCOPES, function ($scope) {
            return $scope['category'] === 'partner';
        });
    }

    /**
     * Obtenir la description d'un scope
     */
    public static function getScopeDescription(string $scope): string
    {
        return self::AVAILABLE_SCOPES[$scope]['description'] ?? 'Scope inconnu';
    }

    /**
     * Vérifier si un scope est valide
     */
    public static function isValidScope(string $scope): bool
    {
        return isset(self::AVAILABLE_SCOPES[$scope]);
    }

    /**
     * Vérifier si un scope est un scope partenaire
     */
    public static function isPartnerScope(string $scope): bool
    {
        return isset(self::AVAILABLE_SCOPES[$scope]) &&
               self::AVAILABLE_SCOPES[$scope]['category'] === 'partner';
    }

    /**
     * Obtenir les scopes formatés pour un formulaire
     */
    public static function getScopesForForm(bool $includePartner = false): array
    {
        $scopes = $includePartner ? self::AVAILABLE_SCOPES : self::getStandardScopes();

        $formatted = [];
        foreach ($scopes as $key => $data) {
            $formatted[$key] = $data['name'] . ' - ' . $data['description'];
        }

        return $formatted;
    }
}
