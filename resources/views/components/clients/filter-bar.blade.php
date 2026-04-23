@props([
    'filters' => [],
    'action',
])

@php
    $search = $filters['search'] ?? '';
    $status = $filters['status'] ?? '';
    $careType = $filters['care_type'] ?? '';
    $sort = $filters['sort'] ?? 'name';
    $hasActive = $search !== '' || $status || $careType || ($sort && $sort !== 'name');
@endphp

<form method="GET" action="{{ $action }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: var(--space-3); align-items: end;">
    <div class="space-y-2">
        <label for="search" class="form-label">Zoeken</label>
        <input
            id="search"
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Voornaam of achternaam"
            class="form-input"
        >
    </div>

    <div class="space-y-2">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-input">
            <option value="">Alle statussen</option>
            <option value="actief" {{ $status === 'actief' ? 'selected' : '' }}>Actief</option>
            <option value="wacht" {{ $status === 'wacht' ? 'selected' : '' }}>Wachtlijst</option>
            <option value="inactief" {{ $status === 'inactief' ? 'selected' : '' }}>Inactief</option>
        </select>
    </div>

    <div class="space-y-2">
        <label for="care_type" class="form-label">Zorgtype</label>
        <select id="care_type" name="care_type" class="form-input">
            <option value="">Alle zorgtypes</option>
            <option value="wmo" {{ $careType === 'wmo' ? 'selected' : '' }}>WMO</option>
            <option value="wlz" {{ $careType === 'wlz' ? 'selected' : '' }}>WLZ</option>
            <option value="jw" {{ $careType === 'jw' ? 'selected' : '' }}>JW</option>
        </select>
    </div>

    <div class="space-y-2">
        <label for="sort" class="form-label">Sortering</label>
        <select id="sort" name="sort" class="form-input">
            <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Naam</option>
            <option value="status" {{ $sort === 'status' ? 'selected' : '' }}>Status</option>
            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Nieuwst eerst</option>
        </select>
    </div>

    <div style="display: flex; gap: var(--space-2);">
        <x-ui.button type="submit" variant="primary">Filter</x-ui.button>
        @if($hasActive)
            <x-ui.button variant="ghost" :href="$action">Reset</x-ui.button>
        @endif
    </div>
</form>
