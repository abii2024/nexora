<?php

namespace App\Http\Requests\Clients;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('update', $client) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'voornaam' => ['required', 'string', 'max:255'],
            'achternaam' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefoon' => ['nullable', 'string', 'max:30'],
            'bsn' => [
                'nullable',
                'string',
                'size:9',
                'regex:/^\d{9}$/',
                Rule::unique('clients', 'bsn')
                    ->ignore($client->id)
                    ->whereNull('deleted_at'),
            ],
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
     * Alleen de velden die gewijzigd mogen worden — team_id en created_by_user_id
     * blijven immutable.
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
        ];
    }
}
