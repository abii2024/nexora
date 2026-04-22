@props([
    'label',
    'value',
    'tone' => 'mint',
    'icon' => null,
    'hint' => null,
])

@php
    $toneBg = match($tone) {
        'amber'  => 'var(--color-accent-amber)',
        'blue'   => 'var(--color-accent-blue)',
        'green'  => 'var(--color-accent-green)',
        'rose'   => 'var(--color-accent-rose)',
        'purple' => 'var(--color-accent-purple)',
        default  => 'var(--color-accent-mint)',
    };
    $toneFg = match($tone) {
        'amber'  => 'var(--color-accent-amber-fg)',
        'blue'   => 'var(--color-accent-blue-fg)',
        'green'  => 'var(--color-accent-green-fg)',
        'rose'   => 'var(--color-accent-rose-fg)',
        'purple' => 'var(--color-accent-purple-fg)',
        default  => 'var(--color-accent-mint-fg)',
    };
@endphp

<div class="card card-padded" style="display: flex; flex-direction: column; gap: var(--space-4); min-height: 128px;">
    <div style="display: flex; align-items: center; gap: var(--space-3);">
        @if($icon)
            <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: var(--radius-lg); background: {{ $toneBg }}; color: {{ $toneFg }};">
                {!! $icon !!}
            </span>
        @endif
        <span style="font-size: var(--font-size-xs); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: var(--letter-spacing-wider); color: var(--color-text-secondary);">
            {{ $label }}
        </span>
    </div>

    <div style="font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); color: var(--color-ink-900); line-height: var(--line-height-tight); letter-spacing: var(--letter-spacing-tight); font-variant-numeric: tabular-nums;">
        {{ $value }}
    </div>

    @if($hint)
        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: auto;">{{ $hint }}</p>
    @endif
</div>
