@extends('layouts.app')

@section('title', 'Medewerkers — Nexora')

@php
    $user = auth()->user();
    $actief = $members->where('is_active', true)->count();
    $inactief = $members->where('is_active', false)->count();
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Medewerkers</h1>
            <p class="page-subtitle">
                {{ $actief }} actief · {{ $inactief }} inactief
                @if($user->team)
                    · {{ $user->team->name }}
                @endif
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="primary" :href="route('team.create')">
                <x-layout.icon name="plus" :size="16" />
                Medewerker toevoegen
            </x-ui.button>
        </div>
    </div>

    <x-ui.card>
        @if($members->isEmpty())
            <x-ui.empty-state
                title="Nog geen medewerkers"
                description="Maak de eerste medewerker aan via 'Medewerker toevoegen'."
            >
                <x-slot:icon>
                    <x-layout.icon name="users" :size="32" />
                </x-slot:icon>
            </x-ui.empty-state>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                            <th style="padding: var(--space-3) var(--space-4);">Naam</th>
                            <th style="padding: var(--space-3) var(--space-4);">E-mail</th>
                            <th style="padding: var(--space-3) var(--space-4);">Rol</th>
                            <th style="padding: var(--space-3) var(--space-4);">Dienstverband</th>
                            <th style="padding: var(--space-3) var(--space-4);">Status</th>
                            <th style="padding: var(--space-3) var(--space-4);">Aangemaakt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                            <tr style="border-bottom: 1px solid var(--color-border); {{ $member->is_active ? '' : 'opacity: 0.55;' }}">
                                <td style="padding: var(--space-3) var(--space-4); font-weight: var(--font-weight-medium); color: var(--color-ink-900);">{{ $member->name }}</td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary);">{{ $member->email }}</td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="{{ $member->isTeamleider() ? 'warning' : 'neutral' }}">
                                        {{ ucfirst($member->role) }}
                                    </x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary);">{{ ucfirst($member->dienstverband ?? '—') }}</td>
                                <td style="padding: var(--space-3) var(--space-4);">
                                    <x-ui.badge tone="{{ $member->is_active ? 'success' : 'neutral' }}">
                                        {{ $member->is_active ? 'Actief' : 'Inactief' }}
                                    </x-ui.badge>
                                </td>
                                <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-muted);">{{ $member->created_at?->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Zoeken, filters en paginatie komen beschikbaar in US-04. Bewerken in US-05. Deactiveren in US-06.
    </p>
@endsection
