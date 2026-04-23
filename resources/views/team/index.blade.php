@extends('layouts.app')

@section('title', 'Medewerkers — Nexora')

@php
    $user = auth()->user();
    $hasFilters = $filters['search'] !== '' || $filters['role'] || $filters['status'];
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Medewerkers</h1>
            <p class="page-subtitle">
                {{ $totals['actief'] }} actief · {{ $totals['inactief'] }} inactief
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

    <x-ui.card padded>
        <form method="GET" action="{{ route('team.index') }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: var(--space-3); align-items: end;">
            <div class="space-y-2">
                <label for="search" class="form-label">Zoeken</label>
                <input
                    id="search"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Naam of e-mail"
                    class="form-input"
                >
            </div>

            <div class="space-y-2">
                <label for="role" class="form-label">Rol</label>
                <select id="role" name="role" class="form-input">
                    <option value="">Alle rollen</option>
                    <option value="teamleider" {{ $filters['role'] === 'teamleider' ? 'selected' : '' }}>Teamleider</option>
                    <option value="zorgbegeleider" {{ $filters['role'] === 'zorgbegeleider' ? 'selected' : '' }}>Zorgbegeleider</option>
                </select>
            </div>

            <div class="space-y-2">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-input">
                    <option value="">Alle statussen</option>
                    <option value="actief" {{ $filters['status'] === 'actief' ? 'selected' : '' }}>Actief</option>
                    <option value="inactief" {{ $filters['status'] === 'inactief' ? 'selected' : '' }}>Inactief</option>
                </select>
            </div>

            <div style="display: flex; gap: var(--space-2);">
                <x-ui.button type="submit" variant="primary">Filter</x-ui.button>
                @if($hasFilters)
                    <x-ui.button variant="ghost" :href="route('team.index')">Reset</x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    <div style="margin-top: var(--space-5);">
        <x-ui.card>
            @if($members->isEmpty())
                @if($hasFilters)
                    <x-ui.empty-state
                        title="Geen medewerkers gevonden"
                        description="Er zijn geen medewerkers die aan deze filters voldoen. Pas je zoek-term of filters aan."
                    >
                        <x-slot:icon>
                            <x-layout.icon name="users" :size="32" />
                        </x-slot:icon>
                        <x-slot:action>
                            <x-ui.button variant="secondary" :href="route('team.index')">Filters resetten</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state
                        title="Nog geen medewerkers"
                        description="Maak de eerste medewerker aan via 'Medewerker toevoegen'."
                    >
                        <x-slot:icon>
                            <x-layout.icon name="users" :size="32" />
                        </x-slot:icon>
                        <x-slot:action>
                            <x-ui.button variant="primary" :href="route('team.create')">
                                <x-layout.icon name="plus" :size="16" />
                                Medewerker toevoegen
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @endif
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
                                <th style="padding: var(--space-3) var(--space-4); text-align: right;">Acties</th>
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
                                    <td style="padding: var(--space-3) var(--space-4); text-align: right;">
                                        <a
                                            href="{{ route('team.edit', $member) }}"
                                            title="Bewerken"
                                            aria-label="Bewerken {{ $member->name }}"
                                            style="display: inline-flex; align-items: center; gap: var(--space-1); color: var(--color-ink-700); padding: var(--space-1) var(--space-2); border-radius: var(--radius-md); font-size: var(--font-size-xs); font-weight: var(--font-weight-medium);"
                                            onmouseover="this.style.background='var(--color-ink-50)';this.style.color='var(--color-ink-900)'"
                                            onmouseout="this.style.background='transparent';this.style.color='var(--color-ink-700)'"
                                        >
                                            <x-layout.icon name="settings" :size="14" />
                                            Bewerken
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($members->hasPages())
                    <div style="padding: var(--space-4); border-top: 1px solid var(--color-border);">
                        {{ $members->links() }}
                    </div>
                @endif
            @endif
        </x-ui.card>
    </div>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Bewerken (US-05) en deactiveren (US-06) volgen in sprint 2.
    </p>
@endsection
