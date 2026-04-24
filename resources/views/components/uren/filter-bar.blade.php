@props([
    'filters' => [],
    'medewerkers' => [],
    'action',
])

@php
    $status = $filters['status'] ?? 'ingediend';
    $medewerker = $filters['medewerker'] ?? '';
    $week = $filters['week'] ?? '';
    $hasActive = $status !== 'ingediend' || $medewerker !== '' || $week !== '';
@endphp

<form method="GET" action="{{ $action }}" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: var(--space-3); align-items: end;">
    <div class="space-y-2">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-input">
            <option value="alle" {{ $status === 'alle' ? 'selected' : '' }}>Alle statussen</option>
            <option value="concept" {{ $status === 'concept' ? 'selected' : '' }}>Concept</option>
            <option value="ingediend" {{ $status === 'ingediend' ? 'selected' : '' }}>Ingediend</option>
            <option value="goedgekeurd" {{ $status === 'goedgekeurd' ? 'selected' : '' }}>Goedgekeurd</option>
            <option value="afgekeurd" {{ $status === 'afgekeurd' ? 'selected' : '' }}>Afgekeurd</option>
        </select>
    </div>

    <div class="space-y-2">
        <label for="medewerker" class="form-label">Medewerker</label>
        <select id="medewerker" name="medewerker" class="form-input">
            <option value="">Alle medewerkers</option>
            @foreach($medewerkers as $m)
                <option value="{{ $m->id }}" {{ (string) $medewerker === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label for="week" class="form-label">Week</label>
        <input
            id="week"
            type="week"
            name="week"
            value="{{ $week }}"
            class="form-input"
        >
    </div>

    <div style="display: flex; gap: var(--space-2);">
        <x-ui.button type="submit" variant="primary">Filter</x-ui.button>
        @if($hasActive)
            <x-ui.button variant="ghost" :href="$action">Reset</x-ui.button>
        @endif
    </div>
</form>
