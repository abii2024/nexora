<?php

namespace App\Http\Requests\Uren;

use App\Models\Urenregistratie;
use Illuminate\Foundation\Http\FormRequest;

/**
 * US-13 AC-3: verplicht veld `teamleider_notitie` met min 10 / max 500 tekens
 * bij afkeuren van ingediende uren.
 */
class AfkeurUrenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $uren = $this->route('uren');

        return $uren instanceof Urenregistratie
            && ($this->user()?->can('afkeuren', $uren) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'teamleider_notitie' => trim((string) $this->input('teamleider_notitie', '')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'teamleider_notitie' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'teamleider_notitie.required' => 'Een afkeur-reden is verplicht.',
            'teamleider_notitie.min' => 'Schrijf minstens 10 tekens uitleg — vage redenen helpen de zorgbegeleider niet.',
            'teamleider_notitie.max' => 'De reden is te lang (max 500 tekens).',
        ];
    }

    public function validatedPayload(): string
    {
        return (string) $this->validated()['teamleider_notitie'];
    }
}
