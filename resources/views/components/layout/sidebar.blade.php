@php
    $user = auth()->user();
    $isTeamleider = $user?->role === 'teamleider';

    $sections = [
        'werk' => [
            'label' => 'Werk',
            'items' => array_filter([
                [
                    'label' => 'Dashboard',
                    'icon' => 'dashboard',
                    'href' => $isTeamleider ? route('teamleider.dashboard') : route('dashboard'),
                    'active' => request()->routeIs('dashboard') || request()->routeIs('teamleider.dashboard'),
                ],
                ['label' => 'Cliënten', 'icon' => 'users', 'href' => '#', 'active' => request()->routeIs('clienten*'), 'disabled' => true],
                ['label' => 'Taken', 'icon' => 'check-square', 'href' => '#', 'active' => request()->routeIs('taken*'), 'disabled' => true],
                ['label' => 'Agenda', 'icon' => 'calendar', 'href' => '#', 'active' => request()->routeIs('agenda*'), 'disabled' => true],
            ]),
        ],
        'praktijk' => [
            'label' => 'Praktijk',
            'items' => [
                ['label' => 'Urenregistratie', 'icon' => 'clock', 'href' => '#', 'active' => request()->routeIs('uren*'), 'disabled' => true],
                ['label' => 'Documenten', 'icon' => 'folder', 'href' => '#', 'disabled' => true],
                ['label' => 'Rapportages', 'icon' => 'bar-chart', 'href' => '#', 'disabled' => true],
                ['label' => 'Incidenten', 'icon' => 'alert-circle', 'href' => '#', 'disabled' => true],
                ['label' => 'Overdracht', 'icon' => 'arrows', 'href' => '#', 'disabled' => true],
            ],
        ],
        'overig' => [
            'label' => 'Overig',
            'items' => array_values(array_filter([
                ['label' => 'Intranet', 'icon' => 'message', 'href' => '#', 'disabled' => true],
                $isTeamleider ? ['label' => 'Teamleden', 'icon' => 'user-cog', 'href' => route('team.index'), 'active' => request()->routeIs('team.*')] : null,
                ['label' => 'Profiel', 'icon' => 'user', 'href' => '#', 'active' => request()->routeIs('profiel*'), 'disabled' => true],
            ])),
        ],
    ];

    $initials = $user ? collect(explode(' ', $user->name))->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->join('') : 'NX';
@endphp

<aside class="sidebar" aria-label="Hoofdnavigatie">
    <div class="sidebar-logo">
        <a href="{{ url('/') }}">Nexora<sup>®</sup></a>
    </div>

    <nav class="sidebar-nav">
        @foreach($sections as $section)
            <div>
                <div class="sidebar-section-label">{{ $section['label'] }}</div>
                <ul class="sidebar-list">
                    @foreach($section['items'] as $item)
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                class="sidebar-item {{ ($item['active'] ?? false) ? 'is-active' : '' }}"
                                @if($item['disabled'] ?? false)
                                    title="Nog niet beschikbaar (komt in latere user story)"
                                    aria-disabled="true"
                                    onclick="event.preventDefault()"
                                    style="opacity: 0.5; cursor: not-allowed;"
                                @endif
                            >
                                <x-layout.icon :name="$item['icon']" :size="18" />
                                <span>{{ $item['label'] }}</span>
                                @if(!empty($item['badge']))
                                    <span class="sidebar-badge">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    @auth
        <div class="sidebar-user">
            <span class="sidebar-avatar" aria-hidden="true">{{ $initials }}</span>
            <div class="sidebar-user-meta">
                <span class="sidebar-user-name">{{ $user->name }}</span>
                <span class="sidebar-user-role">{{ $user->role }}</span>
            </div>
        </div>
    @endauth
</aside>
