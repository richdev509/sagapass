<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoVerification;
use App\Services\EmailValidator;
use App\Mail\EmailVerificationCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Carbon\Carbon;

class RegisterBasicController extends Controller
{
    /**
     * Afficher le formulaire de demande d'email - Étape 1a
     */
    public function showEmailRequest()
    {
        // Si une session d'inscription existe déjà et que l'email est vérifié, rediriger vers l'étape suivante
        if (session()->has('registration.email_verified') && session('registration.email_verified') === true) {
            return redirect()->route('register.basic.step1');
        }

        return view('auth.register-basic.email-request');
    }

    /**
     * Traiter la demande d'email et envoyer le code de vérification - Étape 1b
     */
    public function sendVerificationCode(Request $request)
    {
        // Rate limiting par IP : max 5 codes par heure
        $ipKey = 'email-verification:ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return back()->withErrors([
                'email' => "Trop de tentatives. Réessayez dans " . ceil($seconds / 60) . " minutes."
            ]);
        }

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->email));

        // Vérifier si l'email existe déjà
        if (User::where('email', $email)->exists()) {
            return back()->withErrors([
                'email' => 'Cet email est déjà utilisé. Veuillez vous connecter ou utiliser un autre email.'
            ])->withInput();
        }

        // Valider l'email avec notre système strict
        $validation = EmailValidator::validate($email);
        if (!$validation['valid']) {
            return back()->withErrors([
                'email' => $validation['reason']
            ])->withInput();
        }

        // Rate limiting par email : max 3 codes par heure
        $emailKey = 'email-verification:email:' . $email;
        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            $seconds = RateLimiter::availableIn($emailKey);
            return back()->withErrors([
                'email' => "Trop de codes envoyés à cette adresse. Réessayez dans " . ceil($seconds / 60) . " minutes."
            ])->withInput();
        }

        // Générer un code de vérification à 6 chiffres
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Stocker en session avec expiration de 15 minutes
        session([
            'registration' => [
                'email' => $email,
                'verification_code' => $code,
                'code_expires_at' => Carbon::now()->addMinutes(15)->toDateTimeString(),
                'email_verified' => false,
                'attempts' => 0,
                'expires_at' => Carbon::now()->addHours(24)->toDateTimeString(),
                'ip_address' => $request->ip(),
                'step' => 'verify_code',
            ]
        ]);

        // Envoyer l'email avec le code
        try {
            Mail::to($email)->send(new EmailVerificationCode($code, 15));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email vérification', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'email' => 'Impossible d\'envoyer l\'email. Vérifiez votre adresse et réessayez.'
            ])->withInput();
        }

        // Incrémenter les compteurs de rate limiting
        RateLimiter::hit($ipKey, 3600); // 1 heure
        RateLimiter::hit($emailKey, 3600); // 1 heure

        return redirect()->route('register.basic.verify-code');
    }

    /**
     * Afficher le formulaire de vérification du code - Étape 1c
     */
    public function showVerifyCode()
    {
        if (!session()->has('registration.verification_code')) {
            return redirect()->route('register.basic.email-request')
                ->with('error', 'Veuillez d\'abord demander un code de vérification.');
        }

        $registration = session('registration');

        // Vérifier si le code a expiré
        $expiresAt = Carbon::parse($registration['code_expires_at']);
        if ($expiresAt->isPast()) {
            session()->forget('registration');
            return redirect()->route('register.basic.email-request')
                ->with('error', 'Le code a expiré. Veuillez recommencer.');
        }

        return view('auth.register-basic.verify-code', [
            'email' => $registration['email'],
            'expiresAt' => $expiresAt,
        ]);
    }

    /**
     * Vérifier le code saisi - Étape 1d
     */
    public function verifyCode(Request $request)
    {
        if (!session()->has('registration.verification_code')) {
            return redirect()->route('register.basic.email-request');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.size' => 'Le code doit contenir exactement 6 chiffres.',
            'code.regex' => 'Le code doit contenir uniquement des chiffres.',
        ]);

        $registration = session('registration');

        // Vérifier l'expiration
        $expiresAt = Carbon::parse($registration['code_expires_at']);
        if ($expiresAt->isPast()) {
            session()->forget('registration');
            return redirect()->route('register.basic.email-request')
                ->with('error', 'Le code a expiré. Veuillez recommencer.');
        }

        // Limiter les tentatives (max 5)
        $attempts = $registration['attempts'] ?? 0;
        if ($attempts >= 5) {
            session()->forget('registration');
            return redirect()->route('register.basic.email-request')
                ->with('error', 'Trop de tentatives incorrectes. Veuillez recommencer.');
        }

        // Vérifier le code
        if ($request->code !== $registration['verification_code']) {
            // Incrémenter les tentatives
            $registration['attempts'] = $attempts + 1;
            session(['registration' => $registration]);

            return back()->withErrors([
                'code' => 'Code incorrect. Tentative ' . $registration['attempts'] . '/5.'
            ]);
        }

        // Code valide ! Marquer l'email comme vérifié
        $registration['email_verified'] = true;
        $registration['email_verified_at'] = Carbon::now()->toDateTimeString();
        $registration['step'] = 'personal_info';

        // Supprimer le code (plus besoin)
        unset($registration['verification_code']);
        unset($registration['code_expires_at']);
        unset($registration['attempts']);

        session(['registration' => $registration]);

        // Log de succès
        Log::info('Email vérifié avec succès', [
            'email' => $registration['email'],
            'ip' => $request->ip(),
        ]);

        return redirect()->route('register.basic.step1')
            ->with('success', 'Email vérifié ! Complétez maintenant votre profil.');
    }

    /**
     * Afficher le formulaire d'inscription Basic - Étape 1 (après vérification email)
     */
    public function showStep1()
    {
        // Vérifier que l'email a été vérifié
        if (!session()->has('registration.email_verified') || session('registration.email_verified') !== true) {
            return redirect()->route('register.basic.email-request')
                ->with('error', 'Vous devez d\'abord vérifier votre email.');
        }

        $registration = session('registration');

        return view('auth.register-basic.step1', [
            'email' => $registration['email'],
        ]);
    }

    /**
     * Traiter l'étape 1 - Informations de base (après vérification email)
     */
    public function postStep1(Request $request)
    {
        // Double vérification de la session
        if (!session()->has('registration.email_verified') || session('registration.email_verified') !== true) {
            return redirect()->route('register.basic.email-request');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'regex:/^\+\d{1,4}\d{8,10}$/', 'unique:users'],
        ], [
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'phone.regex' => 'Le format du numéro de téléphone est invalide. Utilisez le format avec indicatif (ex: +50912345678).',
        ]);

        // Ajouter les données à la session
        $registration = session('registration');
        $registration['data'] = $validated;
        $registration['step'] = 'photo';
        session(['registration' => $registration]);

        return redirect()->route('register.basic.step2');
    }

    /**
     * Afficher l'étape 2 - Photo de profil
     */
    public function showStep2()
    {
        // Vérifier la session et l'email vérifié
        if (!session()->has('registration.data')) {
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
        if (!session()->has('registration.data')) {
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
        $registration = session('registration');
        $registration['temp_photo_path'] = 'temp/photos/' . $filename;
        $registration['step'] = 'video';
        session(['registration' => $registration]);

        return redirect()->route('register.basic.step3');
    }

    /**
     * Afficher l'étape 3 - Vidéo de vérification
     */
    public function showStep3()
    {
        if (!session()->has('registration.data') || !session()->has('registration.temp_photo_path')) {
            return redirect()->route('register.basic.step1')
                ->with('error', 'Veuillez compléter toutes les étapes précédentes.');
        }

        $registration = session('registration');
        $data = $registration['data'];

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
            if (!session()->has('registration.data') || !session()->has('registration.temp_photo_path')) {
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

            $registration = session('registration');
            $userData = $registration['data'];
            $photoPath = $registration['temp_photo_path'];

            // Créer l'utilisateur avec email déjà vérifié
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $registration['email'],
                'email_verified_at' => Carbon::parse($registration['email_verified_at']), // ✅ Email déjà vérifié
                'password' => Hash::make($userData['password']),
                'date_of_birth' => $userData['date_of_birth'],
                'phone' => $userData['phone'] ?? null,
                'account_level' => 'pending',  // ✅ Compte en attente de validation vidéo
                'verification_level' => 'email', // ✅ Email vérifié
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

            // Log de succès
            Log::info('Inscription complétée avec email pré-vérifié', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            // Connecter l'utilisateur
            Auth::login($user);

            // Nettoyer la session
            session()->forget('registration');

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
