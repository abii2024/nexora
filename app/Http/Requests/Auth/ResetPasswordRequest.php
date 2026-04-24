<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * US-15 AC-3: nieuw wachtwoord min 8 tekens + confirmed + token + email.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Het reset-token ontbreekt.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
            'password.required' => 'Vul een nieuw wachtwoord in.',
            'password.min' => 'Het wachtwoord moet minstens 8 tekens bevatten.',
            'password.confirmed' => 'De wachtwoord-bevestiging komt niet overeen.',
        ];
    }
}
