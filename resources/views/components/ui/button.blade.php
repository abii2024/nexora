@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'as' => null,
])

@php
    $classes = 'btn btn-'.$variant;
    $tag = $href ? 'a' : ($as ?? 'button');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
