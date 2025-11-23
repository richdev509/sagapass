<?php

require __DIR__ . '/vendor/autoload.php';

$google2fa = new \PragmaRX\Google2FA\Google2FA();

// Générer un secret de test
$secret = $google2fa->generateSecretKey();
echo "Secret: " . $secret . PHP_EOL;

// Obtenir le code actuel
$currentCode = $google2fa->getCurrentOtp($secret);
echo "Code actuel (généré par serveur): " . $currentCode . PHP_EOL;

// Vérifier que le code fonctionne
$isValid = $google2fa->verifyKey($secret, $currentCode, 2);
echo "Validation directe: " . ($isValid ? 'OK ✓' : 'FAIL ✗') . PHP_EOL;

echo PHP_EOL;
echo "Entrez le code affiché dans votre Google Authenticator: ";
$userCode = trim(fgets(STDIN));

// Test avec le code utilisateur et le secret en session
echo PHP_EOL;
echo "Test avec votre code: " . $userCode . PHP_EOL;

// Simuler le secret qui devrait être dans la session
// IMPORTANT: Allez sur /admin/two-factor/enable et copiez le secret affiché
echo "Collez le secret affiché sur la page d'activation: ";
$sessionSecret = trim(fgets(STDIN));

$isValidUser = $google2fa->verifyKey($sessionSecret, $userCode, 2);
echo "Résultat: " . ($isValidUser ? 'VALIDE ✓' : 'INVALIDE ✗') . PHP_EOL;

if (!$isValidUser) {
    echo PHP_EOL;
    echo "Diagnostic:" . PHP_EOL;
    echo "- Longueur du secret: " . strlen($sessionSecret) . " (devrait être 32)" . PHP_EOL;
    echo "- Longueur du code: " . strlen($userCode) . " (devrait être 6)" . PHP_EOL;
    echo "- Timestamp serveur: " . time() . PHP_EOL;
    echo "- Heure serveur: " . date('Y-m-d H:i:s') . PHP_EOL;

    // Générer le code attendu
    $expectedCode = $google2fa->getCurrentOtp($sessionSecret);
    echo "- Code attendu par le serveur: " . $expectedCode . PHP_EOL;
}
