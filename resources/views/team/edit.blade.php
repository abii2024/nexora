@extends('layouts.app')

@section('title', 'Medewerker bewerken — Nexora')

@php
    // User.name is een combinatie van voornaam + achternaam; split voor pre-fill.
    $parts = explode(' ', $member->name, 2);
    $voornaam = $parts[0] ?? '';
    $achternaam = $parts[1] ?? '';
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Medewerker bewerken</h1>
            <p class="page-subtitle">
                {{ $member->name }} · {{ $member->email }}
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('team.index')">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar overzicht
            </x-ui.button>
        </div>
    </div>

    <div style="max-width: 720px;">
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

            <form method="POST" action="{{ route('team.update', $member) }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <x-ui.input name="voornaam" label="Voornaam" :value="$voornaam" required autocomplete="given-name" />
                    <x-ui.input name="achternaam" label="Achternaam" :value="$achternaam" required autocomplete="family-name" />
                </div>

                <x-ui.input
                    name="email"
                    label="E-mailadres"
                    type="email"
                    :value="$member->email"
                    required
                    autocomplete="email"
                />

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="space-y-2">
                        <label for="role" class="form-label">Rol <span style="color: var(--color-danger);">*</span></label>
                        <select id="role" name="role" required class="form-input">
                            <option value="zorgbegeleider" {{ old('role', $member->role) === 'zorgbegeleider' ? 'selected' : '' }}>Zorgbegeleider</option>
                            <option value="teamleider" {{ old('role', $member->role) === 'teamleider' ? 'selected' : '' }}>Teamleider</option>
                        </select>
                        @error('role')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="dienstverband" class="form-label">Dienstverband <span style="color: var(--color-danger);">*</span></label>
                        <select id="dienstverband" name="dienstverband" required class="form-input">
                            <option value="intern" {{ old('dienstverband', $member->dienstverband) === 'intern' ? 'selected' : '' }}>Intern</option>
                            <option value="extern" {{ old('dienstverband', $member->dienstverband) === 'extern' ? 'selected' : '' }}>Extern</option>
                            <option value="zzp" {{ old('dienstverband', $member->dienstverband) === 'zzp' ? 'selected' : '' }}>ZZP</option>
                        </select>
                        @error('dienstverband')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div style="border-top: 1px solid var(--color-border); padding-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
                    Het wachtwoord kan niet hier gewijzigd worden. De medewerker past dit zelf aan via
                    <em>Profiel</em> (beschikbaar vanaf US-16).
                </div>

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('team.index')">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Opslaan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
