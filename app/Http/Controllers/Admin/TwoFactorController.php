<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    /**
     * Afficher la page de configuration 2FA
     */
    public function index()
    {
        $admin = auth('admin')->user();

        return view('admin.two-factor.index', [
            'twoFactorEnabled' => $admin->hasTwoFactorEnabled(),
        ]);
    }

    /**
     * Activer le 2FA - Étape 1: Générer le QR code
     */
    public function enable(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->hasTwoFactorEnabled()) {
            return redirect()
                ->route('admin.two-factor.index')
                ->with('error', 'Le 2FA est déjà activé');
        }

        // Si demande de reset, vider le secret en session
        if ($request->input('reset_secret')) {
            session()->forget('two_factor_secret');
            return redirect()->route('admin.two-factor.enable')
                ->with('success', 'Nouveau QR code généré. Scannez-le maintenant.');
        }

        // Réutiliser le secret en session s'il existe déjà, sinon en générer un nouveau
        // Cela évite de générer un nouveau secret à chaque rafraîchissement de page
        $secret = session('two_factor_secret');

        if (!$secret) {
            $secret = $admin->generateTwoFactorSecret();

            // VALIDATION CRITIQUE : Vérifier que le secret est bien en Base32
            if (!preg_match('/^[A-Z2-7]+$/', $secret)) {
                Log::error('2FA Secret Invalid Format', [
                    'secret' => $secret,
                    'length' => strlen($secret),
                ]);
                return redirect()->route('admin.two-factor.index')
                    ->with('error', 'Erreur de génération du secret. Veuillez réessayer.');
            }

            session(['two_factor_secret' => $secret]);

            Log::info('2FA Secret Generated', [
                'secret_length' => strlen($secret),
                'admin_email' => $admin->email,
            ]);
        }

        // Construire l'URL otpauth COMPLÈTE avec TOUS les paramètres requis
        // Format: otpauth://totp/<issuer>:<label>?secret=<SECRET>&issuer=<issuer>&algorithm=SHA1&digits=6&period=30
        $issuer = config('app.name');
        $label = $admin->email;

        $qrCodeUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($label),
            $secret, // Base32, pas d'encoding
            rawurlencode($issuer)
        );

        Log::info('2FA QR URL Generated', [
            'url' => $qrCodeUrl,
            'issuer' => $issuer,
            'label' => $label,
        ]);

        // Générer l'image QR code avec Endroid v6 - readonly class avec constructeur
        $qrCode = new QrCode(
            data: $qrCodeUrl,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $qrCodeImage = base64_encode($result->getString());

        return view('admin.two-factor.enable', [
            'secret' => $secret,
            'qrCodeImage' => $qrCodeImage,
        ]);
    }

    /**
     * Confirmer l'activation du 2FA
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Le code est requis',
            'code.size' => 'Le code doit contenir 6 chiffres',
        ]);

        $admin = auth('admin')->user();
        $secret = session('two_factor_secret');

        if (!$secret) {
            return redirect()
                ->route('admin.two-factor.enable')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        // Vérifier le code avec fenêtre de tolérance élargie (4 intervalles = ±2 minutes)
        // Cela permet de gérer les décalages horaires temporaires
        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        // DEBUG MODE : Logger TOUS les codes valides dans la fenêtre de tolérance élargie
        $currentTimestamp = time();
        $validCodes = [];

        // Augmenter la fenêtre de -10 à +10 (±5 minutes) pour couvrir le décalage horaire
        for ($window = -10; $window <= 10; $window++) {
            $timestamp = $currentTimestamp + ($window * 30);
            $code = $google2fa->oathTotp($secret, $timestamp);
            $validCodes[$window] = [
                'code' => $code,
                'timestamp' => $timestamp,
                'time_utc' => gmdate('H:i:s', $timestamp),
                'is_current' => $window === 0,
            ];
        }

        // Le code "attendu" affiché est celui de la fenêtre actuelle (window=0)
        $expectedCode = $validCodes[0]['code'];

        // Debug: afficher les informations de validation COMPLÈTES
        Log::info('2FA Validation Attempt', [
            'code_entered' => $request->code,
            'expected_code_current_window' => $expectedCode,
            'all_valid_codes' => $validCodes,
            'secret_length' => strlen($secret),
            'server_time_local' => now()->toDateTimeString(),
            'server_time_utc' => gmdate('Y-m-d H:i:s'),
            'timestamp' => $currentTimestamp,
        ]);

        // Vérifier avec fenêtre de tolérance de 10 (±5 minutes)
        // pour gérer les décalages horaires importants (ex: 1h de décalage entre Windows et smartphone)
        $isValid = $google2fa->verifyKey($secret, $request->code, 10);

        // Déterminer quelle fenêtre a été utilisée si valide
        $matchedWindow = null;
        if ($isValid) {
            foreach ($validCodes as $window => $data) {
                if ($data['code'] === $request->code) {
                    $matchedWindow = $window;
                    break;
                }
            }
        }

        Log::info('2FA Validation Result', [
            'is_valid' => $isValid,
            'matched_window' => $matchedWindow,
            'time_offset_seconds' => $matchedWindow ? ($matchedWindow * 30) : null,
        ]);

        if (!$isValid) {
            return back()
                ->withErrors(['code' => 'Code invalide. Veuillez réessayer avec le code actuel affiché dans l\'application.'])
                ->withInput();
        }

        // Générer les codes de récupération
        $recoveryCodes = $admin->generateRecoveryCodes();

        // Activer le 2FA
        $admin->enableTwoFactor($secret, $recoveryCodes);

        // Nettoyer la session
        session()->forget('two_factor_secret');

        return view('admin.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Désactiver le 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ], [
            'password.required' => 'Le mot de passe est requis',
        ]);

        $admin = auth('admin')->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $admin->password)) {
            return back()
                ->withErrors(['password' => 'Mot de passe incorrect'])
                ->withInput();
        }

        // Désactiver le 2FA
        $admin->disableTwoFactor();

        return redirect()
            ->route('admin.two-factor.index')
            ->with('success', 'L\'authentification à deux facteurs a été désactivée');
    }

    /**
     * Régénérer les codes de récupération
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ], [
            'password.required' => 'Le mot de passe est requis',
        ]);

        $admin = auth('admin')->user();

        if (!$admin->hasTwoFactorEnabled()) {
            return redirect()
                ->route('admin.two-factor.index')
                ->with('error', 'Le 2FA n\'est pas activé');
        }

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $admin->password)) {
            return back()
                ->withErrors(['password' => 'Mot de passe incorrect'])
                ->withInput();
        }

        // Générer de nouveaux codes
        $recoveryCodes = $admin->generateRecoveryCodes();
        $admin->storeRecoveryCodes($recoveryCodes);

        return view('admin.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'regenerated' => true,
        ]);
    }

    /**
     * Afficher la page de vérification 2FA au login
     */
    public function showVerify(Request $request)
    {
        $adminId = $request->session()->get('2fa:auth:id');

        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        return view('admin.two-factor.verify');
    }

    /**
     * Vérifier le code 2FA au login
     */
    public function verify(Request $request)
    {
        $adminId = $request->session()->get('2fa:auth:id');

        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }

        $admin = \App\Models\Admin::findOrFail($adminId);

        // Si utilisation d'un code de récupération
        if ($request->filled('recovery_code')) {
            $request->validate([
                'recovery_code' => 'required|string|size:10',
            ], [
                'recovery_code.required' => 'Le code de récupération est requis',
                'recovery_code.size' => 'Le code doit contenir 10 caractères',
            ]);

            if ($admin->verifyRecoveryCode($request->recovery_code)) {
                return $this->loginAdmin($request, $admin);
            }

            return back()
                ->withErrors(['recovery_code' => 'Code de récupération invalide'])
                ->withInput();
        }

        // Vérification du code TOTP normal
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Le code est requis',
            'code.size' => 'Le code doit contenir 6 chiffres',
        ]);

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $admin->getDecryptedTwoFactorSecret();

        // Fenêtre de tolérance élargie de 10 (±5 minutes) pour le login aussi
        if ($google2fa->verifyKey($secret, $request->code, 10)) {
            Log::info('2FA Login Success', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
            ]);

            return $this->loginAdmin($request, $admin);
        }

        Log::warning('2FA Login Failed', [
            'admin_id' => $admin->id,
            'code_entered' => $request->code,
        ]);

        return back()
            ->withErrors(['code' => 'Code invalide. Veuillez réessayer.'])
            ->withInput();
    }

    /**
     * Connecter l'admin après validation 2FA
     */
    protected function loginAdmin(Request $request, $admin)
    {
        $remember = $request->session()->get('2fa:auth:remember', false);

        // Nettoyer la session 2FA
        $request->session()->forget('2fa:auth:id');
        $request->session()->forget('2fa:auth:remember');

        // Connecter l'admin
        Auth::guard('admin')->login($admin, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Connexion réussie !');
    }
}
