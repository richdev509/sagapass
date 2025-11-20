<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => [
                'required',
                'string',
                'regex:/^\+(?:509\d{8}|1\d{10})$/',
                'unique:users,phone,' . $user->id
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                'before_or_equal:' . now()->subYears(18)->format('Y-m-d')
            ],
        ], [
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le numéro de téléphone doit être au format: +509XXXXXXXX (Haïti 8 chiffres) ou +1XXXXXXXXXX (USA/R.D. 10 chiffres)',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre utilisateur.',
            'date_of_birth.required' => 'La date de naissance est obligatoire.',
            'date_of_birth.before_or_equal' => 'Vous devez avoir au moins 18 ans pour utiliser ce service.'
        ]);

        $user->update($validated);

        return redirect()->route('profile.edit')
            ->with('success', 'Profil mis à jour avec succès !');
    }    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password'])
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Mot de passe modifié avec succès !');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $user = auth()->user();

        // Supprimer l'ancienne photo si elle existe
        if ($user->profile_photo && \Storage::exists($user->profile_photo)) {
            \Storage::delete($user->profile_photo);
        }

        // Sauvegarder la nouvelle photo
        $path = $request->file('photo')->store('profile-photos', 'public');

        $user->update([
            'profile_photo' => $path
        ]);

        return redirect()->route('profile.edit')
            ->with('success', 'Photo de profil mise à jour !');
    }

    /**
     * Afficher les services connectés
     */
    public function connectedServices()
    {
        $authorizations = UserAuthorization::where('user_id', auth()->id())
            ->whereNull('revoked_at')
            ->with('application')
            ->latest('granted_at')
            ->get();

        return view('profile.connected-services', compact('authorizations'));
    }

    /**
     * Révoquer l'accès d'un service
     */
    public function revokeService(UserAuthorization $authorization)
    {
        // Vérifier que l'autorisation appartient à l'utilisateur connecté
        if ($authorization->user_id !== auth()->id()) {
            abort(403);
        }

        $appName = $authorization->application->name;
        $authorization->revoke();

        return redirect()->route('profile.connected-services')
            ->with('success', "L'accès de {$appName} a été révoqué avec succès.");
    }

    /**
     * Historique des connexions
     */
    public function connectionHistory()
    {
        // Récupérer tous les logs de connexion avec les infos de l'application
        $connections = \App\Models\OAuthConnectionLog::where('user_id', auth()->id())
            ->with(['application', 'authorization'])
            ->latest('connected_at')
            ->paginate(20);

        return view('profile.connection-history', compact('connections'));
    }
}
