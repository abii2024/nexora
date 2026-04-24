<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * US-15 AC-1 + AC-2: e-mail-format valideren; enumeration protection gebeurt
 * in de controller (altijd dezelfde flash, ook bij niet-bestaande user).
 */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
        ];
    }
}
