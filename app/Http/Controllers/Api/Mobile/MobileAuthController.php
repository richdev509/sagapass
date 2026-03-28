<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\SendOtpRequest;
use App\Http\Requests\Mobile\VerifyOtpRequest;
use App\Http\Requests\Mobile\CompleteRegistrationRequest;
use App\Models\User;
use App\Models\UserVerificationDocument;
use App\Mail\EmailVerificationCode;
use App\Services\EncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MobileAuthController extends Controller
{
    /**
     * Send OTP code for registration
     */
    public function sendRegistrationOtp(SendOtpRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->email));

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette adresse email est déjà utilisée.',
            ], 400);
        }

        // Rate limiting: 5 attempts per hour per IP
        $ipKey = 'registration-otp:ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Réessayez dans ' . ceil($seconds / 60) . ' minutes.',
            ], 429);
        }

        // Rate limiting: 3 attempts per hour per email
        $emailKey = 'registration-otp:email:' . $email;
        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            $seconds = RateLimiter::availableIn($emailKey);
            return response()->json([
                'success' => false,
                'message' => 'Trop de codes envoyés à cette adresse. Réessayez dans ' . ceil($seconds / 60) . ' minutes.',
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache (15 minutes)
        $cacheKey = 'registration-otp:' . $email;
        Cache::put($cacheKey, [
            'otp' => $otp,
            'attempts' => 0,
            'created_at' => Carbon::now(),
        ], 900); // 15 minutes

        // Send email
        try {
            Mail::to($email)->send(new EmailVerificationCode($otp, 15));
        } catch (\Exception $e) {
            Log::error('Failed to send registration OTP', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'envoyer l\'email. Vérifiez votre adresse et réessayez.',
            ], 500);
        }

        // Increment rate limiters
        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($emailKey, 3600);

        return response()->json([
            'success' => true,
            'message' => 'Code OTP envoyé à votre email.',
        ]);
    }

    /**
     * Verify OTP code for registration
     */
    public function verifyRegistrationOtp(VerifyOtpRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->email));
        $otp = $request->otp;

        $cacheKey = 'registration-otp:' . $email;
        $data = Cache::get($cacheKey);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP expiré ou invalide.',
            ], 400);
        }

        // Check attempts
        if ($data['attempts'] >= 5) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives incorrectes. Demandez un nouveau code.',
            ], 400);
        }

        // Verify OTP
        if ($data['otp'] !== $otp) {
            $data['attempts']++;
            Cache::put($cacheKey, $data, 900);

            return response()->json([
                'success' => false,
                'message' => 'Code OTP incorrect. Tentatives restantes: ' . (5 - $data['attempts']),
            ], 400);
        }

        // Mark as verified
        Cache::put($cacheKey . ':verified', true, 86400); // 24 hours to complete registration

        // Debug log
        Log::info('Email verified successfully', [
            'email' => $email,
            'verifiedKey' => $cacheKey . ':verified',
            'cachedValue' => Cache::get($cacheKey . ':verified'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès.',
        ]);
    }

    /**
     * Complete registration with all data
     */
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->email));

        // Check if email was verified
        $verifiedKey = 'registration-otp:' . $email . ':verified';

        // Debug log
        Log::info('Checking email verification', [
            'email' => $email,
            'verifiedKey' => $verifiedKey,
            'cacheHasKey' => Cache::has($verifiedKey),
            'cacheValue' => Cache::get($verifiedKey),
        ]);

        if (!Cache::has($verifiedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'La vérification de l\'email a expiré. Veuillez recommencer l\'inscription.',
            ], 400);
        }

        try {
            // DÉCRYPTAGE DES DONNÉES SI ELLES SONT CRYPTÉES
            $idCardFront = $request->id_card_front;
            $idCardBack = $request->id_card_back;
            $selfie = $request->selfie;

            // Vérifier si les données sont cryptées
            if ($request->boolean('encrypted')) {
                Log::info('⚠️ Détection de données cryptées - décryptage en cours');

                $encryptionService = new EncryptionService();

                try {
                    // Décrypter et valider les données
                    $decryptedData = $encryptionService->decryptAndValidateRequest($request->all());

                    // Utiliser les données décryptées
                    $idCardFront = $decryptedData['id_card_front'];
                    $idCardBack = $decryptedData['id_card_back'];
                    $selfie = $decryptedData['selfie'];

                    Log::info('✅ Données décryptées et validées avec succès');

                } catch (\Exception $e) {
                    Log::error('❌ Erreur de décryptage', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de sécurité: ' . $e->getMessage(),
                    ], 400);
                }
            } else {
                Log::info('⚠️ Données non cryptées détectées (mode compatibilité)');
            }

            // Save photos (avec données décryptées si nécessaire)
            $idCardFrontPath = $this->saveBase64Image($idCardFront, 'id_cards');
            $idCardBackPath = $this->saveBase64Image($idCardBack, 'id_cards');
            $selfiePath = $this->saveBase64Image($selfie, 'selfies');

            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $email,
                'phone' => $request->phone,
                'niu' => $request->niu,
                'date_of_birth' => $request->date_of_birth,
                'email_verified_at' => Carbon::now(),
                'verification_status' => 'pending',
                'account_status' => 'active',
                'account_level' => 'basic',
                'verification_level' => 'document', // Changed from 'pending' to 'document'
                'password' => Hash::make(uniqid()), // Random password (not used)
            ]);

            // Save verification documents
            UserVerificationDocument::create([
                'user_id' => $user->id,
                'id_card_front' => $idCardFrontPath,
                'id_card_back' => $idCardBackPath,
                'selfie' => $selfiePath,
                'status' => 'pending',
            ]);

            // Generate token with all mobile scopes
            $token = $user->createToken('mobile-app', [
                'profile', 'email', 'phone', 'birthdate', 'photo', 'address'
            ])->plainTextToken;

            // Clear cache
            Cache::forget('registration-otp:' . $email);
            Cache::forget($verifiedKey);

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Votre compte est en cours de vérification.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'niu' => $user->niu,
                        'date_of_birth' => $user->date_of_birth->format('Y-m-d'),
                        'verification_status' => $user->verification_status,
                        'account_status' => $user->account_status,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                    ],
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'inscription.',
            ], 500);
        }
    }

    /**
     * Send OTP for login
     */
    public function sendLoginOtp(SendOtpRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->email));

        // Check if user exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cette adresse email.',
            ], 404);
        }

        // Rate limiting
        $ipKey = 'login-otp:ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Réessayez dans ' . ceil($seconds / 60) . ' minutes.',
            ], 429);
        }

        // Generate OTP
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP
        $cacheKey = 'login-otp:' . $email;
        Cache::put($cacheKey, [
            'otp' => $otp,
            'attempts' => 0,
            'user_id' => $user->id,
        ], 900); // 15 minutes

        // Send email
        try {
            Mail::to($email)->send(new EmailVerificationCode($otp, 15));
        } catch (\Exception $e) {
            Log::error('Failed to send login OTP', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'envoyer l\'email.',
            ], 500);
        }

        RateLimiter::hit($ipKey, 3600);

        return response()->json([
            'success' => true,
            'message' => 'Code OTP envoyé à votre email.',
        ]);
    }

    /**
     * Verify OTP and login
     */
    public function verifyLoginOtp(VerifyOtpRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->email));
        $otp = $request->otp;

        $cacheKey = 'login-otp:' . $email;
        $data = Cache::get($cacheKey);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP expiré ou invalide.',
            ], 400);
        }

        // Check attempts
        if ($data['attempts'] >= 5) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives incorrectes. Demandez un nouveau code.',
            ], 400);
        }

        // Verify OTP
        if ($data['otp'] !== $otp) {
            $data['attempts']++;
            Cache::put($cacheKey, $data, 900);

            return response()->json([
                'success' => false,
                'message' => 'Code OTP incorrect.',
            ], 400);
        }

        // Get user
        $user = User::find($data['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Generate token with all mobile scopes
        $token = $user->createToken('mobile-app', [
            'profile', 'email', 'phone', 'birthdate', 'photo', 'address'
        ])->plainTextToken;

        // Clear cache
        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'niu' => $user->niu,
                    'date_of_birth' => $user->date_of_birth->format('Y-m-d'),
                    'verification_status' => $user->verification_status,
                    'account_status' => $user->account_status,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'profile_picture' => $user->profile_picture,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Check if phone number is available
     */
    public function checkPhoneNumber(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = trim($request->phone);

        // Check if phone already exists
        $exists = User::where('phone', $phone)->exists();

        return response()->json([
            'success' => true,
            'message' => $exists ? 'Ce numéro de téléphone est déjà utilisé.' : 'Numéro disponible.',
            'data' => [
                'available' => !$exists,
                'phone' => $phone,
            ],
        ]);
    }

    /**
     * Check NIU availability
     */
    public function checkNiu(Request $request): JsonResponse
    {
        $request->validate([
            'niu' => 'required|string|regex:/^[0-9]{10}$/',
        ], [
            'niu.regex' => 'Le NIU doit contenir exactement 10 chiffres',
        ]);

        $niu = trim($request->niu);
        $exists = User::where('niu', $niu)->exists();

        return response()->json([
            'success' => true,
            'message' => $exists ? 'Ce NIU est déjà enregistré.' : 'NIU disponible.',
            'data' => [
                'available' => !$exists,
                'niu' => $niu,
            ],
        ]);
    }

    /**
     * Helper: Save base64 image
     */
    private function saveBase64Image(string $base64String, string $folder): string
    {
        // Remove data:image/...;base64, prefix if present
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
        $image = base64_decode($image);

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.jpg';
        $path = $folder . '/' . $filename;

        // Save to storage
        Storage::disk('local')->put($path, $image);

        return $path;
    }
}
