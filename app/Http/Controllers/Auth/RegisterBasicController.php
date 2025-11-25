<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoVerification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class RegisterBasicController extends Controller
{
    /**
     * Afficher le formulaire d'inscription Basic - Étape 1
     */
    public function showStep1()
    {
        return view('auth.register-basic.step1');
    }

    /**
     * Traiter l'étape 1 - Informations de base
     */
    public function postStep1(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users'],
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
        ]);

        // Stocker les données en session
        session(['register_basic_step1' => $validated]);

        return redirect()->route('register.basic.step2');
    }

    /**
     * Afficher l'étape 2 - Photo de profil
     */
    public function showStep2()
    {
        if (!session()->has('register_basic_step1')) {
            return redirect()->route('register.basic.step1')
                ->with('error', 'Veuillez d\'abord remplir les informations de base.');
        }

        return view('auth.register-basic.step2');
    }

    /**
     * Traiter l'étape 2 - Sauvegarder photo
     */
    public function postStep2(Request $request)
    {
        if (!session()->has('register_basic_step1')) {
            return redirect()->route('register.basic.step1');
        }

        $request->validate([
            'photo' => ['required', 'string'], // Base64 image
        ]);

        // Décoder l'image base64
        $imageData = $request->input('photo');

        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]);
        } else {
            return back()->withErrors(['photo' => 'Format d\'image invalide.']);
        }

        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return back()->withErrors(['photo' => 'Impossible de décoder l\'image.']);
        }

        // Générer un nom unique
        $filename = 'temp_' . uniqid() . '.' . $type;
        Storage::disk('local')->put('temp/photos/' . $filename, $imageData);

        // Stocker le chemin en session
        session(['register_basic_photo' => 'temp/photos/' . $filename]);

        return redirect()->route('register.basic.step3');
    }

    /**
     * Afficher l'étape 3 - Vidéo de vérification
     */
    public function showStep3()
    {
        if (!session()->has('register_basic_step1') || !session()->has('register_basic_photo')) {
            return redirect()->route('register.basic.step1')
                ->with('error', 'Veuillez compléter toutes les étapes précédentes.');
        }

        $data = session('register_basic_step1');
        return view('auth.register-basic.step3', [
            'userName' => $data['first_name'] . ' ' . $data['last_name']
        ]);
    }

    /**
     * Traiter l'étape 3 - Sauvegarder vidéo et créer compte
     */
    public function postStep3(Request $request)
    {
        try {
            if (!session()->has('register_basic_step1') || !session()->has('register_basic_photo')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session expirée. Veuillez recommencer.'
                    ], 400);
                }
                return redirect()->route('register.basic.step1');
            }

            $request->validate([
                'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:10240'],
                'consent' => ['required', 'accepted'],
            ]);

            $userData = session('register_basic_step1');
            $photoPath = session('register_basic_photo');

            // Créer l'utilisateur
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'date_of_birth' => $userData['date_of_birth'],
                'phone' => $userData['phone'] ?? null,
                'account_level' => 'pending',  // ✅ Compte en attente de validation vidéo
                'verification_level' => 'none',
                'video_status' => 'pending',
                'video_consent_at' => now(),
            ]);

            // Déplacer la photo vers le dossier permanent
            $finalPhotoPath = 'profile_pictures/' . $user->id . '_' . time() . '.jpg';
            Storage::disk('public')->put(
                $finalPhotoPath,
                Storage::disk('local')->get($photoPath)
            );
            Storage::disk('local')->delete($photoPath);

            // Sauvegarder la vidéo dans storage/app/verification_videos
            $videoPath = $request->file('video')->store('verification_videos/' . $user->id, 'local');

            // Mettre à jour l'utilisateur
            $user->update([
                'profile_picture' => $finalPhotoPath,
                'verification_video' => $videoPath,
            ]);

            // Créer l'entrée de vérification vidéo
            VideoVerification::create([
                'user_id' => $user->id,
                'video_path' => $videoPath,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Envoyer l'email de vérification
            event(new Registered($user));

            // Connecter l'utilisateur
            Auth::login($user);

            // Nettoyer la session
            session()->forget(['register_basic_step1', 'register_basic_photo']);

            // Retourner JSON pour le JavaScript
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inscription réussie',
                    'redirect' => route('register.basic.complete')
                ]);
            }

            return redirect()->route('register.basic.complete');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur inscription step3', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur serveur: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    /**
     * Page de confirmation - En attente de validation
     */
    public function complete()
    {
        return view('auth.register-basic.complete');
    }
}
