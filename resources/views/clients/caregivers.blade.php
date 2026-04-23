@extends('layouts.app')

@section('title', 'Begeleiders — '.$client->fullName())

@php
    $currentCaregivers = $client->caregivers->keyBy('id');
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Begeleiders beheren</h1>
            <p class="page-subtitle">
                Cliënt: <strong>{{ $client->fullName() }}</strong> · {{ $client->team?->name ?? '—' }}
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('clients.show', $client)">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar cliënt
            </x-ui.button>
        </div>
    </div>

    <div style="max-width: 820px;">
        <x-ui.card padded>
            @if($errors->any())
                <div style="margin-bottom: var(--space-5);">
                    <x-ui.alert type="error">
                        <strong>Er zijn {{ $errors->count() }} {{ $errors->count() === 1 ? 'fout' : 'fouten' }} gevonden:</strong>
                        <ul style="margin-top: var(--space-2); padding-left: var(--space-5); list-style: disc;">
                            @foreach($errors->all() as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                </div>
            @endif

            <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
                Vink de zorgbegeleiders aan die aan deze cliënt gekoppeld zijn. De eerste wordt automatisch primair, de tweede secundair, de rest tertiair. Gebruik de radio-knop om een andere begeleider primair te maken — de huidige primair wordt dan secundair.
            </p>

            <form method="POST" action="{{ route('clients.caregivers.update', $client) }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
                @csrf
                @method('PUT')

                @if($availableCaregivers->isEmpty())
                    <div style="padding: var(--space-4); background: var(--color-ink-50); border-radius: var(--radius-md); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                        Geen actieve zorgbegeleiders beschikbaar in je team.
                    </div>
                @else
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-3);">
                        @foreach($availableCaregivers as $caregiver)
                            @php
                                $existing = $currentCaregivers->get($caregiver->id);
                                $isChecked = old('caregiver_ids') !== null
                                    ? in_array((string) $caregiver->id, array_map('strval', old('caregiver_ids', [])), true)
                                    : $existing !== null;

                                $existingRole = $existing?->pivot?->role;
                                $isPrimary = old('primary_user_id') !== null
                                    ? (string) old('primary_user_id') === (string) $caregiver->id
                                    : $existingRole === 'primair';
                            @endphp

                            <label style="display: flex; flex-direction: column; gap: var(--space-2); padding: var(--space-3); border: 1px solid var(--color-border); border-radius: var(--radius-md); cursor: pointer; {{ $isChecked ? 'background: var(--color-primary-bg);' : '' }}">
                                <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                                    <input
                                        type="checkbox"
                                        name="caregiver_ids[]"
                                        value="{{ $caregiver->id }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        style="margin-top: 2px; accent-color: var(--color-ink-900);"
                                    >
                                    <div style="flex: 1;">
                                        <span style="display: block; font-weight: var(--font-weight-medium); color: var(--color-ink-900); font-size: var(--font-size-sm);">{{ $caregiver->name }}</span>
                                        <span style="display: block; font-size: var(--font-size-xs); color: var(--color-text-muted);">{{ $caregiver->email }}</span>
                                        @if($existingRole)
                                            <span style="display: inline-block; margin-top: var(--space-1);">
                                                <x-ui.badge tone="{{ $existingRole === 'primair' ? 'success' : ($existingRole === 'secundair' ? 'warning' : 'neutral') }}">
                                                    Nu: {{ ucfirst($existingRole) }}
                                                </x-ui.badge>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--font-size-xs); color: var(--color-text-secondary); padding-left: var(--space-6);">
                                    <input
                                        type="radio"
                                        name="primary_user_id"
                                        value="{{ $caregiver->id }}"
                                        {{ $isPrimary ? 'checked' : '' }}
                                        style="accent-color: var(--color-primary-500);"
                                    >
                                    <span>Maak primair</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('clients.show', $client)">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Opslaan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
