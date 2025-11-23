<?php
require __DIR__ . '/vendor/autoload.php';

$google2fa = new \PragmaRX\Google2FA\Google2FA();
$secret = 'NTYNEEFBVN2AN3Z7EGW7DWS7ZK4ZF5JY';

echo "SECRET: {$secret}\n";
echo "CODE ACTUEL: " . $google2fa->getCurrentOtp($secret) . "\n";
echo "HEURE UTC: " . gmdate('H:i:s') . "\n";
