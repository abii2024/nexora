@extends('layouts.app')

@section('title', 'Cliënt toevoegen — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Cliënt toevoegen</h1>
            <p class="page-subtitle">
                Nieuwe cliënt voor
                @if(auth()->user()->team)
                    {{ auth()->user()->team->name }}.
                @else
                    het team.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('clients.index')">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar overzicht
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

            <form method="POST" action="{{ route('clients.store') }}" style="display: flex; flex-direction: column; gap: var(--space-6);">
                @csrf

                {{-- ── 1. Persoonlijk ─────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Persoonlijk</h2>
                        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">Naam, geboortedatum en BSN. Vul alleen BSN in als je dat strikt nodig hebt (AVG-dataminimalisatie).</p>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input name="voornaam" label="Voornaam" required autocomplete="given-name" />
                        <x-ui.input name="achternaam" label="Achternaam" required autocomplete="family-name" />
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input
                            name="geboortedatum"
                            label="Geboortedatum"
                            type="date"
                            :attributes="new \Illuminate\View\ComponentAttributeBag(['max' => now()->format('Y-m-d')])"
                        />
                        <x-ui.input
                            name="bsn"
                            label="BSN"
                            hint="9 cijfers — optioneel"
                            inputmode="numeric"
                            pattern="\d{9}"
                            maxlength="9"
                        />
                    </div>
                </section>

                {{-- ── 2. Contact ────────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Contact</h2>
                        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">E-mail en telefoon zijn optioneel.</p>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input name="email" label="E-mailadres" type="email" autocomplete="email" placeholder="naam@example.nl" />
                        <x-ui.input name="telefoon" label="Telefoonnummer" type="tel" autocomplete="tel" placeholder="06 12 34 56 78" />
                    </div>
                </section>

                {{-- ── 3. Zorg ───────────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Zorg</h2>
                        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">Status en zorgtype bepalen waar de cliënt in het proces zit en welke financieringsstroom van toepassing is.</p>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <div class="space-y-2">
                            <label for="status" class="form-label">Status <span style="color: var(--color-danger);">*</span></label>
                            <select id="status" name="status" required class="form-input">
                                <option value="actief" {{ old('status', 'actief') === 'actief' ? 'selected' : '' }}>Actief</option>
                                <option value="wacht" {{ old('status') === 'wacht' ? 'selected' : '' }}>Wachtlijst</option>
                                <option value="inactief" {{ old('status') === 'inactief' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            @error('status')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="care_type" class="form-label">Zorgtype <span style="color: var(--color-danger);">*</span></label>
                            <select id="care_type" name="care_type" required class="form-input">
                                <option value="wmo" {{ old('care_type', 'wmo') === 'wmo' ? 'selected' : '' }}>WMO — Wet maatschappelijke ondersteuning</option>
                                <option value="wlz" {{ old('care_type') === 'wlz' ? 'selected' : '' }}>WLZ — Wet langdurige zorg</option>
                                <option value="jw" {{ old('care_type') === 'jw' ? 'selected' : '' }}>JW — Jeugdwet</option>
                            </select>
                            @error('care_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('clients.index')">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <x-layout.icon name="plus" :size="16" />
                        Cliënt aanmaken
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
