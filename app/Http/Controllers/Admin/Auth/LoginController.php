<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Afficher le formulaire de login admin
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Traiter la tentative de connexion admin
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();

            // Vérifier si l'admin est actif
            if ($admin->status !== 'active') {
                Auth::guard('admin')->logout();

                throw ValidationException::withMessages([
                    'email' => ['Votre compte administrateur est désactivé. Contactez le super admin.'],
                ]);
            }

            // Vérifier si le 2FA est activé
            if ($admin->hasTwoFactorEnabled()) {
                // Marquer comme nécessitant la vérification 2FA
                $request->session()->put('2fa:auth:id', $admin->id);
                $request->session()->put('2fa:auth:remember', $remember);

                // Déconnecter temporairement
                Auth::guard('admin')->logout();

                return redirect()->route('admin.two-factor.verify');
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis ne correspondent à aucun compte administrateur.'],
        ]);
    }

    /**
     * Déconnecter l'admin
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

