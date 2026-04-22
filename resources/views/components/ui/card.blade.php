@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
])

@php
    $classes = 'card'.($padded ? ' card-padded' : '');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($title || isset($actions))
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-4);">
            <div>
                @if($title)
                    <h2 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); letter-spacing: var(--letter-spacing-subtle);">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p style="margin-top: var(--space-1); font-size: var(--font-size-sm); color: var(--color-text-secondary);">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
