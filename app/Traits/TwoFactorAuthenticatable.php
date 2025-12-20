<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

trait TwoFactorAuthenticatable
{
    /**
     * Déterminer si le 2FA est activé
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Générer un nouveau secret 2FA
     */
    public function generateTwoFactorSecret(): string
    {
        $google2fa = new Google2FA();
        return $google2fa->generateSecretKey();
    }

    /**
     * Obtenir l'URL du QR Code pour Google Authenticator
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        $google2fa = new Google2FA();
        $companyName = config('app.name');
        $email = $this->email;

        return $google2fa->getQRCodeUrl(
            $companyName,
            $email,
            $this->two_factor_secret
        );
    }

    /**
     * Vérifier un code 2FA avec fenêtre de tolérance élargie
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->hasTwoFactorEnabled()) {
            return false;
        }

        $google2fa = new Google2FA();
        $secret = $this->getDecryptedTwoFactorSecret();

        if (!$secret) {
            return false;
        }

        // Fenêtre de tolérance de 10 intervalles (±5 minutes)
        // pour gérer les décalages horaires importants entre serveur et smartphone
        return $google2fa->verifyKey($secret, $code, 10);
    }

    /**
     * Générer des codes de récupération
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(10));
        }
        return $codes;
    }

    /**
     * Enregistrer les codes de récupération (hashés)
     */
    public function storeRecoveryCodes(array $codes): void
    {
        $hashedCodes = collect($codes)->map(function ($code) {
            return hash('sha256', $code);
        })->toArray();

        $this->two_factor_recovery_codes = json_encode($hashedCodes);
        $this->save();
    }

    /**
     * Obtenir les codes de récupération déchiffrés
     */
    public function getRecoveryCodes(): Collection
    {
        if (!$this->two_factor_recovery_codes) {
            return collect([]);
        }

        return collect(json_decode($this->two_factor_recovery_codes, true));
    }

    /**
     * Vérifier un code de récupération
     */
    public function verifyRecoveryCode(string $code): bool
    {
        $hashedCode = hash('sha256', strtoupper($code));
        $recoveryCodes = $this->getRecoveryCodes();

        if ($recoveryCodes->contains($hashedCode)) {
            // Supprimer le code utilisé
            $remainingCodes = $recoveryCodes->reject(function ($storedCode) use ($hashedCode) {
                return $storedCode === $hashedCode;
            })->values();

            $this->two_factor_recovery_codes = json_encode($remainingCodes->toArray());
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Activer le 2FA
     */
    public function enableTwoFactor(string $secret, array $recoveryCodes): void
    {
        $this->two_factor_secret = encrypt($secret);
        $this->two_factor_confirmed_at = now();
        $this->save();

        $this->storeRecoveryCodes($recoveryCodes);
    }

    /**
     * Désactiver le 2FA
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    /**
     * Obtenir le secret déchiffré
     */
    public function getDecryptedTwoFactorSecret(): ?string
    {
        if (!$this->two_factor_secret) {
            return null;
        }

        try {
            $secret = decrypt($this->two_factor_secret);
            
            // Valider que le secret a au moins 16 caractères (requis par Google2FA)
            if (empty($secret) || strlen($secret) < 16) {
                \Log::warning('2FA secret too short for user', [
                    'user_id' => $this->id,
                    'secret_length' => strlen($secret ?? '')
                ]);
                return null;
            }
            
            return $secret;
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt 2FA secret', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
