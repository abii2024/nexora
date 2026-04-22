@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $errorMsg = $error ?? ($errors->first($name));
@endphp

<div class="space-y-2">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required)<span style="color: var(--color-danger);">*</span>@endif
        </label>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'form-input']) }}
    >

    @if($errorMsg)
        <p class="form-error">{{ $errorMsg }}</p>
    @elseif($hint)
        <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--space-1);">{{ $hint }}</p>
    @endif
</div>
