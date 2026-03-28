<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\DeveloperApplication;
use Illuminate\Support\Str;

// Trouver ou créer un utilisateur test
$user = User::firstOrCreate(
    ['email' => 'test@sagapass.com'],
    [
        'first_name' => 'Test',
        'last_name' => 'Partner',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

// Créer une application OAuth test
$clientSecret = Str::random(60);

$app = DeveloperApplication::create([
    'user_id' => $user->id,
    'name' => 'KAYPA Test App',
    'description' => 'Application de test pour Partner API',
    'website' => 'https://kaypa.com',
    'client_id' => 'test_client_123',
    'client_secret' => bcrypt($clientSecret),
    'redirect_uris' => ['https://kaypa.com/callback'],
    'allowed_scopes' => ['partner:create-citizen', 'partner:verify-citizen'],
    'status' => 'approved',
    'is_trusted' => true,
    'approved_at' => now(),
]);

echo "✅ Application créée avec succès!\n";
echo "Client ID: test_client_123\n";
echo "Client Secret: {$clientSecret}\n";
echo "\nTestez avec:\n";
echo "POST http://127.0.0.1:8000/oauth/token\n";
echo "{\n";
echo '  "grant_type": "client_credentials",' . "\n";
echo '  "client_id": "test_client_123",' . "\n";
echo '  "client_secret": "' . $clientSecret . '",' . "\n";
echo '  "scope": "partner:create-citizen partner:verify-citizen"' . "\n";
echo "}\n";
