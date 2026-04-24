<?php

namespace App\Http\Requests\Profiel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * US-16: profiel-update.
 *
 *  - name + email verplicht bij elke submit
 *  - password optioneel — maar als ingevuld: current_password verplicht en correct
 *  - role / is_active / team_id worden NIET gevalideerd → genegeerd (AC-4)
 */
class UpdateProfielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'current_password' => ['required_with:password', 'nullable', 'string', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'current_password.required_with' => 'Vul je huidige wachtwoord in om een nieuw wachtwoord te kunnen kiezen.',
            'current_password.current_password' => 'Je huidige wachtwoord klopt niet.',
            'password.min' => 'Het wachtwoord moet minstens 8 tekens bevatten.',
            'password.confirmed' => 'De wachtwoord-bevestiging komt niet overeen.',
        ];
    }

    /**
     * @return array{name: string, email: string}
     */
    public function profielData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => $data['email'],
        ];
    }

    public function newPassword(): ?string
    {
        $password = $this->validated()['password'] ?? null;

        return is_string($password) && $password !== '' ? $password : null;
    }
}
