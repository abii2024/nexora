@extends('layouts.app')

@section('title', 'Teamleider dashboard — Nexora')

@php
    $user = auth()->user();
    $greeting = match(true) {
        now()->hour < 12 => 'Goedemorgen',
        now()->hour < 18 => 'Goedemiddag',
        default => 'Goedenavond',
    };
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $greeting }}, {{ explode(' ', $user->name)[0] }}</h1>
            <p class="page-subtitle">
                Teamoverzicht
                @if($user->team)
                    — {{ $user->team->name }}
                @endif
                · beheer je team en verwerk ingediende uren.
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="secondary" :href="route('teamleider.uren.index')">
                Uren verwerken
            </x-ui.button>
            <x-ui.button variant="primary" :href="route('team.create')">
                <x-layout.icon name="plus" :size="16" />
                Medewerker toevoegen
            </x-ui.button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-5); margin-bottom: var(--space-8);">
        <x-ui.stats-card
            label="Teamleden"
            value="0"
            tone="mint"
            hint="in mijn team"
        >
            <x-slot:icon>
                <x-layout.icon name="users" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>

        <x-ui.stats-card
            label="Uren ter goedkeuring"
            value="0"
            tone="amber"
            hint="wachten op beoordeling"
        >
            <x-slot:icon>
                <x-layout.icon name="clock" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>

        <x-ui.stats-card
            label="Actieve cliënten"
            value="0"
            tone="blue"
            hint="in beheer"
        >
            <x-slot:icon>
                <x-layout.icon name="user" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>

        <x-ui.stats-card
            label="Goedgekeurd"
            value="0u"
            tone="green"
            hint="week {{ now()->weekOfYear }}"
        >
            <x-slot:icon>
                <x-layout.icon name="check-square" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: var(--space-5);">
        <x-ui.card title="Ingediende uren" subtitle="Wacht op goedkeuring">
            <x-slot:actions>
                <a href="{{ route('teamleider.uren.overzicht') }}" style="font-size: var(--font-size-sm); color: var(--color-primary-600); font-weight: var(--font-weight-medium); display: inline-flex; align-items: center; gap: var(--space-1);">
                    Alle uren
                    <x-layout.icon name="arrow-right" :size="14" />
                </a>
            </x-slot:actions>

            <x-ui.empty-state
                title="Nog geen ingediende uren"
                description="Wanneer een zorgbegeleider uren indient verschijnen ze hier ter beoordeling."
            >
                <x-slot:icon>
                    <x-layout.icon name="clock" :size="32" />
                </x-slot:icon>
            </x-ui.empty-state>
        </x-ui.card>

        <x-ui.card title="Cliënt-toewijzingen" subtitle="Nog te koppelen">
            <x-slot:actions>
                <a href="{{ route('clients.index') }}" style="font-size: var(--font-size-sm); color: var(--color-primary-600); font-weight: var(--font-weight-medium); display: inline-flex; align-items: center; gap: var(--space-1);">
                    Alle toewijzingen
                    <x-layout.icon name="arrow-right" :size="14" />
                </a>
            </x-slot:actions>

            <x-ui.empty-state
                title="Geen open toewijzingen"
                description="Cliënten zonder primaire zorgbegeleider verschijnen hier — koppel ze via het cliëntdetail."
            >
                <x-slot:icon>
                    <x-layout.icon name="users" :size="32" />
                </x-slot:icon>
            </x-ui.empty-state>
        </x-ui.card>
    </div>
@endsection
