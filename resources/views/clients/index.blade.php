@extends('layouts.app')

@section('title', 'Cliënten — Nexora')

@php
    $user = auth()->user();
    $isTeamleider = $user->isTeamleider();
    $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['status'] ?? null) || ($filters['care_type'] ?? null);
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Cliënten</h1>
            <p class="page-subtitle">
                @if($isTeamleider)
                    {{ $totals['totaal'] }} {{ $totals['totaal'] === 1 ? 'cliënt' : 'cliënten' }}
                    · {{ $totals['actief'] }} actief · {{ $totals['wacht'] }} wachtlijst · {{ $totals['inactief'] }} inactief
                    @if($user->team)
                        · {{ $user->team->name }}
                    @endif
                @else
                    Jouw caseload — {{ $totals['totaal'] }} gekoppelde {{ $totals['totaal'] === 1 ? 'cliënt' : 'cliënten' }}
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

    {{-- Rol-specifieke banner --}}
    @if(! $isTeamleider)
        <div style="margin-bottom: var(--space-5); padding: var(--space-4); background: var(--color-primary-bg); border: 1px solid var(--color-primary-100); border-radius: var(--radius-lg); font-size: var(--font-size-sm); color: var(--color-text-primary);">
            <strong>Eigen caseload</strong> — je ziet alleen de cliënten waar je als primair, secundair of tertiair begeleider aan gekoppeld bent. Voor koppelingen aan nieuwe cliënten neem contact op met je teamleider.
        </div>
    @endif

    {{-- Filter bar --}}
    <x-ui.card padded>
        <x-clients.filter-bar :filters="$filters" :action="route('clients.index')" />
    </x-ui.card>

    <div style="margin-top: var(--space-5);">
        @if($clients->isEmpty())
            <x-ui.card>
                @if($hasFilters)
                    <x-ui.empty-state
                        title="Geen cliënten gevonden"
                        description="Er zijn geen cliënten die aan deze filters voldoen. Pas je zoek-term of filters aan."
                    >
                        <x-slot:icon>
                            <x-layout.icon name="user" :size="32" />
                        </x-slot:icon>
                        <x-slot:action>
                            <x-ui.button variant="secondary" :href="route('clients.index')">Filters resetten</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @elseif($isTeamleider)
                    <x-ui.empty-state
                        title="Nog geen cliënten"
                        description="Maak de eerste cliënt aan via 'Cliënt toevoegen'."
                    >
                        <x-slot:icon>
                            <x-layout.icon name="user" :size="32" />
                        </x-slot:icon>
                        <x-slot:action>
                            <x-ui.button variant="primary" :href="route('clients.create')">
                                <x-layout.icon name="plus" :size="16" />
                                Cliënt toevoegen
                            </x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state
                        title="Je hebt momenteel geen cliënten toegewezen."
                        description="Zodra je teamleider je aan een cliënt koppelt, verschijnt die hier."
                    >
                        <x-slot:icon>
                            <x-layout.icon name="user" :size="32" />
                        </x-slot:icon>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @elseif($isTeamleider)
            {{-- TEAMLEIDER: Tabel-weergave --}}
            <x-ui.card>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: var(--font-size-sm);">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--color-border); color: var(--color-text-secondary); font-weight: var(--font-weight-semibold); text-transform: uppercase; font-size: var(--font-size-xs); letter-spacing: var(--letter-spacing-wider);">
                                <th style="padding: var(--space-3) var(--space-4);">Naam</th>
                                <th style="padding: var(--space-3) var(--space-4);">Status</th>
                                <th style="padding: var(--space-3) var(--space-4);">Zorgtype</th>
                                <th style="padding: var(--space-3) var(--space-4);">Geboortedatum</th>
                                <th style="padding: var(--space-3) var(--space-4);">Primaire begeleider</th>
                                <th style="padding: var(--space-3) var(--space-4);">Telefoon</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $client)
                                @php
                                    $primair = $client->caregivers->first(fn ($c) => $c->pivot->role === 'primair');
                                @endphp
                                <tr style="border-bottom: 1px solid var(--color-border);">
                                    <td style="padding: var(--space-3) var(--space-4); font-weight: var(--font-weight-medium);">
                                        <a href="{{ route('clients.show', $client) }}" style="color: var(--color-ink-900); text-decoration: none;">{{ $client->fullName() }}</a>
                                    </td>
                                    <td style="padding: var(--space-3) var(--space-4);">
                                        <x-ui.badge tone="{{ $client->status === 'actief' ? 'success' : ($client->status === 'wacht' ? 'warning' : 'neutral') }}">
                                            {{ ucfirst($client->status) }}
                                        </x-ui.badge>
                                    </td>
                                    <td style="padding: var(--space-3) var(--space-4);">
                                        <x-ui.badge tone="neutral">{{ strtoupper($client->care_type) }}</x-ui.badge>
                                    </td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->geboortedatum?->format('d-m-Y') ?? '—' }}</td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary);">
                                        @if($primair)
                                            {{ $primair->name }}
                                        @else
                                            <span style="color: var(--color-text-muted); font-style: italic;">Geen primair</span>
                                        @endif
                                    </td>
                                    <td style="padding: var(--space-3) var(--space-4); color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->telefoon ?? '—' }}</td>
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
        @else
            {{-- ZORGBEGELEIDER: Kaart-weergave --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--space-4);">
                @foreach($clients as $client)
                    @php
                        $ownCaregiver = $client->caregivers->firstWhere('id', $user->id);
                        $role = $ownCaregiver?->pivot?->role;
                    @endphp
                    <a href="{{ route('clients.show', $client) }}" style="text-decoration: none;">
                        <div class="card card-padded" style="transition: transform var(--transition-micro), box-shadow var(--transition-micro); cursor: pointer;"
                             onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-md)'"
                             onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-3); margin-bottom: var(--space-3);">
                                <h3 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">{{ $client->fullName() }}</h3>
                                @if($role)
                                    <x-ui.badge tone="{{ $role === 'primair' ? 'success' : ($role === 'secundair' ? 'warning' : 'neutral') }}">
                                        {{ ucfirst($role) }}
                                    </x-ui.badge>
                                @endif
                            </div>
                            <div style="display: flex; gap: var(--space-2); margin-bottom: var(--space-3); flex-wrap: wrap;">
                                <x-ui.badge tone="{{ $client->status === 'actief' ? 'success' : ($client->status === 'wacht' ? 'warning' : 'neutral') }}">
                                    {{ ucfirst($client->status) }}
                                </x-ui.badge>
                                <x-ui.badge tone="neutral">{{ strtoupper($client->care_type) }}</x-ui.badge>
                            </div>
                            <dl style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-1) var(--space-3); font-size: var(--font-size-xs);">
                                <dt style="color: var(--color-text-muted);">Geboren</dt>
                                <dd style="color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->geboortedatum?->format('d-m-Y') ?? '—' }}</dd>
                                <dt style="color: var(--color-text-muted);">Telefoon</dt>
                                <dd style="color: var(--color-text-secondary); font-variant-numeric: tabular-nums;">{{ $client->telefoon ?? '—' }}</dd>
                            </dl>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($clients->hasPages())
                <div style="margin-top: var(--space-5); padding: var(--space-4); background: var(--color-canvas-raised); border: 1px solid var(--color-border); border-radius: var(--radius-xl);">
                    {{ $clients->links() }}
                </div>
            @endif
        @endif
    </div>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted);">
        Cliënt bewerken + archiveren komt in US-10. Urenregistratie in US-11.
    </p>
@endsection
