<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Exception;

class EncryptionService
{
    // Clé AES-256 = EXACTEMENT 32 bytes
    // Lire depuis .env en priorité, sinon utiliser la clé par défaut
    // SAGAID_2026_SECURE_32BYTE_APPKEY = 32 caractères (vérifiés)
    private string $masterKey;

    // Cipher method pour OpenSSL
    private const CIPHER_METHOD = 'AES-256-CBC';

    public function __construct()
    {
        $this->masterKey = env('APP_ENCRYPTION_KEY', 'SAGAID_2026_SECURE_32BYTE_APPKEY');

        // Vérifier la longueur de la clé
        if (strlen($this->masterKey) !== 32) {
            throw new \RuntimeException(
                'ERREUR SÉCURITÉ: La clé APP_ENCRYPTION_KEY doit faire exactement 32 caractères pour AES-256! '
                . 'Actuelle: ' . strlen($this->masterKey) . ' caractères.'
            );
        }
    }

    /**
     * Décrypter une chaîne cryptée par Flutter
     * Format attendu: base64(IV):base64(encrypted_data)
     */
    public function decrypt(string $encryptedText): string
    {
        try {
            // Séparer IV et données
            $parts = explode(':', $encryptedText);

            if (count($parts) !== 2) {
                throw new Exception('Format de données cryptées invalide');
            }

            $iv = base64_decode($parts[0]);
            $encryptedData = base64_decode($parts[1]);

            if ($iv === false || $encryptedData === false) {
                throw new Exception('Erreur de décodage base64');
            }

            // Décrypter avec OpenSSL
            $decrypted = openssl_decrypt(
                $encryptedData,
                self::CIPHER_METHOD,
                $this->masterKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception('Erreur de décryptage: ' . openssl_error_string());
            }

            return $decrypted;

        } catch (Exception $e) {
            throw new Exception('Erreur lors du décryptage: ' . $e->getMessage());
        }
    }

    /**
     * Crypter une chaîne (pour tests ou réponses)
     */
    public function encrypt(string $plainText): string
    {
        try {
            // Générer un IV aléatoire (16 bytes)
            $iv = openssl_random_pseudo_bytes(16);

            // Crypter
            $encrypted = openssl_encrypt(
                $plainText,
                self::CIPHER_METHOD,
                $this->masterKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new Exception('Erreur de cryptage: ' . openssl_error_string());
            }

            // Retourner: IV + données cryptées (séparés par :)
            return base64_encode($iv) . ':' . base64_encode($encrypted);

        } catch (Exception $e) {
            throw new Exception('Erreur lors du cryptage: ' . $e->getMessage());
        }
    }

    /**
     * Décrypter une image base64 cryptée
     */
    public function decryptImage(string $encryptedImage): string
    {
        return $this->decrypt($encryptedImage);
    }

    /**
     * Créer une signature HMAC pour vérifier l'intégrité
     */
    public function createSignature(array $data, string $timestamp): string
    {
        // Convertir les données en JSON
        // JSON_UNESCAPED_SLASHES est OBLIGATOIRE pour matcher le comportement de Dart json.encode()
        // Dart ne fait PAS d'escape sur '/', PHP le fait par défaut → HMAC différent sinon
        // JSON_UNESCAPED_UNICODE pour cohérence avec Dart également
        $jsonString = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Créer le message à signer: timestamp + json
        $message = $timestamp . ':' . $jsonString;

        // Créer HMAC-SHA256
        return hash_hmac('sha256', $message, $this->masterKey);
    }

    /**
     * Vérifier une signature HMAC
     */
    public function verifySignature(array $data, string $timestamp, string $signature): bool
    {
        $expectedSignature = $this->createSignature($data, $timestamp);

        // Comparaison sécurisée contre les timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vérifier le nonce pour éviter les replay attacks
     * Le nonce doit être stocké temporairement en cache
     */
    public function verifyNonce(string $nonce, int $maxAgeMinutes = 10): bool
    {
        // Le nonce a le format: {milliseconds_timestamp}:{base64_random}
        // Le ':' est le seul séparateur car base64 n'utilise pas ':'
        $colonPos = strpos($nonce, ':');

        if ($colonPos === false) {
            return false;
        }

        $timestampMs = (int) substr($nonce, 0, $colonPos);
        $nowMs = (int) (now()->timestamp * 1000);
        $maxAgeMs = $maxAgeMinutes * 60 * 1000;

        // Vérifier que le nonce n'est pas trop vieux
        if (($nowMs - $timestampMs) > $maxAgeMs) {
            return false;
        }

        // Prévention anti-replay: stocker le nonce en cache pendant sa durée de vie
        $cacheKey = 'nonce_used:' . hash('sha256', $nonce);

        if (Cache::has($cacheKey)) {
            // Nonce déjà utilisé !
            return false;
        }

        // Marquer le nonce comme utilisé
        Cache::put($cacheKey, true, $maxAgeMinutes * 60);

        return true;
    }

    /**
     * Décrypter et valider les données sensibles reçues
     *
     * @param array $requestData Les données de la requête
     * @return array Les données décryptées
     * @throws Exception Si la validation échoue
     */
    public function decryptAndValidateRequest(array $requestData): array
    {
        // Vérifier que c'est bien une requête cryptée
        // JSON boolean true peut arriver comme bool ou comme string '1'
        $isEncrypted = isset($requestData['encrypted']) &&
                       filter_var($requestData['encrypted'], FILTER_VALIDATE_BOOLEAN);

        if (!$isEncrypted) {
            throw new Exception('Requête non cryptée détectée');
        }

        // Vérifier la présence des métadonnées de sécurité
        if (!isset($requestData['timestamp']) || !isset($requestData['signature']) || !isset($requestData['nonce'])) {
            throw new Exception('Métadonnées de sécurité manquantes');
        }

        $timestamp = $requestData['timestamp'];
        $signature = $requestData['signature'];
        $nonce = $requestData['nonce'];

        // Vérifier le nonce (anti-replay)
        if (!$this->verifyNonce($nonce)) {
            throw new Exception('Nonce invalide ou expiré');
        }

        // Créer l'objet de données pour la signature
        $dataForSignature = [
            'id_card_front' => $requestData['id_card_front'],
            'id_card_back' => $requestData['id_card_back'],
            'selfie' => $requestData['selfie'],
            'nonce' => $nonce,
        ];

        // Vérifier la signature
        if (!$this->verifySignature($dataForSignature, $timestamp, $signature)) {
            throw new Exception('Signature invalide - données possiblement altérées');
        }

        // Décrypter les images
        $decryptedData = [
            'id_card_front' => $this->decryptImage($requestData['id_card_front']),
            'id_card_back' => $this->decryptImage($requestData['id_card_back']),
            'selfie' => $this->decryptImage($requestData['selfie']),
        ];

        // Retourner les données décryptées + données non sensibles
        return array_merge($requestData, $decryptedData);
    }

    /**
     * Hash une valeur (utile pour mots de passe ou vérifications)
     */
    public function hash(string $input): string
    {
        return hash('sha256', $input);
    }
}
