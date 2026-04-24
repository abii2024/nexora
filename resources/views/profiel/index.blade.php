@extends('layouts.app')

@section('title', 'Profiel — Nexora')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Profiel</h1>
            <p class="page-subtitle">Wijzig je eigen accountgegevens en wachtwoord. Rol ({{ ucfirst($user->role) }}) en teamtoewijzing kunnen alleen door een teamleider worden aangepast.</p>
        </div>
    </div>

    <div style="max-width: 720px;">
        @if (session('success'))
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="error">
                    <strong>Er zijn {{ $errors->count() }} {{ $errors->count() === 1 ? 'fout' : 'fouten' }} gevonden:</strong>
                    <ul style="margin-top: var(--space-2); padding-left: var(--space-5); list-style: disc;">
                        @foreach($errors->all() as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            </div>
        @endif

        <form method="POST" action="{{ route('profiel.update') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
            @csrf
            @method('PATCH')

            <x-ui.card padded>
                <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); margin-bottom: var(--space-2);">Accountgegevens</h2>
                <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-bottom: var(--space-4);">Naam is hoe collega's je zien in het team en op cliëntdossiers. E-mail gebruik je om in te loggen.</p>

                <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-4);">
                    <x-ui.input
                        name="name"
                        label="Naam"
                        required
                        autocomplete="name"
                        :value="old('name', $user->name)"
                    />
                    <x-ui.input
                        name="email"
                        label="E-mailadres"
                        type="email"
                        required
                        autocomplete="email"
                        :value="old('email', $user->email)"
                    />
                </div>
            </x-ui.card>

            <x-ui.card padded>
                <h2 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); color: var(--color-ink-900); margin-bottom: var(--space-2);">Wachtwoord wijzigen</h2>
                <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-bottom: var(--space-4);">
                    Laat de wachtwoord-velden leeg als je alleen je naam of e-mail wilt bijwerken. Andere sessies op andere apparaten worden na een wachtwoordwijziging automatisch uitgelogd.
                </p>

                <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-4);">
                    <x-ui.input
                        name="current_password"
                        label="Huidig wachtwoord"
                        type="password"
                        autocomplete="current-password"
                        hint="Alleen vereist als je een nieuw wachtwoord instelt."
                    />
                    <x-ui.input
                        name="password"
                        label="Nieuw wachtwoord"
                        type="password"
                        autocomplete="new-password"
                        hint="Minstens 8 tekens."
                    />
                    <x-ui.input
                        name="password_confirmation"
                        label="Nieuw wachtwoord bevestigen"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
            </x-ui.card>

            <div style="display: flex; gap: var(--space-3); justify-content: flex-end;">
                <x-ui.button type="submit" variant="primary">
                    <x-layout.icon name="check" :size="16" />
                    Opslaan
                </x-ui.button>
            </div>
        </form>
    </div>

    <p style="margin-top: var(--space-4); font-size: var(--font-size-xs); color: var(--color-text-muted); max-width: 720px;">
        Beveiligingsopmerking: rol, teamtoewijzing en actief-status staan bewust niet in dit formulier. Mutaties daarop worden genegeerd bij het opslaan (AVG art. 32 — defense in depth tegen mass-assignment).
    </p>
@endsection
