@php
    $user = auth()->user();
    $greeting = match(true) {
        now()->hour < 12 => 'Goedemorgen',
        now()->hour < 18 => 'Goedemiddag',
        default => 'Goedenavond',
    };
@endphp

<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="topbar-icon-btn" aria-label="Toggle sidebar" onclick="document.querySelector('.sidebar')?.classList.toggle('is-collapsed')">
            <x-layout.icon name="sidebar" :size="20" />
        </button>
    </div>

    <div class="topbar-right">
        <button type="button" class="topbar-icon-btn" aria-label="Thema wisselen">
            <x-layout.icon name="moon" :size="20" />
        </button>

        <button type="button" class="topbar-icon-btn" aria-label="Notificaties">
            <x-layout.icon name="bell" :size="20" />
        </button>

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="topbar-icon-btn" aria-label="Uitloggen" title="Uitloggen">
                    <x-layout.icon name="log-out" :size="20" />
                </button>
            </form>
        @endauth
    </div>
</header>
