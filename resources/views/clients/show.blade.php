@extends('layouts.app')

@section('title', $client->fullName().' — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $client->fullName() }}</h1>
            <p class="page-subtitle">
                <x-ui.badge tone="{{ $client->status === 'actief' ? 'success' : ($client->status === 'wacht' ? 'warning' : 'neutral') }}">
                    {{ ucfirst($client->status) }}
                </x-ui.badge>
                <x-ui.badge tone="neutral">{{ strtoupper($client->care_type) }}</x-ui.badge>
            </p>
        </div>
        <div class="page-actions">
            <x-ui.button variant="ghost" :href="route('clients.index')">
                <x-layout.icon name="chevron-right" :size="14" />
                Terug naar overzicht
            </x-ui.button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-5);">
        <x-ui.card title="Persoonlijk">
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
                <dt style="color: var(--color-text-secondary);">Voornaam</dt>
                <dd style="color: var(--color-ink-900);">{{ $client->voornaam }}</dd>
                <dt style="color: var(--color-text-secondary);">Achternaam</dt>
                <dd style="color: var(--color-ink-900);">{{ $client->achternaam }}</dd>
                <dt style="color: var(--color-text-secondary);">Geboortedatum</dt>
                <dd style="color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $client->geboortedatum?->format('d-m-Y') ?? '—' }}</dd>
                <dt style="color: var(--color-text-secondary);">BSN</dt>
                <dd style="color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $client->bsn ?? '—' }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card title="Contact">
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
                <dt style="color: var(--color-text-secondary);">E-mail</dt>
                <dd>
                    @if($client->email)
                        <a href="mailto:{{ $client->email }}" style="color: var(--color-primary-600);">{{ $client->email }}</a>
                    @else
                        <span style="color: var(--color-text-muted);">—</span>
                    @endif
                </dd>
                <dt style="color: var(--color-text-secondary);">Telefoon</dt>
                <dd style="color: var(--color-ink-900);">{{ $client->telefoon ?? '—' }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card title="Aanmaak">
            <dl style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
                <dt style="color: var(--color-text-secondary);">Team</dt>
                <dd style="color: var(--color-ink-900);">{{ $client->team?->name ?? '—' }}</dd>
                <dt style="color: var(--color-text-secondary);">Aangemaakt op</dt>
                <dd style="color: var(--color-ink-900); font-variant-numeric: tabular-nums;">{{ $client->created_at?->format('d-m-Y H:i') }}</dd>
                <dt style="color: var(--color-text-secondary);">Aangemaakt door</dt>
                <dd style="color: var(--color-ink-900);">{{ $client->createdBy?->name ?? '—' }}</dd>
            </dl>
        </x-ui.card>
    </div>

    <p style="margin-top: var(--space-6); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Begeleiders koppelen (primair/secundair/tertiair) komt in US-08. Bewerken + archiveren in US-10.
    </p>
@endsection
