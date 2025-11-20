<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Developer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeveloperAuthController extends Controller
{
    /**
     * Display the developer registration form.
     */
    public function showRegisterForm()
    {
        return view('developers.auth.register');
    }

    /**
     * Handle a developer registration request.
     * User must have a verified SAGAPASS account first.
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'company_name' => ['required', 'string', 'max:255'],
            'developer_website' => ['nullable', 'url', 'max:255'],
            'developer_bio' => ['nullable', 'string', 'max:1000'],
            'terms' => ['required', 'accepted'],
        ]);

        // Verify SAGAPASS credentials
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Les identifiants SAGAPASS fournis sont incorrects.',
            ])->withInput($request->except('password'));
        }

        $user = Auth::user();

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Votre compte SAGAPASS doit être vérifié. Veuillez vérifier votre email.',
            ])->withInput($request->except('password'));
        }

        // Check if user already has a developer account
        if ($user->developer()->exists()) {
            return redirect()->route('developers.dashboard')
                ->with('info', 'Vous avez déjà un compte développeur.');
        }

        // Create developer profile
        $developer = Developer::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'developer_website' => $request->developer_website,
            'developer_bio' => $request->developer_bio,
            'status' => 'active', // Auto-activate since SAGAPASS is already verified
            'verified_at' => now(),
        ]);

        return redirect()->route('developers.dashboard')
            ->with('success', 'Compte développeur créé avec succès ! Bienvenue sur SAGAPASS Developer.');
    }

    /**
     * Display the developer login form.
     */
    public function showLoginForm()
    {
        return view('developers.auth.login');
    }

    /**
     * Handle a developer login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Verify the user has a developer account
            if (!$user->developer()->exists()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Ce compte n\'a pas de profil développeur. Créez-en un d\'abord.',
                ])->withInput($request->only('email'));
            }

            // Check if developer account is not suspended
            if ($user->developer->status === 'suspended') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Votre compte développeur a été suspendu. Contactez l\'administrateur.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();

            return redirect()->intended(route('developers.dashboard'))
                ->with('success', 'Connexion réussie ! Bienvenue, ' . $user->first_name . '.');
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->withInput($request->only('email'));
    }

    /**
     * Log the developer out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('developers.login')
            ->with('success', 'Déconnexion réussie.');
    }
}
