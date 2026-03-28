<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est requise',
            'email.email' => 'L\'adresse email doit être valide',
            'otp.required' => 'Le code OTP est requis',
            'otp.size' => 'Le code OTP doit contenir exactement 6 chiffres',
        ];
    }
}
