@props([
    'type' => 'error',
])

@php
    $class = 'alert alert-'.$type;
@endphp

<div role="alert" {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</div>
