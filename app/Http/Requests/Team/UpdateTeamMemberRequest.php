<?php

namespace App\Http\Requests\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('update', $target) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'voornaam' => ['required', 'string', 'max:255'],
            'achternaam' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target->id),
            ],
            'role' => ['required', Rule::in([User::ROLE_ZORGBEGELEIDER, User::ROLE_TEAMLEIDER])],
            'dienstverband' => ['required', Rule::in(['intern', 'extern', 'zzp'])],
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
        ];
    }

    /**
     * Retourneert de te persisten velden zonder wachtwoord
     * (wachtwoord-wijziging valt onder US-16 /profiel).
     *
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['voornaam'].' '.$data['achternaam']),
            'email' => $data['email'],
            'role' => $data['role'],
            'dienstverband' => $data['dienstverband'],
        ];
    }
}
