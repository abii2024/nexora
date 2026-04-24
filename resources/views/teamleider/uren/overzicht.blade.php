@extends('layouts.app')

@section('title', 'Urenoverzicht — Nexora')

@php
    use App\Enums\UrenStatus;

    // Helper voor sorteerbare kolomkoppen: toggled asc/desc bij dezelfde kolom.
    $sortLink = function (string $column, string $label) use ($filters) {
        $current = $filters['sort'] ?? 'datum';
        $dir = $filters['direction'] ?? 'desc';
        $newDir = ($current === $column && $dir === 'desc') ? 'asc' : 'desc';
        $arrow = $current === $column ? ($dir === 'desc' ? ' ↓' : ' ↑') : '';
        $params = array_merge($filters, ['sort' => $column, 'direction' => $newDir]);
        $href = route('teamleider.uren.overzicht', array_filter($params, fn ($v) => $v !== '' && $v !== null));

        return '<a href="'.e($href).'" style="color: inherit; text-decoration: none;">'.e($label).$arrow.'</a>';
    };
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Urenoverzicht</h1>
            <p class="page-subtitle">
                @if($rows->total() > 0)
                    {{ $rows->total() }} {{ $rows->total() === 1 ? 'registratie' : 'registraties' }} in huidige selectie.
                    @if($weekTotal > 0)
                        · Totaal: <strong style="color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ number_format($weekTotal, 2, ',', '') }} u</strong>
                    @endif
                @else
                    Geen uren gevonden met deze filters.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('teamleider.uren.index')">
                <x-layout.icon name="check-square" :size="16" />
                Naar beoordeel-inbox
            </x-ui.button>
        </div>
    </div>

    {{-- Samenvatting per medewerker (AC: header toont Piet: 38,00 · Anna: 38,00 · Totaal week: 76,00 uur) --}}
    @if($weekSummary->isNotEmpty())
        <div style="margin-bottom: var(--space-4); padding: var(--space-4); background: var(--color-canvas-raised); border: 1px solid var(--color-border); border-radius: var(--radius-lg); font-size: var(--font-size-sm); color: var(--color-text-secondary); display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
            @foreach($weekSummary as $naam => $subtotaal)
                <span><strong style="color: var(--color-ink-900);">{{ $naam }}:</strong> <span style="font-variant-numeric: tabular-nums;">{{ number_format($subtotaal, 2, ',', '') }}</span></span>
            @endforeach
            <span style="margin-left: auto; color: var(--color-ink-900); font-weight: var(--font-weight-semibold);">Totaal zichtbaar: <span style="font-variant-numeric: tabular-nums;">{{ number_format($weekTotal, 2, ',', '') }}</span> uur</span>
        </div>
    @endif

    <x-ui.card padded>
        <x-uren.filter-bar :filters="$filters" :medewerkers="$medewerkers" :action="route('teamleider.uren.overzicht')" />
    </x-ui.card>

    <div style="margin-top: var(--space-5);">
        @if($rows->isEmpty())
            <x-ui.card>
                <x-ui.empty-state
                    title="Geen uren gevonden"
                    description="Pas je filters aan of reset om meer resultaten te zien."
                >
                    <x-slot:icon>
                        <x-layout.icon name="clock" :size="32" />
                    </x-slot:icon>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <x-ui.card>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                                <th style="padding: var(--space-3) var(--space-4);">{!! $sortLink('datum', 'Datum') !!}</th>
                                <th style="padding: var(--space-3) var(--space-4);">{!! $sortLink('medewerker', 'Medewerker') !!}</th>
                                <th style="padding: var(--space-3) var(--space-4);">Cliënt</th>
                                <th style="padding: var(--space-3) var(--space-4);">Tijd</th>
                                <th style="padding: var(--space-3) var(--space-4); text-align: right;">{!! $sortLink('duur', 'Uren') !!}</th>
                                <th style="padding: var(--space-3) var(--space-4);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $row->datum?->format('d-m-Y') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900);">{{ $row->user?->name ?? '—' }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary);">{{ $row->client?->fullName() ?? '—' }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ \Carbon\Carbon::parse($row->starttijd)->format('H:i') }} – {{ \Carbon\Carbon::parse($row->eindtijd)->format('H:i') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900); font-variant-numeric: tabular-nums; text-align: right; font-weight: var(--font-weight-medium);">{{ number_format((float) $row->uren, 2, ',', '') }}</td>
                                    <td style="padding: var(--space-3) var(--space-4);">
                                        <x-ui.badge tone="{{ $row->status->badgeTone() }}">{{ $row->status->label() }}</x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($rows->hasPages())
                    <div style="padding: var(--space-4); border-top: 1px solid var(--color-border);">
                        {{ $rows->links() }}
                    </div>
                @endif
            </x-ui.card>
        @endif
    </div>
@endsection
