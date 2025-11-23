<?php

require __DIR__ . '/vendor/autoload.php';

// Utiliser le même secret que celui en session actuellement
echo "Entrez le SECRET affiché sur votre page web d'activation 2FA : ";
$secret = trim(fgets(STDIN));

if (strlen($secret) !== 32) {
    die("ERREUR: Le secret doit faire 32 caractères. Vous avez entré " . strlen($secret) . " caractères.\n");
}

echo "\n=== TEST EN TEMPS RÉEL ===\n\n";

$google2fa = new \PragmaRX\Google2FA\Google2FA();

echo "Secret utilisé: {$secret}\n";
echo "Heure UTC     : " . gmdate('Y-m-d H:i:s') . "\n";
echo "Timestamp     : " . time() . "\n\n";

echo "CODE ACTUEL ATTENDU PAR LE SERVEUR : ";
$currentCode = $google2fa->getCurrentOtp($secret);
echo "\033[1;32m" . $currentCode . "\033[0m\n\n";

echo "Codes valides dans la fenêtre de tolérance (±2 minutes):\n";
$timestamp = time();
for ($i = -4; $i <= 4; $i++) {
    $ts = $timestamp + ($i * 30);
    $code = $google2fa->oathTotp($secret, $ts);
    $time = gmdate('H:i:s', $ts);
    $marker = $i === 0 ? ' ← MAINTENANT' : '';
    echo "  {$time} UTC → {$code}{$marker}\n";
}

echo "\n";
echo "Entrez le code affiché dans Google Authenticator : ";
$userCode = trim(fgets(STDIN));

echo "\n";

// Vérifier avec différentes fenêtres de tolérance
$windows = [0, 1, 2, 4, 8];
foreach ($windows as $window) {
    $isValid = $google2fa->verifyKey($secret, $userCode, $window);
    $tolerance = $window * 30;
    $status = $isValid ? "\033[1;32m✓ VALIDE\033[0m" : "\033[1;31m✗ INVALIDE\033[0m";
    echo "Tolérance ±{$tolerance}s (window={$window}) : {$status}\n";
}

echo "\n";

if ($google2fa->verifyKey($secret, $userCode, 8)) {
    echo "\033[1;32m✓ SUCCÈS : Le code est valide !\033[0m\n";
    echo "Le système fonctionne correctement.\n";
} else {
    echo "\033[1;31m✗ ÉCHEC : Le code n'est jamais valide, même avec ±4 minutes.\033[0m\n";
    echo "Problème possible :\n";
    echo "  1. Secret incorrect (pas le même entre serveur et app)\n";
    echo "  2. Décalage horaire > 4 minutes\n";
    echo "  3. Mauvaise saisie du code\n";
}

echo "\n";
