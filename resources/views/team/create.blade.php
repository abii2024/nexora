@extends('layouts.app')

@section('title', 'Medewerker toevoegen — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Medewerker toevoegen</h1>
            <p class="page-subtitle">
                Nieuwe zorgbegeleider of teamleider voor
                @if(auth()->user()->team)
                    {{ auth()->user()->team->name }}.
                @else
                    het team.
                @endif
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

            <form method="POST" action="{{ route('team.store') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <x-ui.input name="voornaam" label="Voornaam" required autocomplete="given-name" />
                    <x-ui.input name="achternaam" label="Achternaam" required autocomplete="family-name" />
                </div>

                <x-ui.input
                    name="email"
                    label="E-mailadres"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="voornaam.achternaam@organisatie.nl"
                />

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="space-y-2">
                        <label for="role" class="form-label">Rol <span style="color: var(--color-danger);">*</span></label>
                        <select id="role" name="role" required class="form-input">
                            <option value="zorgbegeleider" {{ old('role', 'zorgbegeleider') === 'zorgbegeleider' ? 'selected' : '' }}>Zorgbegeleider</option>
                            <option value="teamleider" {{ old('role') === 'teamleider' ? 'selected' : '' }}>Teamleider</option>
                        </select>
                        @error('role')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="dienstverband" class="form-label">Dienstverband <span style="color: var(--color-danger);">*</span></label>
                        <select id="dienstverband" name="dienstverband" required class="form-input">
                            <option value="intern" {{ old('dienstverband', 'intern') === 'intern' ? 'selected' : '' }}>Intern</option>
                            <option value="extern" {{ old('dienstverband') === 'extern' ? 'selected' : '' }}>Extern</option>
                            <option value="zzp" {{ old('dienstverband') === 'zzp' ? 'selected' : '' }}>ZZP</option>
                        </select>
                        @error('dienstverband')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div style="border-top: 1px solid var(--color-border); padding-top: var(--space-5); margin-top: var(--space-2);">
                    <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); margin-bottom: var(--space-1);">Initieel wachtwoord</h2>
                    <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-bottom: var(--space-4);">
                        Communiceer dit wachtwoord veilig buiten de applicatie (bijv. mondeling of via een tweede kanaal). De medewerker wijzigt het zelf via /profiel zodra beschikbaar (US-16).
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input
                            name="password"
                            label="Wachtwoord"
                            type="password"
                            required
                            hint="minimaal 8 tekens"
                            autocomplete="new-password"
                        />

                        <x-ui.input
                            name="password_confirmation"
                            label="Wachtwoord bevestigen"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('team.index')">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <x-layout.icon name="plus" :size="16" />
                        Medewerker aanmaken
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
