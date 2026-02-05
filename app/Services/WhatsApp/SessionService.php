<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SessionService
{
    protected $sessionPrefix = 'whatsapp_session:';
    protected $sessionLifetime;

    public function __construct()
    {
        $this->sessionLifetime = config('whatsapp.session_lifetime', 15); // minutes
    }

    /**
     * Obtenir ou créer une session
     *
     * @param string $phoneNumber
     * @return array
     */
    public function getOrCreateSession(string $phoneNumber): array
    {
        $session = $this->getSession($phoneNumber);

        if (!$session) {
            $session = $this->createSession($phoneNumber);
        }

        return $session;
    }

    /**
     * Obtenir une session existante
     *
     * @param string $phoneNumber
     * @return array|null
     */
    public function getSession(string $phoneNumber): ?array
    {
        $key = $this->getSessionKey($phoneNumber);
        $session = Cache::get($key);

        if (!$session) {
            return null;
        }

        // Vérifier l'expiration
        if ($this->isExpired($session)) {
            $this->destroySession($phoneNumber);
            return null;
        }

        return $session;
    }

    /**
     * Créer une nouvelle session
     *
     * @param string $phoneNumber
     * @param array $context
     * @return array
     */
    public function createSession(string $phoneNumber, array $context = []): array
    {
        $now = Carbon::now();

        $session = [
            'phone_number' => $phoneNumber,
            'current_menu' => 'main',
            'context' => $context,
            'created_at' => $now->toIso8601String(),
            'last_activity' => $now->toIso8601String(),
            'expires_at' => $now->addMinutes($this->sessionLifetime)->toIso8601String(),
        ];

        $this->saveSession($phoneNumber, $session);

        return $session;
    }

    /**
     * Mettre à jour une session
     *
     * @param string $phoneNumber
     * @param string|null $currentMenu
     * @param array|null $context
     * @return array
     */
    public function updateSession(
        string $phoneNumber,
        ?string $currentMenu = null,
        ?array $context = null
    ): array {
        $session = $this->getOrCreateSession($phoneNumber);

        $now = Carbon::now();

        if ($currentMenu !== null) {
            $session['current_menu'] = $currentMenu;
        }

        if ($context !== null) {
            $session['context'] = array_merge($session['context'] ?? [], $context);
        }

        $session['last_activity'] = $now->toIso8601String();
        $session['expires_at'] = $now->addMinutes($this->sessionLifetime)->toIso8601String();

        $this->saveSession($phoneNumber, $session);

        return $session;
    }

    /**
     * Sauvegarder une session
     *
     * @param string $phoneNumber
     * @param array $session
     * @return void
     */
    protected function saveSession(string $phoneNumber, array $session)
    {
        $key = $this->getSessionKey($phoneNumber);

        // Expiration avec une marge de sécurité
        $ttl = $this->sessionLifetime + 5; // +5 minutes de marge

        Cache::put($key, $session, now()->addMinutes($ttl));
    }

    /**
     * Détruire une session
     *
     * @param string $phoneNumber
     * @return void
     */
    public function destroySession(string $phoneNumber)
    {
        $key = $this->getSessionKey($phoneNumber);
        Cache::forget($key);
    }

    /**
     * Vérifier si une session existe
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function hasSession(string $phoneNumber): bool
    {
        return $this->getSession($phoneNumber) !== null;
    }

    /**
     * Vérifier si une session est expirée
     *
     * @param array $session
     * @return bool
     */
    protected function isExpired(array $session): bool
    {
        if (!isset($session['expires_at'])) {
            return true;
        }

        $expiresAt = Carbon::parse($session['expires_at']);
        return Carbon::now()->greaterThan($expiresAt);
    }

    /**
     * Obtenir le contexte de la session
     *
     * @param string $phoneNumber
     * @param string|null $key
     * @return mixed
     */
    public function getContext(string $phoneNumber, ?string $key = null)
    {
        $session = $this->getSession($phoneNumber);

        if (!$session) {
            return null;
        }

        $context = $session['context'] ?? [];

        if ($key === null) {
            return $context;
        }

        return $context[$key] ?? null;
    }

    /**
     * Définir un élément de contexte
     *
     * @param string $phoneNumber
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setContext(string $phoneNumber, string $key, $value)
    {
        $session = $this->getOrCreateSession($phoneNumber);

        $context = $session['context'] ?? [];
        $context[$key] = $value;

        $this->updateSession($phoneNumber, null, $context);
    }

    /**
     * Obtenir le menu actuel
     *
     * @param string $phoneNumber
     * @return string
     */
    public function getCurrentMenu(string $phoneNumber): string
    {
        $session = $this->getSession($phoneNumber);
        return $session['current_menu'] ?? 'main';
    }

    /**
     * Nettoyer les sessions expirées
     *
     * @return int Nombre de sessions nettoyées
     */
    public function cleanupExpiredSessions(): int
    {
        // Cette méthode peut être appelée par un job schedulé
        // Pour l'instant, on utilise l'expiration automatique de Cache

        // Si on veut tracker le nombre de sessions nettoyées,
        // il faudrait stocker la liste des clés de session

        return 0;
    }

    /**
     * Obtenir toutes les sessions actives (pour monitoring)
     *
     * @return array
     */
    public function getActiveSessions(): array
    {
        // Cette méthode nécessiterait de maintenir un index des sessions
        // Pour le MVP, on peut la laisser vide

        return [];
    }

    /**
     * Obtenir la durée de vie restante d'une session (en minutes)
     *
     * @param string $phoneNumber
     * @return int|null
     */
    public function getTimeToLive(string $phoneNumber): ?int
    {
        $session = $this->getSession($phoneNumber);

        if (!$session || !isset($session['expires_at'])) {
            return null;
        }

        $expiresAt = Carbon::parse($session['expires_at']);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($expiresAt)) {
            return 0;
        }

        return $now->diffInMinutes($expiresAt);
    }

    /**
     * Prolonger une session
     *
     * @param string $phoneNumber
     * @param int|null $additionalMinutes
     * @return void
     */
    public function extendSession(string $phoneNumber, ?int $additionalMinutes = null)
    {
        $session = $this->getSession($phoneNumber);

        if (!$session) {
            return;
        }

        $minutes = $additionalMinutes ?? $this->sessionLifetime;
        $newExpiresAt = Carbon::now()->addMinutes($minutes);

        $session['expires_at'] = $newExpiresAt->toIso8601String();

        $this->saveSession($phoneNumber, $session);
    }

    /**
     * Obtenir la clé de cache pour une session
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function getSessionKey(string $phoneNumber): string
    {
        // Normaliser le numéro de téléphone
        $normalized = preg_replace('/[^0-9+]/', '', $phoneNumber);
        return $this->sessionPrefix . $normalized;
    }

    /**
     * Obtenir des statistiques sur les sessions (pour monitoring)
     *
     * @return array
     */
    public function getStats(): array
    {
        // Pour le MVP, retourner des stats basiques
        // En production, on pourrait tracker plus de métriques

        return [
            'session_lifetime_minutes' => $this->sessionLifetime,
            'active_sessions' => count($this->getActiveSessions()),
        ];
    }
}
