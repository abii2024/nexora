@extends('layouts.app')

@section('title', 'Dashboard — Nexora')

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
            <p class="page-subtitle">Zorgbegeleider · overzicht van je caseload en uren.</p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="secondary" href="#" title="Komt in US-11">
                <x-layout.icon name="clock" :size="16" />
                Uren registreren
            </x-ui.button>
            <x-ui.button variant="primary" href="#" title="Komt in US-11">
                <x-layout.icon name="plus" :size="16" />
                Nieuwe uren
            </x-ui.button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-5); margin-bottom: var(--space-8);">
        <x-ui.stats-card
            label="Mijn cliënten"
            value="0"
            tone="mint"
            hint="Nog geen gekoppelde cliënten"
        >
            <x-slot:icon>
                <x-layout.icon name="users" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>

        <x-ui.stats-card
            label="Concept-uren"
            value="0"
            tone="amber"
            hint="Nog niet ingediend"
        >
            <x-slot:icon>
                <x-layout.icon name="clock" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>

        <x-ui.stats-card
            label="Goedgekeurd deze week"
            value="0u"
            tone="green"
            hint="Week {{ now()->weekOfYear }}"
        >
            <x-slot:icon>
                <x-layout.icon name="check-square" :size="18" />
            </x-slot:icon>
        </x-ui.stats-card>
    </div>

    <x-ui.card title="Aan de slag" subtitle="De komende user stories maken deze functionaliteiten beschikbaar.">
        <ul style="display: flex; flex-direction: column; gap: var(--space-3); list-style: none; padding: 0;">
            <li style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-mint); color: var(--color-accent-mint-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="users" :size="16" /></span>
                Cliëntenoverzicht — <span class="badge badge-neutral">US-09</span>
            </li>
            <li style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-amber); color: var(--color-accent-amber-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="clock" :size="16" /></span>
                Urenregistratie — <span class="badge badge-neutral">US-11</span>
            </li>
            <li style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-purple); color: var(--color-accent-purple-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="user" :size="16" /></span>
                Profielbeheer — <span class="badge badge-neutral">US-16</span>
            </li>
        </ul>
    </x-ui.card>
@endsection
