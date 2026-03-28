<?php

namespace App\Services\Sagaloto;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SagalotoApiService
{
    private ?string $baseUrl;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.sagaloto.api_url');
        $this->apiKey = config('services.sagaloto.api_key');
    }

    /**
     * Récupère les branches disponibles pour un utilisateur Telegram
     *
     * @param string $telegramUsername Le username Telegram (sans @)
     * @return array|null
     */
    public function getUserBranches(string $telegramUsername): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/api/branches', [
                'telegram_username' => $telegramUsername,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sagaloto API error - getUserBranches', [
                'telegram_username' => $telegramUsername,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sagaloto API exception - getUserBranches', [
                'telegram_username' => $telegramUsername,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Récupère le rapport pour un tirage spécifique
     *
     * @param string $telegramUsername Le username Telegram (sans @)
     * @param int $branchId L'ID de la branche sélectionnée
     * @param string $periode La période (matin, apres_midi, soir)
     * @param string $tirage Le nom du tirage (tennessee, texas, georgia, florida, new_york)
     * @param string $type Le type de rapport (rapport ou tirage)
     * @return array|null
     */
    public function getRapport(
        string $telegramUsername,
        int $branchId,
        string $periode,
        string $tirage,
        string $type = 'rapport'
    ): ?array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/rapports', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
                'tirage' => $tirage,
                'type' => $type,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sagaloto API error - getRapport', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
                'tirage' => $tirage,
                'type' => $type,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sagaloto API exception - getRapport', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
                'tirage' => $tirage,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Récupère les statistiques de ventes
     *
     * @param string $telegramUsername Le username Telegram (sans @)
     * @param int $branchId L'ID de la branche sélectionnée
     * @param string $periode La période (jour ou semaine)
     * @return array|null
     */
    public function getVentes(
        string $telegramUsername,
        int $branchId,
        string $periode
    ): ?array {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/ventes', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sagaloto API error - getVentes', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sagaloto API exception - getVentes', [
                'telegram_username' => $telegramUsername,
                'branch_id' => $branchId,
                'periode' => $periode,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
