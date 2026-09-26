<?php

namespace App\Http\Controllers\Public;

use App\Mail\PartnerLoginOtpMail;
use App\Http\Controllers\Controller;
use App\Models\PartnerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion des comptes entreprise partenaire (guard 'partner'), en 2 temps :
 * email+mot de passe valides ->  code a usage unique envoye par email,
 * saisi avant que la session ne soit reellement etablie (voir
 * generateTwoFactorCode/verifyTwoFactorCode sur PartnerAccount). L'identite
 * en attente de verification vit en session ('partner_2fa:id'), jamais
 * connectee tant que le code n'est pas confirme.
 */
class PartnerAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('public.partner-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // validate() verifie les identifiants SANS etablir de session - la
        // connexion reelle n'a lieu qu'apres verification du code (voir
        // verifyOtp()).
        if (! Auth::guard('partner')->validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => ["Les identifiants fournis ne correspondent à aucun compte partenaire."],
            ]);
        }

        $account = PartnerAccount::where('email', $credentials['email'])->firstOrFail();

        $this->sendOtp($account);

        $request->session()->put('partner_2fa:id', $account->id);
        $request->session()->put('partner_2fa:remember', $request->boolean('remember'));

        return redirect()->route('partner.otp.verify');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('partner_2fa:id')) {
            return redirect()->route('partner.login');
        }

        return view('public.partner-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $accountId = $request->session()->get('partner_2fa:id');

        if (! $accountId) {
            return redirect()->route('partner.login');
        }

        $account = PartnerAccount::find($accountId);

        if (! $account || ! $account->verifyTwoFactorCode($request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expiré.'],
            ]);
        }

        $remember = $request->session()->pull('partner_2fa:remember', false);
        $request->session()->forget('partner_2fa:id');

        Auth::guard('partner')->login($account, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('partner.dashboard'));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $accountId = $request->session()->get('partner_2fa:id');

        if (! $accountId) {
            return redirect()->route('partner.login');
        }

        $account = PartnerAccount::find($accountId);

        if ($account) {
            $this->sendOtp($account);
        }

        return back()->with('status', 'Un nouveau code a été envoyé par email.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('partner')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }

    private function sendOtp(PartnerAccount $account): void
    {
        $code = $account->generateTwoFactorCode();

        Mail::to($account->email)->send(new PartnerLoginOtpMail($code));
    }
}
