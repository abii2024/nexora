@extends('layouts.app')

@section('title', 'Gearchiveerde cliënten — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Gearchiveerde cliënten</h1>
            <p class="page-subtitle">
                {{ $clients->total() }} {{ $clients->total() === 1 ? 'cliënt' : 'cliënten' }} gearchiveerd. Historische data blijft bewaard (Wgbo 20 jaar).
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('clients.index')">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar actief overzicht
            </x-ui.button>
        </div>
    </div>

    @if($clients->isEmpty())
        <x-ui.card>
            <x-ui.empty-state
                title="Nog geen gearchiveerde cliënten"
                description="Cliënten die je archiveert, verschijnen hier. Je kunt ze daar altijd herstellen."
            >
                <x-slot:icon>
                    <x-layout.icon name="archive" :size="32" />
                </x-slot:icon>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <x-ui.card>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                            <th style="padding: var(--space-3) var(--space-4);">Naam</th>
                            <th style="padding: var(--space-3) var(--space-4);">Zorgtype</th>
                            <th style="padding: var(--space-3) var(--space-4);">Gearchiveerd op</th>
                            <th style="padding: var(--space-3) var(--space-4); text-align: right;">Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: var(--space-3) var(--space-4); font-weight: var(--font-weight-medium); color: var(--color-ink-900);">{{ $client->fullName() }}</td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="neutral">{{ strtoupper($client->care_type) }}</x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->deleted_at?->format('d-m-Y H:i') ?? '—' }}</td>
                                <td style="padding: var(--space-3) var(--space-4); text-align: right;">
                                    <form method="POST" action="{{ route('clients.restore', $client->id) }}" style="display: inline;">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary">
                                            <x-layout.icon name="rotate-ccw" :size="14" />
                                            Herstellen
                                        </x-ui.button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($clients->hasPages())
                <div style="padding: var(--space-4); border-top: 1px solid var(--color-border);">
                    {{ $clients->links() }}
                </div>
            @endif
        </x-ui.card>
    @endif

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Permanente verwijdering is bewust niet beschikbaar in de UI — dataverlies-preventie conform Wgbo.
    </p>
@endsection
