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
            <x-ui.button variant="secondary" :href="route('uren.index')">
                <x-layout.icon name="clock" :size="16" />
                Mijn uren
            </x-ui.button>
            <x-ui.button variant="primary" :href="route('uren.create')">
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

    <x-ui.card title="Snel naar" subtitle="Direct naar de plek waar je werkt.">
        <ul style="display: flex; flex-direction: column; gap: var(--space-3); list-style: none; padding: 0;">
            <li>
                <a href="{{ route('clients.index') }}" style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-ink-900); text-decoration: none; padding: var(--space-2) 0;">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-mint); color: var(--color-accent-mint-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="users" :size="16" /></span>
                    Mijn cliënten
                </a>
            </li>
            <li>
                <a href="{{ route('uren.index') }}" style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-ink-900); text-decoration: none; padding: var(--space-2) 0;">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-amber); color: var(--color-accent-amber-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="clock" :size="16" /></span>
                    Urenregistratie
                </a>
            </li>
            <li>
                <a href="{{ route('profiel.show') }}" style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--font-size-sm); color: var(--color-ink-900); text-decoration: none; padding: var(--space-2) 0;">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-md); background: var(--color-accent-purple); color: var(--color-accent-purple-fg); display: inline-flex; align-items: center; justify-content: center;"><x-layout.icon name="user" :size="16" /></span>
                    Mijn profiel
                </a>
            </li>
        </ul>
    </x-ui.card>
@endsection
