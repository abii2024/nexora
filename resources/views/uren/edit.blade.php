@extends('layouts.app')

@section('title', 'Uren bewerken — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Uren bewerken</h1>
            <p class="page-subtitle">
                Status:
                <x-ui.badge tone="{{ $uren->status->badgeTone() }}">{{ $uren->status->label() }}</x-ui.badge>
                — wijzigen kan omdat deze nog niet definitief is goedgekeurd.
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('uren.index', ['status' => $uren->status->value])">
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

            <form method="POST" action="{{ route('uren.update', $uren) }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="client_id" class="form-label">Cliënt <span style="color: var(--color-danger);">*</span></label>
                    <select id="client_id" name="client_id" required class="form-input">
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ (string) old('client_id', $uren->client_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->fullName() }}</option>
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
                        :value="old('datum', $uren->datum?->format('Y-m-d'))"
                        :attributes="new \Illuminate\View\ComponentAttributeBag(['max' => now()->format('Y-m-d')])"
                    />
                    <x-ui.input
                        name="starttijd"
                        label="Starttijd"
                        type="time"
                        required
                        :value="old('starttijd', \Carbon\Carbon::parse($uren->starttijd)->format('H:i'))"
                    />
                    <x-ui.input
                        name="eindtijd"
                        label="Eindtijd"
                        type="time"
                        required
                        :value="old('eindtijd', \Carbon\Carbon::parse($uren->eindtijd)->format('H:i'))"
                    />
                </div>

                <div class="space-y-2">
                    <label for="notities" class="form-label">Notities</label>
                    <textarea id="notities" name="notities" rows="4" class="form-input">{{ old('notities', $uren->notities) }}</textarea>
                    @error('notities')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-5);">
                    <x-ui.button variant="secondary" :href="route('uren.index', ['status' => $uren->status->value])">Annuleren</x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <x-layout.icon name="check" :size="16" />
                        Opslaan
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
