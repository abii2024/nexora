@extends('layouts.app')

@section('title', 'Uren beoordelen — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Uren beoordelen</h1>
            <p class="page-subtitle">
                @if($totalRows > 0)
                    {{ $totalRows }} ingediende {{ $totalRows === 1 ? 'registratie' : 'registraties' }} wachten op jouw goedkeuring.
                @else
                    Er zijn nu geen ingediende uren om te beoordelen.
                @endif
            </p>
        </div>
    </div>

    @if($totalRows === 0)
        <x-ui.card>
            <x-ui.empty-state
                title="Niets te beoordelen"
                description="Wanneer een zorgbegeleider uren indient, verschijnen ze hier."
            >
                <x-slot:icon>
                    <x-layout.icon name="check-square" :size="32" />
                </x-slot:icon>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        @foreach($groups as $userId => $rows)
            @php
                $medewerker = $rows->first()->user;
                $subTotaal = $rows->sum(fn ($r) => (float) $r->uren);
            @endphp
            <x-ui.card style="margin-bottom: var(--space-5);">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--color-border);">
                    <div>
                        <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">{{ $medewerker?->name ?? '—' }}</h2>
                        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">{{ $rows->count() }} {{ $rows->count() === 1 ? 'registratie' : 'registraties' }}</p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: var(--font-size-xs); color: var(--color-text-muted); display: block;">Subtotaal</span>
                        <span style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ number_format($subTotaal, 2, ',', '') }} u</span>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                        <thead>
                            <tr style="text-align: left; color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                                <th style="padding: var(--space-3) var(--space-4);">Datum</th>
                                <th style="padding: var(--space-3) var(--space-4);">Cliënt</th>
                                <th style="padding: var(--space-3) var(--space-4);">Tijd</th>
                                <th style="padding: var(--space-3) var(--space-4); text-align: right;">Uren</th>
                                <th style="padding: var(--space-3) var(--space-4);">Notities</th>
                                <th style="padding: var(--space-3) var(--space-4); text-align: right;">Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr style="border-top: 1px solid var(--color-border);">
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $row->datum?->format('d-m-Y') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900);">{{ $row->client?->fullName() ?? '—' }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ \Carbon\Carbon::parse($row->starttijd)->format('H:i') }} – {{ \Carbon\Carbon::parse($row->eindtijd)->format('H:i') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); text-align: right; font-variant-numeric: tabular-nums; font-weight: var(--font-weight-medium);">{{ number_format((float) $row->uren, 2, ',', '') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); max-width: 260px;">
                                        @if($row->notities)
                                            <span title="{{ $row->notities }}">{{ \Illuminate\Support\Str::limit($row->notities, 60) }}</span>
                                        @else
                                            <span style="color: var(--color-text-muted);">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: var(--space-3) var(--space-4); text-align: right;">
                                        <div style="display: inline-flex; gap: var(--space-2); justify-content: flex-end;">
                                            <form method="POST" action="{{ route('teamleider.uren.approve', $row) }}" style="display: inline;">
                                                @csrf
                                                <x-ui.button type="submit" variant="primary">Goedkeuren</x-ui.button>
                                            </form>
                                            <x-ui.button variant="danger" type="button" onclick="document.getElementById('afkeur-dialog-{{ $row->id }}').showModal()">Afkeuren</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Afkeur-modals (één per rij zodat de context in de form zit) --}}
            @foreach($rows as $row)
                <dialog id="afkeur-dialog-{{ $row->id }}" style="border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 0; background: var(--color-canvas-raised); color: var(--color-ink-900); max-width: 540px; width: 90vw; box-shadow: var(--shadow-md);">
                    <form method="POST" action="{{ route('teamleider.uren.reject', $row) }}" style="padding: var(--space-5); display: flex; flex-direction: column; gap: var(--space-4);">
                        @csrf
                        <div>
                            <h2 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">Uren afkeuren</h2>
                            <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-top: var(--space-2);">
                                {{ $medewerker?->name }} · {{ $row->datum?->format('d-m-Y') }} · {{ $row->client?->fullName() }} ({{ number_format((float) $row->uren, 2, ',', '') }} u)
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="teamleider_notitie_{{ $row->id }}" class="form-label">Reden (minstens 10 tekens) <span style="color: var(--color-danger);">*</span></label>
                            <textarea
                                id="teamleider_notitie_{{ $row->id }}"
                                name="teamleider_notitie"
                                rows="4"
                                minlength="10"
                                maxlength="500"
                                required
                                class="form-input"
                                placeholder="Leg uit waarom je afkeurt zodat de medewerker kan corrigeren."
                            ></textarea>
                            @error('teamleider_notitie')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; gap: var(--space-3); justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: var(--space-4);">
                            <x-ui.button type="button" variant="secondary" onclick="document.getElementById('afkeur-dialog-{{ $row->id }}').close()">Annuleren</x-ui.button>
                            <x-ui.button type="submit" variant="danger">Afkeuren en reden verzenden</x-ui.button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        @endforeach
    @endif

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Urenoverzicht met filters (goedgekeurd + afgekeurd) volgt in US-14.
    </p>
@endsection
