@extends('layouts.app')

@section('title', 'Urenregistratie — Nexora')

@php
    use App\Enums\UrenStatus;
    $user = auth()->user();
    $isZorgbeg = $user->isZorgbegeleider();
    $tabs = [
        ['value' => UrenStatus::Concept, 'label' => 'Concepten'],
        ['value' => UrenStatus::Ingediend, 'label' => 'Ingediend'],
        ['value' => UrenStatus::Goedgekeurd, 'label' => 'Goedgekeurd'],
        ['value' => UrenStatus::Afgekeurd, 'label' => 'Afgekeurd'],
    ];
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Urenregistratie</h1>
            <p class="page-subtitle">
                @if($isZorgbeg)
                    Jouw uren per cliënt — concepten bijwerken, indienen of overzicht bekijken.
                @else
                    Overzicht van ingediende en beoordeelde uren.
                @endif
            </p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Urenregistratie::class)
                <x-ui.button variant="primary" :href="route('uren.create')">
                    <x-layout.icon name="plus" :size="16" />
                    Uren toevoegen
                </x-ui.button>
            @endcan
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display: flex; gap: var(--space-2); border-bottom: 1px solid var(--color-border); margin-bottom: var(--space-5); overflow-x: auto;">
        @foreach($tabs as $tab)
            @php
                $isActive = $activeStatus === $tab['value'];
                $count = $counts[$tab['value']->value] ?? 0;
            @endphp
            <a
                href="{{ route('uren.index', ['status' => $tab['value']->value]) }}"
                style="padding: var(--space-3) var(--space-4); font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: {{ $isActive ? 'var(--color-ink-900)' : 'var(--color-text-secondary)' }}; border-bottom: 2px solid {{ $isActive ? 'var(--color-primary-500)' : 'transparent' }}; text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-2); white-space: nowrap;"
            >
                {{ $tab['label'] }}
                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; padding: 0 6px; background: {{ $isActive ? 'var(--color-primary-100)' : 'var(--color-ink-100, var(--color-canvas-raised))' }}; color: {{ $isActive ? 'var(--color-primary-700)' : 'var(--color-text-muted)' }}; border-radius: var(--radius-full, 9999px); font-size: var(--font-size-xs); font-variant-numeric: tabular-nums;">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    @if($rows->isEmpty())
        @php
            $showCreateAction = $activeStatus === UrenStatus::Concept && auth()->user()->can('create', App\Models\Urenregistratie::class);
            $emptyDescription = $activeStatus === UrenStatus::Concept
                ? 'Klik "Uren toevoegen" om je eerste concept te maken.'
                : 'Er zijn geen uren met deze status.';
        @endphp
        <x-ui.card>
            @if($showCreateAction)
                <x-ui.empty-state title="Nog geen uren in deze tab" :description="$emptyDescription">
                    <x-slot:icon><x-layout.icon name="clock" :size="32" /></x-slot:icon>
                    <x-slot:action>
                        <x-ui.button variant="primary" :href="route('uren.create')">
                            <x-layout.icon name="plus" :size="16" />
                            Uren toevoegen
                        </x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state title="Nog geen uren in deze tab" :description="$emptyDescription">
                    <x-slot:icon><x-layout.icon name="clock" :size="32" /></x-slot:icon>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    @else
        <x-ui.card>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                            <th style="padding: var(--space-3) var(--space-4);">Datum</th>
                            <th style="padding: var(--space-3) var(--space-4);">Cliënt</th>
                            <th style="padding: var(--space-3) var(--space-4);">Tijd</th>
                            <th style="padding: var(--space-3) var(--space-4); text-align: right;">Uren</th>
                            <th style="padding: var(--space-3) var(--space-4);">Status</th>
                            <th style="padding: var(--space-3) var(--space-4); text-align: right;">Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $row->datum?->format('d-m-Y') }}</td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900);">{{ $row->client?->fullName() ?? '—' }}</td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ \Carbon\Carbon::parse($row->starttijd)->format('H:i') }} – {{ \Carbon\Carbon::parse($row->eindtijd)->format('H:i') }}</td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-ink-900); font-variant-numeric: tabular-nums; text-align: right; font-weight: var(--font-weight-medium);">{{ number_format((float) $row->uren, 2, ',', '') }}</td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="{{ $row->status->badgeTone() }}">{{ $row->status->label() }}</x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); text-align: right;">
                                    <div style="display: inline-flex; gap: var(--space-2); justify-content: flex-end; align-items: center;">
                                        @can('update', $row)
                                            <x-ui.button variant="ghost" :href="route('uren.edit', $row)">Bewerken</x-ui.button>
                                        @endcan
                                        @can('submit', $row)
                                            <form method="POST" action="{{ route('uren.submit', $row) }}" style="display: inline;">
                                                @csrf
                                                <x-ui.button type="submit" variant="primary">Indienen</x-ui.button>
                                            </form>
                                        @endcan
                                        @can('withdraw', $row)
                                            <form method="POST" action="{{ route('uren.withdraw', $row) }}" style="display: inline;" onsubmit="return confirm('Deze uren-registratie terugtrekken naar concept?');">
                                                @csrf
                                                <x-ui.button type="submit" variant="secondary">Terugtrekken</x-ui.button>
                                            </form>
                                        @endcan
                                        @if($row->status === UrenStatus::Goedgekeurd)
                                            <span style="color: var(--color-text-muted); font-size: var(--font-size-xs);">Read-only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div style="padding: var(--space-4); border-top: 1px solid var(--color-border);">
                    {{ $rows->withQueryString()->links() }}
                </div>
            @endif
        </x-ui.card>
    @endif

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Indienen + terugtrekken + opnieuw indienen komt in US-12. Goedkeuren + afkeuren in US-13.
    </p>
@endsection
