@extends('layouts.app')

@section('title', $client->fullName() . ' bewerken — Nexora')

@php
    $caregivers = $client->caregivers;
    $defaultCheckedIds = old('caregiver_ids', $caregivers->pluck('id')->map(fn ($id) => (string) $id)->all());
    $primair = $caregivers->first(fn ($c) => $c->pivot->role === 'primair');
    $defaultPrimary = old('primary_user_id', $primair?->id);
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Cliënt bewerken</h1>
            <p class="page-subtitle">Wijzig gegevens en begeleiders van <strong>{{ $client->fullName() }}</strong>.</p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('clients.show', $client)">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar detail
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

            <form method="POST" action="{{ route('clients.update', $client) }}" style="display: flex; flex-direction: column; gap: var(--space-6);">
                @csrf
                @method('PUT')

                {{-- ── 1. Persoonlijk ─────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Persoonlijk</h2>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input name="voornaam" label="Voornaam" required :value="old('voornaam', $client->voornaam)" />
                        <x-ui.input name="achternaam" label="Achternaam" required :value="old('achternaam', $client->achternaam)" />
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input
                            name="geboortedatum"
                            label="Geboortedatum"
                            type="date"
                            :value="old('geboortedatum', $client->geboortedatum?->format('Y-m-d'))"
                            :attributes="new \Illuminate\View\ComponentAttributeBag(['max' => now()->format('Y-m-d')])"
                        />
                        <x-ui.input
                            name="bsn"
                            label="BSN"
                            hint="9 cijfers — optioneel"
                            inputmode="numeric"
                            pattern="\d{9}"
                            maxlength="9"
                            :value="old('bsn', $client->bsn)"
                        />
                    </div>
                </section>

                {{-- ── 2. Contact ────────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Contact</h2>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <x-ui.input name="email" label="E-mailadres" type="email" :value="old('email', $client->email)" />
                        <x-ui.input name="telefoon" label="Telefoonnummer" type="tel" :value="old('telefoon', $client->telefoon)" />
                    </div>
                </section>

                {{-- ── 3. Zorg ───────────────────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Zorg</h2>
                        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">Statuswijzigingen worden automatisch gelogd in het audit-trail (AVG art. 5).</p>
                    </header>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                        <div class="space-y-2">
                            @php $currentStatus = old('status', $client->status); @endphp
                            <label for="status" class="form-label">Status <span style="color: var(--color-danger);">*</span></label>
                            <select id="status" name="status" required class="form-input">
                                <option value="actief" {{ $currentStatus === 'actief' ? 'selected' : '' }}>Actief</option>
                                <option value="wacht" {{ $currentStatus === 'wacht' ? 'selected' : '' }}>Wachtlijst</option>
                                <option value="inactief" {{ $currentStatus === 'inactief' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            @error('status')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            @php $currentCareType = old('care_type', $client->care_type); @endphp
                            <label for="care_type" class="form-label">Zorgtype <span style="color: var(--color-danger);">*</span></label>
                            <select id="care_type" name="care_type" required class="form-input">
                                <option value="wmo" {{ $currentCareType === 'wmo' ? 'selected' : '' }}>WMO — Wet maatschappelijke ondersteuning</option>
                                <option value="wlz" {{ $currentCareType === 'wlz' ? 'selected' : '' }}>WLZ — Wet langdurige zorg</option>
                                <option value="jw" {{ $currentCareType === 'jw' ? 'selected' : '' }}>JW — Jeugdwet</option>
                            </select>
                            @error('care_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- ── 4. Begeleiders ──────────────────────────────── --}}
                <section style="display: flex; flex-direction: column; gap: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <header>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Begeleiders</h2>
                    </header>

                    @if($availableCaregivers->isEmpty())
                        <div style="padding: var(--space-4); background: var(--color-ink-50); border-radius: var(--radius-md); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                            Geen actieve zorgbegeleiders in je team beschikbaar.
                        </div>
                    @else
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-3);">
                            @foreach($availableCaregivers as $caregiver)
                                @php
                                    $isChecked = in_array((string) $caregiver->id, array_map('strval', (array) $defaultCheckedIds), true);
                                    $isPrimary = (string) $defaultPrimary === (string) $caregiver->id;
                                @endphp
                                <label style="display: flex; align-items: flex-start; gap: var(--space-3); padding: var(--space-3); border: 1px solid var(--color-border); border-radius: var(--radius-md); cursor: pointer;">
                                    <input type="checkbox" name="caregiver_ids[]" value="{{ $caregiver->id }}" {{ $isChecked ? 'checked' : '' }} style="margin-top: 2px; accent-color: var(--color-ink-900);">
                                    <span style="flex: 1;">
                                        <span style="display: block; font-weight: var(--font-weight-medium); color: var(--color-ink-900); font-size: var(--font-size-sm);">{{ $caregiver->name }}</span>
                                        <span style="display: block; font-size: var(--font-size-xs); color: var(--color-text-muted);">{{ $caregiver->email }}</span>
                                        <span style="display: inline-flex; align-items: center; gap: var(--space-1); margin-top: var(--space-2); font-size: var(--font-size-xs); color: var(--color-text-secondary);">
                                            <input type="radio" name="primary_user_id" value="{{ $caregiver->id }}" {{ $isPrimary ? 'checked' : '' }} style="accent-color: var(--color-primary-500);">
                                            Primair
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('clients.show', $client)">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <x-layout.icon name="check" :size="16" />
                        Opslaan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- ── Archiveren (AC-3) ─────────────────────────────────────────── --}}
        <x-ui.card padded style="margin-top: var(--space-5); border-color: var(--color-danger-200, var(--color-border));">
            <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); margin-bottom: var(--space-2);">Cliënt archiveren</h2>
            <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);">
                Archiveren zet de cliënt op een gearchiveerd-overzicht. Historische data (uren, koppelingen, audit-logs) blijft bewaard conform Wgbo 20-jaar termijn. Herstellen blijft altijd mogelijk.
            </p>
            <form method="POST" action="{{ route('clients.archive', $client) }}" onsubmit="return confirm('Cliënt {{ $client->fullName() }} archiveren? Historische data blijft bewaard.');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">
                    <x-layout.icon name="archive" :size="16" />
                    Archiveren
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
