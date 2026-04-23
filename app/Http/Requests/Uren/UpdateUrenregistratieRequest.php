<?php

namespace App\Http\Requests\Uren;

use App\Models\Urenregistratie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUrenregistratieRequest extends FormRequest
{
    public function authorize(): bool
    {
        $uren = $this->route('uren');

        return $uren instanceof Urenregistratie
            && ($this->user()?->can('update', $uren) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'datum' => ['required', 'date', 'before_or_equal:today'],
            'starttijd' => ['required', 'date_format:H:i'],
            'eindtijd' => ['required', 'date_format:H:i', 'after:starttijd'],
            'notities' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Kies een cliënt.',
            'client_id.exists' => 'Geen geldige cliënt gekozen.',
            'datum.required' => 'Kies een datum.',
            'datum.before_or_equal' => 'Uren in de toekomst registreren mag niet.',
            'starttijd.required' => 'Vul een starttijd in.',
            'starttijd.date_format' => 'Starttijd moet in het formaat HH:MM.',
            'eindtijd.required' => 'Vul een eindtijd in.',
            'eindtijd.date_format' => 'Eindtijd moet in het formaat HH:MM.',
            'eindtijd.after' => 'Eindtijd moet na starttijd liggen.',
            'notities.max' => 'Notities zijn te lang (max 2000 tekens).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();

        return [
            'client_id' => (int) $data['client_id'],
            'datum' => $data['datum'],
            'starttijd' => $data['starttijd'].':00',
            'eindtijd' => $data['eindtijd'].':00',
            'notities' => $data['notities'] ?? null,
        ];
    }
}
