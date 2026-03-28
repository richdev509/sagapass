<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:-18 years'],
            'phone' => ['required', 'string', 'regex:/^\+509[0-9]{8}$/'],
            'niu' => ['required', 'string', 'regex:/^[0-9]{10}$/', 'unique:users,niu'],
            'email' => ['required', 'email', 'unique:users,email'],
            'id_card_front' => ['required', 'string'], // base64 ou base64 crypté
            'id_card_back' => ['required', 'string'],  // base64 ou base64 crypté
            'selfie' => ['required', 'string'],         // base64 ou base64 crypté

            // Champs de sécurité (optionnels pour compatibilité)
            'encrypted' => ['sometimes', 'boolean'],
            'timestamp' => ['sometimes', 'required_with:encrypted', 'string'],
            'signature' => ['sometimes', 'required_with:encrypted', 'string'],
            'nonce' => ['sometimes', 'required_with:encrypted', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis',
            'first_name.min' => 'Le prénom doit contenir au moins 2 caractères',
            'last_name.required' => 'Le nom est requis',
            'last_name.min' => 'Le nom doit contenir au moins 2 caractères',
            'date_of_birth.required' => 'La date de naissance est requise',
            'date_of_birth.before' => 'Vous devez avoir au moins 18 ans',
            'phone.required' => 'Le numéro de téléphone est requis',
            'phone.regex' => 'Le numéro de téléphone doit être au format +509XXXXXXXX (Ex: +50928123456)',
            'niu.required' => 'Le NIU est requis',
            'niu.regex' => 'Le NIU doit contenir exactement 10 chiffres',
            'niu.unique' => 'Ce NIU est déjà enregistré dans notre système',
            'email.required' => 'L\'adresse email est requise',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'id_card_front.required' => 'La photo recto de la pièce d\'identité est requise',
            'id_card_back.required' => 'La photo verso de la pièce d\'identité est requise',
            'selfie.required' => 'Le selfie est requis',
        ];
    }
}
