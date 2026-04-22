@props([
    'title',
    'description' => null,
])

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-12) var(--space-6); text-align: center; gap: var(--space-2);">
    @isset($icon)
        <div style="color: var(--color-text-muted); margin-bottom: var(--space-2);">
            {{ $icon }}
        </div>
    @endisset
    <h3 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900);">{{ $title }}</h3>
    @if($description)
        <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); max-width: 420px;">{{ $description }}</p>
    @endif
    @isset($action)
        <div style="margin-top: var(--space-4);">{{ $action }}</div>
    @endisset
</div>
