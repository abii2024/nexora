@extends('layouts.app')

@section('title', 'Uren toevoegen — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Uren toevoegen</h1>
            <p class="page-subtitle">Nieuwe concept-registratie — indienen doe je later vanuit het overzicht.</p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('uren.index')">
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

            @if($clients->isEmpty())
                <x-ui.empty-state
                    title="Geen cliënten toegewezen"
                    description="Vraag je teamleider om je aan een cliënt te koppelen voordat je uren kunt registreren."
                >
                    <x-slot:icon>
                        <x-layout.icon name="user" :size="32" />
                    </x-slot:icon>
                </x-ui.empty-state>
            @else
                <form method="POST" action="{{ route('uren.store') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
                    @csrf

                    <div class="space-y-2">
                        <label for="client_id" class="form-label">Cliënt <span style="color: var(--color-danger);">*</span></label>
                        <select id="client_id" name="client_id" required class="form-input">
                            <option value="">— Kies cliënt —</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ (string) old('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->fullName() }}</option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input
                            name="datum"
                            label="Datum"
                            type="date"
                            required
                            :value="old('datum', now()->format('Y-m-d'))"
                            :attributes="new \Illuminate\View\ComponentAttributeBag(['max' => now()->format('Y-m-d')])"
                        />
                        <x-ui.input
                            name="starttijd"
                            label="Starttijd"
                            type="time"
                            required
                            :value="old('starttijd', '09:00')"
                        />
                        <x-ui.input
                            name="eindtijd"
                            label="Eindtijd"
                            type="time"
                            required
                            :value="old('eindtijd', '12:00')"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="notities" class="form-label">Notities</label>
                        <textarea id="notities" name="notities" rows="4" class="form-input" placeholder="Korte omschrijving van de activiteiten — geen bijzondere persoonsgegevens noteren.">{{ old('notities') }}</textarea>
                        @error('notities')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                        <x-ui.button variant="secondary" :href="route('uren.index')">Annuleren</x-ui.button>
                        <x-ui.button type="submit" variant="primary">
                            <x-layout.icon name="plus" :size="16" />
                            Opslaan als concept
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card>
    </div>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Dataminimalisatie: geen GPS, geen locatietracking. Duur wordt server-side berekend.
    </p>
@endsection
