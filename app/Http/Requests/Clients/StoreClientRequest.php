<?php

namespace App\Http\Requests\Clients;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Client::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'voornaam' => ['required', 'string', 'max:255'],
            'achternaam' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefoon' => ['nullable', 'string', 'max:30'],
            'bsn' => ['nullable', 'string', 'size:9', 'regex:/^\d{9}$/', 'unique:clients,bsn'],
            'geboortedatum' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in([
                Client::STATUS_ACTIEF,
                Client::STATUS_WACHT,
                Client::STATUS_INACTIEF,
            ])],
            'care_type' => ['required', Rule::in([
                Client::CARE_WMO,
                Client::CARE_WLZ,
                Client::CARE_JW,
            ])],
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
            'email.email' => 'Dit is geen geldig e-mailadres.',
            'bsn.size' => 'Een BSN bestaat uit precies 9 cijfers.',
            'bsn.regex' => 'Een BSN mag alleen cijfers bevatten.',
            'bsn.unique' => 'Dit BSN is al gekoppeld aan een andere cliënt.',
            'geboortedatum.date' => 'Geen geldige geboortedatum.',
            'geboortedatum.before' => 'Geboortedatum moet in het verleden liggen.',
            'status.required' => 'Kies een status.',
            'status.in' => 'Ongeldige status.',
            'care_type.required' => 'Kies een zorgtype.',
            'care_type.in' => 'Ongeldig zorgtype (kies WMO, WLZ of JW).',
        ];
    }

    /**
     * Levert de te persisten payload — voegt server-side velden toe
     * (team_id, created_by_user_id) en dekt alleen whitelisted velden.
     *
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();

        return [
            'voornaam' => $data['voornaam'],
            'achternaam' => $data['achternaam'],
            'email' => $data['email'] ?? null,
            'telefoon' => $data['telefoon'] ?? null,
            'bsn' => $data['bsn'] ?? null,
            'geboortedatum' => $data['geboortedatum'] ?? null,
            'status' => $data['status'],
            'care_type' => $data['care_type'],
            'team_id' => $this->user()->team_id,
            'created_by_user_id' => $this->user()->id,
        ];
    }
}
