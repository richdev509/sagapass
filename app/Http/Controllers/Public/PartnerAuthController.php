<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion des comptes entreprise partenaire (guard 'partner') — même
 * squelette que Admin\Auth\LoginController, guard différent.
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

        if (! Auth::guard('partner')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ["Les identifiants fournis ne correspondent à aucun compte partenaire."],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('partner.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('partner')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }
}
