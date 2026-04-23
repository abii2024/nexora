@extends('layouts.app')

@section('title', 'Cliënten — Nexora')

@php
    $user = auth()->user();
    $isTeamleider = $user->isTeamleider();
    $actief = $clients->where('status', 'actief')->count();
    $wacht = $clients->where('status', 'wacht')->count();
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Cliënten</h1>
            <p class="page-subtitle">
                {{ $clients->count() }} {{ $clients->count() === 1 ? 'cliënt' : 'cliënten' }}
                · {{ $actief }} actief · {{ $wacht }} wachtlijst
                @if($user->team)
                    · {{ $user->team->name }}
                @endif
            </p>
        </div>
        <div class="page-actions">
            @can('create', App\Models\Client::class)
                <x-ui.button variant="primary" :href="route('clients.create')">
                    <x-layout.icon name="plus" :size="16" />
                    Cliënt toevoegen
                </x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card>
        @if($clients->isEmpty())
            <x-ui.empty-state
                title="Nog geen cliënten"
                description="{{ $isTeamleider ? 'Maak de eerste cliënt aan via \'Cliënt toevoegen\'.' : 'Er zijn nog geen cliënten aan jou toegewezen. Vraag je teamleider om koppeling.' }}"
            >
                <x-slot:icon>
                    <x-layout.icon name="user" :size="32" />
                </x-slot:icon>
                @if($isTeamleider)
                    <x-slot:action>
                        <x-ui.button variant="primary" :href="route('clients.create')">
                            <x-layout.icon name="plus" :size="16" />
                            Cliënt toevoegen
                        </x-ui.button>
                    </x-slot:action>
                @endif
            </x-ui.empty-state>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                            <th style="padding: var(--space-3) var(--space-4);">Naam</th>
                            <th style="padding: var(--space-3) var(--space-4);">Geboortedatum</th>
                            <th style="padding: var(--space-3) var(--space-4);">Status</th>
                            <th style="padding: var(--space-3) var(--space-4);">Zorgtype</th>
                            <th style="padding: var(--space-3) var(--space-4);">Aangemaakt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: var(--space-3) var(--space-4); font-weight: var(--font-weight-medium);">
                                    <a href="{{ route('clients.show', $client) }}" style="color: var(--color-ink-900); text-decoration: none;">{{ $client->fullName() }}</a>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->geboortedatum?->format('d-m-Y') ?? '—' }}</td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="{{ $client->status === 'actief' ? 'success' : ($client->status === 'wacht' ? 'warning' : 'neutral') }}">
                                        {{ ucfirst($client->status) }}
                                    </x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="neutral">{{ strtoupper($client->care_type) }}</x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-muted);">{{ $client->created_at?->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Zoeken + filter + paginatie komen in US-09. Cliënt koppelen aan begeleiders in US-08. Bewerken in US-10.
    </p>
@endsection
