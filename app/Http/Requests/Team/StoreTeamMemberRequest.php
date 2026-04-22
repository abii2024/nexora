<?php

namespace App\Http\Requests\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'voornaam' => ['required', 'string', 'max:255'],
            'achternaam' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ZORGBEGELEIDER, User::ROLE_TEAMLEIDER])],
            'dienstverband' => ['required', Rule::in(['intern', 'extern', 'zzp'])],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voornaam.required' => 'Voornaam is verplicht.',
            'achternaam.required' => 'Achternaam is verplicht.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
            'email.unique' => 'Er bestaat al een medewerker met dit e-mailadres.',
            'role.required' => 'Kies een rol.',
            'role.in' => 'Ongeldige rol.',
            'dienstverband.required' => 'Kies een dienstverband.',
            'dienstverband.in' => 'Kies intern, extern of zzp.',
            'password.required' => 'Kies een initieel wachtwoord.',
            'password.min' => 'Wachtwoord moet minimaal :min tekens zijn.',
            'password.confirmed' => 'Wachtwoord-bevestiging komt niet overeen.',
        ];
    }

    /**
     * Levert de te persisten payload — voegt server-side velden toe die niet
     * in het formulier horen (team_id, is_active) en voorkomt mass-assignment
     * van velden buiten de whitelist.
     *
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['voornaam'].' '.$data['achternaam']),
            'email' => $data['email'],
            'password' => $data['password'], // wordt door User::casts('hashed') bcrypted
            'role' => $data['role'],
            'dienstverband' => $data['dienstverband'],
            'team_id' => $this->user()->team_id,
            'is_active' => true,
        ];
    }
}
