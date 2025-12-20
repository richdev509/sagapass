<?php

namespace App\Services;

use App\Models\User;
use App\Models\Document;
use App\Models\VideoVerification;
use App\Services\EmailValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class PartnerCitizenService
{
    /**
     * Créer un citoyen SAGAPASS via l'API partenaire
     *
     * @param array $data Données du citoyen
     * @param string $partnerId ID du partenaire
     * @param string|null $partnerReference Référence client chez le partenaire
     * @param string|null $ipAddress IP de la requête
     * @return array
     */
    public function createCitizen(array $data, string $partnerId, ?string $partnerReference = null, ?string $ipAddress = null): array
    {
        // 1. VALIDATION EMAIL
        $emailValidation = EmailValidator::validate($data['email']);
        if (!$emailValidation['valid']) {
            throw new Exception($emailValidation['reason']);
        }

        // 2. VÉRIFIER SI EMAIL EXISTE DÉJÀ
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            // Vérifier le statut de vérification
            if ($existingUser->video_status === 'pending') {
                throw new Exception(
                    'Un compte avec cet email existe déjà et est en attente de vérification. ' .
                    'Veuillez patienter pendant que l\'administrateur examine votre dossier.'
                );
            } elseif ($existingUser->video_status === 'rejected') {
                throw new Exception(
                    'Votre vérification a été rejetée. Pour soumettre de nouveaux documents, ' .
                    'veuillez contacter le support SAGAPASS : sagapass@sagapass.com'
                );
            } else {
                throw new Exception(
                    'Un compte avec cet email existe déjà et est actif. ' .
                    'Si vous avez oublié votre mot de passe, utilisez la récupération de compte.'
                );
            }
        }

        // 3. VÉRIFIER SI DOCUMENT NUMBER EXISTE DÉJÀ (si fourni)
        if (isset($data['document']['document_number'])) {
            $existingDoc = Document::where('document_number', $data['document']['document_number'])->first();

            if ($existingDoc) {
                $docOwner = User::find($existingDoc->user_id);

                if ($docOwner) {
                    // Vérifier le statut
                    if ($docOwner->video_status === 'pending' || $existingDoc->verification_status === 'pending') {
                        throw new Exception(
                            'Ce numéro de document (NINU: ' . $data['document']['document_number'] . ') ' .
                            'est déjà enregistré et en attente de vérification. ' .
                            'Veuillez patienter pendant l\'examen de votre dossier.'
                        );
                    } elseif ($existingDoc->verification_status === 'rejected') {
                        throw new Exception(
                            'Ce numéro de document (NINU) a été rejeté lors d\'une précédente vérification. ' .
                            'Pour soumettre de nouveaux documents, contactez : sagapass@sagapass.com'
                        );
                    } else {
                        throw new Exception(
                            'Ce numéro de document (NINU: ' . $data['document']['document_number'] . ') ' .
                            'est déjà utilisé par un compte vérifié. ' .
                            'Chaque document ne peut être lié qu\'à un seul compte SAGAPASS.'
                        );
                    }
                }
            }
        }

        // 4. VÉRIFIER SI CARD NUMBER EXISTE DÉJÀ (pour CNI)
        if (isset($data['document']['card_number']) && !empty($data['document']['card_number'])) {
            $existingCard = Document::where('card_number', $data['document']['card_number'])->first();

            if ($existingCard) {
                $cardOwner = User::find($existingCard->user_id);

                if ($cardOwner) {
                    if ($cardOwner->video_status === 'pending' || $existingCard->verification_status === 'pending') {
                        throw new Exception(
                            'Ce numéro de carte (Card Number: ' . $data['document']['card_number'] . ') ' .
                            'est déjà enregistré et en attente de vérification.'
                        );
                    } elseif ($existingCard->verification_status === 'rejected') {
                        throw new Exception(
                            'Ce numéro de carte a été rejeté lors d\'une précédente vérification. ' .
                            'Pour soumettre de nouveaux documents, contactez : sagapass@sagapass.com'
                        );
                    } else {
                        throw new Exception(
                            'Ce numéro de carte (Card Number: ' . $data['document']['card_number'] . ') ' .
                            'est déjà utilisé par un compte vérifié.'
                        );
                    }
                }
            }
        }

        // 5. GÉNÉRER MOT DE PASSE ALÉATOIRE SÉCURISÉ
        $password = $this->generateSecurePassword();

        // 6. UPLOADER PHOTO DE PROFIL
        $profilePicturePath = $this->uploadBase64Image(
            $data['profile_picture'],
            'profile_pictures',
            'profile_' . Str::random(10)
        );

        // 7. UPLOADER VIDÉO DE VÉRIFICATION
        $videoPath = $this->uploadBase64Video(
            $data['verification_video'],
            'verification_videos',
            'video_' . Str::random(10)
        );

        // 8. CRÉER L'UTILISATEUR
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'email_verified_at' => now(), // Email considéré comme vérifié (vient du partenaire)
            'password' => Hash::make($password),
            'date_of_birth' => $data['date_of_birth'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'profile_picture' => $profilePicturePath,
            'verification_video' => $videoPath,
            'video_consent_at' => now(),
            'video_status' => 'pending', // En attente de validation admin
            'account_level' => 'pending',
            'verification_level' => 'email',
        ]);

        // 9. CRÉER L'ENTRÉE VIDEO VERIFICATION
        VideoVerification::create([
            'user_id' => $user->id,
            'video_path' => $videoPath,
            'status' => 'pending',
            'ip_address' => $ipAddress,
            'user_agent' => 'Partner API: ' . $partnerId,
        ]);

        // 10. CRÉER LE DOCUMENT SI FOURNI
        $document = null;
        if (isset($data['document'])) {
            $document = $this->createDocument($user->id, $data['document'], $ipAddress);
        }

        // 11. ENVOYER EMAIL AU CITOYEN AVEC IDENTIFIANTS
        $this->sendWelcomeEmail($user, $password, $partnerId);

        // 12. RETOURNER LES INFORMATIONS
        return [
            'success' => true,
            'citizen_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending_validation',
            'account_level' => $user->account_level,
            'verification_level' => $user->verification_level,
            'video_status' => $user->video_status,
            'document_status' => $document ? $document->verification_status : null,
            'message' => 'Compte créé avec succès. En attente de validation par l\'administrateur.',
            'warning' => 'Le compte sera activé après vérification de la vidéo et du document par un administrateur.',
        ];
    }

    /**
     * Uploader une image en base64
     */
    private function uploadBase64Image(string $base64Data, string $directory, string $filename): string
    {
        // Extraire le type MIME et les données
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Format d\'image invalide. Utilisez JPG ou PNG.');
            }

            $data = base64_decode($data);
            if ($data === false) {
                throw new Exception('Échec du décodage de l\'image');
            }

            $filename = $filename . '.' . $type;
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $data);

            return $path;
        }

        throw new Exception('Format de données image invalide');
    }

    /**
     * Uploader une vidéo en base64
     */
    private function uploadBase64Video(string $base64Data, string $directory, string $filename): string
    {
        // Extraire le type MIME et les données
        if (preg_match('/^data:video\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // mp4, webm, etc.

            if (!in_array($type, ['mp4', 'webm', 'mov'])) {
                throw new Exception('Format de vidéo invalide. Utilisez MP4, WEBM ou MOV.');
            }

            $data = base64_decode($data);
            if ($data === false) {
                throw new Exception('Échec du décodage de la vidéo');
            }

            $filename = $filename . '.' . $type;
            $path = $directory . '/' . $filename;

            Storage::disk('local')->put($path, $data);

            return $path;
        }

        throw new Exception('Format de données vidéo invalide');
    }

    /**
     * Créer un document d'identité
     */
    private function createDocument(int $userId, array $documentData, ?string $ipAddress): Document
    {
        // Upload des images du document
        $frontPhotoPath = $this->uploadBase64Image(
            $documentData['front_photo'],
            'documents',
            'doc_front_' . Str::random(10)
        );

        $backPhotoPath = null;
        if (isset($documentData['back_photo'])) {
            $backPhotoPath = $this->uploadBase64Image(
                $documentData['back_photo'],
                'documents',
                'doc_back_' . Str::random(10)
            );
        }

        return Document::create([
            'user_id' => $userId,
            'document_type' => $documentData['document_type'],
            'document_number' => $documentData['document_number'],
            'card_number' => $documentData['card_number'] ?? null,
            'issue_date' => $documentData['issue_date'],
            'expiry_date' => $documentData['expiry_date'],
            'front_photo_path' => $frontPhotoPath,
            'back_photo_path' => $backPhotoPath,
            'verification_status' => 'pending',
            'submitted_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Générer un mot de passe sécurisé aléatoire
     */
    private function generateSecurePassword(): string
    {
        return Str::random(12) . rand(100, 999);
    }

    /**
     * Envoyer l'email de bienvenue avec identifiants
     */
    private function sendWelcomeEmail(User $user, string $password, string $partnerId): void
    {
        Mail::to($user->email)->send(
            new \App\Mail\PartnerCitizenCreated($user, $password, $partnerId)
        );
    }

    /**
     * Vérifier le statut d'un citoyen
     */
    public function checkVerificationStatus(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'found' => false,
                'message' => 'Aucun citoyen trouvé avec cet email'
            ];
        }

        $document = Document::where('user_id', $user->id)
            ->latest()
            ->first();

        return [
            'found' => true,
            'citizen_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'account_level' => $user->account_level,
            'verification_level' => $user->verification_level,
            'email_verified' => $user->email_verified_at !== null,
            'video_status' => $user->video_status,
            'video_verified_at' => $user->video_verified_at,
            'document_status' => $document ? $document->verification_status : null,
            'document_verified_at' => $document ? $document->verified_at : null,
            'is_fully_verified' => $user->account_level === 'verified' && $user->video_status === 'approved',
        ];
    }
}
