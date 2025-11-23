<?php

require __DIR__ . '/vendor/autoload.php';

echo "=== DIAGNOSTIC TOTP COMPLET ===\n\n";

$google2fa = new \PragmaRX\Google2FA\Google2FA();

// 1. Générer un secret
$secret = $google2fa->generateSecretKey();
echo "1. SECRET GÉNÉRÉ\n";
echo "   Secret: {$secret}\n";
echo "   Longueur: " . strlen($secret) . " caractères\n";
echo "   Base32: " . (ctype_alnum($secret) ? "✓ OUI" : "✗ NON") . "\n\n";

// 2. Vérifier le format du secret
echo "2. VALIDATION DU SECRET\n";
$secretClean = str_replace(' ', '', trim($secret));
echo "   Secret nettoyé: {$secretClean}\n";
echo "   Identique: " . ($secret === $secretClean ? "✓ OUI" : "✗ NON - PROBLÈME!") . "\n\n";

// 3. Construire l'URL TOTP manuellement
$issuer = 'SAGAPASS';
$label = 'admin@example.com';

echo "3. URL TOTP (FORMAT STANDARD)\n";
$manualUrl = sprintf(
    'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
    rawurlencode($issuer),
    rawurlencode($label),
    $secret, // Pas d'encoding pour Base32
    rawurlencode($issuer)
);
echo "   URL manuelle: {$manualUrl}\n\n";

// 4. URL générée par Google2FA
$google2faUrl = $google2fa->getQRCodeUrl($issuer, $label, $secret);
echo "4. URL GÉNÉRÉE PAR GOOGLE2FA\n";
echo "   URL Google2FA: {$google2faUrl}\n\n";

// 5. Comparer les deux
echo "5. COMPARAISON\n";
echo "   Identiques: " . ($manualUrl === $google2faUrl ? "✓ OUI" : "✗ NON") . "\n";
if ($manualUrl !== $google2faUrl) {
    echo "   DIFFÉRENCE:\n";
    echo "   - Manuelle  : {$manualUrl}\n";
    echo "   - Google2FA : {$google2faUrl}\n";
}
echo "\n";

// 6. Générer le code actuel
$currentCode = $google2fa->getCurrentOtp($secret);
echo "6. CODE TOTP ACTUEL\n";
echo "   Code: {$currentCode}\n";
echo "   Timestamp: " . time() . "\n";
echo "   UTC: " . gmdate('Y-m-d H:i:s') . "\n\n";

// 7. Tester la vérification
echo "7. TEST DE VÉRIFICATION\n";
$isValid = $google2fa->verifyKey($secret, $currentCode, 4);
echo "   Auto-vérification: " . ($isValid ? "✓ VALIDE" : "✗ INVALIDE") . "\n\n";

// 8. Afficher les codes des 5 prochains intervalles
echo "8. CODES TOTP DES PROCHAINS INTERVALLES (30s chacun)\n";
$timestamp = time();
for ($i = -2; $i <= 2; $i++) {
    $ts = $timestamp + ($i * 30);
    $code = $google2fa->oathTotp($secret, $ts);
    $label = $i === 0 ? '← ACTUEL' : ($i < 0 ? "(passé)" : "(futur)");
    echo "   " . gmdate('H:i:s', $ts) . " → {$code} {$label}\n";
}
echo "\n";

// 9. Instructions finales
echo "9. INSTRUCTIONS POUR LE TEST\n";
echo "   a) Copiez ce secret: {$secret}\n";
echo "   b) Dans Google Authenticator:\n";
echo "      - Ajoutez manuellement un compte\n";
echo "      - Nom: SAGAPASS\n";
echo "      - Clé: {$secret}\n";
echo "      - Type: Temporel\n";
echo "   c) Le code affiché devrait être: {$currentCode}\n";
echo "   d) Si différent, vérifiez l'heure de votre téléphone\n\n";

echo "=== FIN DU DIAGNOSTIC ===\n";
