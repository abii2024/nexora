<?php

namespace App\Http\Requests\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CaregiverAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        // Zowel bij store (geen client) als update (met client) — ClientPolicy
        // handelt team-scope af. Bij store valt dit onder ClientPolicy@create.
        if ($client instanceof Client) {
            return $this->user()?->can('update', $client) ?? false;
        }

        return $this->user()?->can('create', Client::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'caregiver_ids' => ['sometimes', 'array'],
            'caregiver_ids.*' => ['integer', 'exists:users,id'],
            'primary_user_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'caregiver_ids.array' => 'Ongeldige selectie van begeleiders.',
            'caregiver_ids.*.integer' => 'Ongeldige begeleider-ID.',
            'caregiver_ids.*.exists' => 'Een van de geselecteerde begeleiders bestaat niet.',
            'primary_user_id.integer' => 'Ongeldige primaire begeleider-ID.',
        ];
    }

    /**
     * Extra cross-field checks:
     *  - primary_user_id moet in caregiver_ids zitten
     *  - alle caregivers moeten actieve zorgbegeleiders zijn in het eigen team
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $ids = array_values(array_unique(array_map('intval', $this->input('caregiver_ids', []) ?? [])));
            $primaryId = $this->input('primary_user_id');

            if ($primaryId !== null && $primaryId !== '' && ! in_array((int) $primaryId, $ids, true)) {
                $v->errors()->add('primary_user_id', 'De primaire begeleider moet ook aangevinkt zijn.');
            }

            if ($ids === []) {
                return;
            }

            $teamId = $this->user()->team_id;
            $validUsers = User::query()
                ->whereIn('id', $ids)
                ->where('team_id', $teamId)
                ->where('role', User::ROLE_ZORGBEGELEIDER)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $invalid = array_diff($ids, $validUsers);
            if ($invalid !== []) {
                $v->errors()->add(
                    'caregiver_ids',
                    'Alleen actieve zorgbegeleiders uit je eigen team kunnen gekoppeld worden.'
                );
            }
        });
    }

    /**
     * @return array{userIds: list<int>, primaryId: ?int}
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();

        $userIds = array_values(array_unique(array_map('intval', $data['caregiver_ids'] ?? [])));
        $primary = $data['primary_user_id'] ?? null;

        return [
            'userIds' => $userIds,
            'primaryId' => $primary !== null && $primary !== '' ? (int) $primary : null,
        ];
    }
}
