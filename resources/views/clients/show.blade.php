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
        <div class="page-actions" style="display: flex; gap: var(--space-2);">
            @can('update', $client)
                <x-ui.button variant="secondary" :href="route('clients.edit', $client)">
                    <x-layout.icon name="edit" :size="16" />
                    Bewerken
                </x-ui.button>
            @endcan
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

    {{-- US-08: Gekoppelde begeleiders --}}
    @php
        $caregivers = $client->caregivers->sortBy(function ($c) {
            return ['primair' => 0, 'secundair' => 1, 'tertiair' => 2][$c->pivot->role] ?? 9;
        });
    @endphp

    <div style="margin-top: var(--space-5);">
        <x-ui.card title="Begeleiders">
            <x-slot:actions>
                @can('update', $client)
                    <x-ui.button variant="secondary" :href="route('clients.caregivers.edit', $client)">
                        <x-layout.icon name="user-cog" :size="16" />
                        Begeleiders beheren
                    </x-ui.button>
                @endcan
            </x-slot:actions>

            @if($caregivers->isEmpty())
                <x-ui.empty-state
                    title="Geen begeleiders gekoppeld"
                    description="Deze cliënt heeft nog geen primaire zorgbegeleider. Koppel er minstens één om continuïteit van zorg te garanderen."
                >
                    <x-slot:icon>
                        <x-layout.icon name="users" :size="32" />
                    </x-slot:icon>
                    @can('update', $client)
                        <x-slot:action>
                            <x-ui.button variant="primary" :href="route('clients.caregivers.edit', $client)">
                                Begeleiders koppelen
                            </x-ui.button>
                        </x-slot:action>
                    @endcan
                </x-ui.empty-state>
            @else
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: var(--space-3);">
                    @foreach($caregivers as $caregiver)
                        @php $role = $caregiver->pivot->role; @endphp
                        <li style="display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                            <span style="flex: 1;">
                                <span style="display: block; font-weight: var(--font-weight-medium); color: var(--color-ink-900); font-size: var(--font-size-sm);">{{ $caregiver->name }}</span>
                                <span style="display: block; font-size: var(--font-size-xs); color: var(--color-text-muted);">{{ $caregiver->email }}</span>
                            </span>
                            <x-ui.badge tone="{{ $role === 'primair' ? 'success' : ($role === 'secundair' ? 'warning' : 'neutral') }}">
                                {{ ucfirst($role) }}
                            </x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    {{-- US-10: Recente statuswijzigingen (audit-trail preview) --}}
    @if($client->statusLogs->isNotEmpty())
        <div style="margin-top: var(--space-5);">
            <x-ui.card title="Recente statuswijzigingen">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: var(--space-2);">
                    @foreach($client->statusLogs as $log)
                        <li style="display: flex; align-items: center; gap: var(--space-3); padding: var(--space-2) var(--space-3); font-size: var(--font-size-sm); border-bottom: 1px solid var(--color-border);">
                            <span style="color: var(--color-text-muted); font-variant-numeric: tabular-nums; font-size: var(--font-size-xs); min-width: 120px;">{{ $log->created_at?->format('d-m-Y H:i') }}</span>
                            <span style="flex: 1; color: var(--color-text-secondary);">
                                <x-ui.badge tone="neutral">{{ ucfirst($log->old_status) }}</x-ui.badge>
                                →
                                <x-ui.badge tone="{{ $log->new_status === 'actief' ? 'success' : ($log->new_status === 'wacht' ? 'warning' : 'neutral') }}">{{ ucfirst($log->new_status) }}</x-ui.badge>
                            </span>
                            <span style="color: var(--color-text-muted); font-size: var(--font-size-xs);">door {{ $log->changedBy?->name ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
    @endif
@endsection
