<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TelegramSessionService
{
    protected $sessionPrefix = 'telegram_session:';
    protected int $sessionLifetime;

    public function __construct()
    {
        // Cast explicite vers int pour éviter le TypeError avec Carbon
        $this->sessionLifetime = (int) config('telegram.session_lifetime', 15); // minutes
    }

    /**
     * Obtenir ou créer une session
     *
     * @param string $userId
     * @return array
     */
    public function getOrCreateSession(string $userId): array
    {
        $session = $this->getSession($userId);

        if (!$session) {
            $session = $this->createSession($userId);
        }

        return $session;
    }

    /**
     * Obtenir une session existante
     *
     * @param string $userId
     * @return array|null
     */
    public function getSession(string $userId): ?array
    {
        $key = $this->getSessionKey($userId);
        $session = Cache::get($key);

        if (!$session) {
            return null;
        }

        // Vérifier l'expiration
        if ($this->isExpired($session)) {
            $this->destroySession($userId);
            return null;
        }

        return $session;
    }

    /**
     * Créer une nouvelle session
     *
     * @param string $userId
     * @param array $context
     * @return array
     */
    public function createSession(string $userId, array $context = []): array
    {
        $now = Carbon::now();

        $session = [
            'user_id' => $userId,
            'current_menu' => 'main',
            'context' => $context,
            'created_at' => $now->toIso8601String(),
            'last_activity' => $now->toIso8601String(),
            'expires_at' => $now->copy()->addMinutes($this->sessionLifetime)->toIso8601String(),
        ];

        $this->saveSession($userId, $session);

        return $session;
    }

    /**
     * Mettre à jour une session
     *
     * @param string $userId
     * @param string|null $currentMenu
     * @param array|null $context
     * @return array
     */
    public function updateSession(
        string $userId,
        ?string $currentMenu = null,
        ?array $context = null
    ): array {
        $session = $this->getOrCreateSession($userId);

        $now = Carbon::now();

        if ($currentMenu !== null) {
            $session['current_menu'] = $currentMenu;
        }

        if ($context !== null) {
            $session['context'] = array_merge($session['context'] ?? [], $context);
        }

        $session['last_activity'] = $now->toIso8601String();
        $session['expires_at'] = $now->copy()->addMinutes($this->sessionLifetime)->toIso8601String();

        $this->saveSession($userId, $session);

        return $session;
    }

    /**
     * Mettre à jour le contexte de la session
     *
     * @param string $userId
     * @param array $context
     * @return array
     */
    public function updateContext(string $userId, array $context): array
    {
        return $this->updateSession($userId, null, $context);
    }

    /**
     * Changer le menu actuel
     *
     * @param string $userId
     * @param string $menu
     * @return array
     */
    public function setCurrentMenu(string $userId, string $menu): array
    {
        return $this->updateSession($userId, $menu);
    }

    /**
     * Obtenir le menu actuel
     *
     * @param string $userId
     * @return string|null
     */
    public function getCurrentMenu(string $userId): ?string
    {
        $session = $this->getSession($userId);
        return $session['current_menu'] ?? null;
    }

    /**
     * Obtenir le contexte de la session
     *
     * @param string $userId
     * @return array
     */
    public function getContext(string $userId): array
    {
        $session = $this->getSession($userId);
        return $session['context'] ?? [];
    }

    /**
     * Sauvegarder la session dans le cache
     *
     * @param string $userId
     * @param array $session
     * @return void
     */
    protected function saveSession(string $userId, array $session): void
    {
        $key = $this->getSessionKey($userId);
        Cache::put($key, $session, now()->addMinutes($this->sessionLifetime));
    }

    /**
     * Détruire une session
     *
     * @param string $userId
     * @return void
     */
    public function destroySession(string $userId): void
    {
        $key = $this->getSessionKey($userId);
        Cache::forget($key);
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
        return $expiresAt->isPast();
    }

    /**
     * Obtenir la clé de session
     *
     * @param string $userId
     * @return string
     */
    protected function getSessionKey(string $userId): string
    {
        return $this->sessionPrefix . $userId;
    }

    /**
     * Réinitialiser la session au menu principal
     *
     * @param string $userId
     * @return array
     */
    public function resetToMainMenu(string $userId): array
    {
        return $this->updateSession($userId, 'main', []);
    }

    /**
     * Vérifier si une session existe
     *
     * @param string $userId
     * @return bool
     */
    public function hasSession(string $userId): bool
    {
        return $this->getSession($userId) !== null;
    }

    /**
     * Prolonger la durée de vie de la session
     *
     * @param string $userId
     * @return void
     */
    public function extendSession(string $userId): void
    {
        $session = $this->getSession($userId);
        if ($session) {
            $this->updateSession($userId);
        }
    }
}
