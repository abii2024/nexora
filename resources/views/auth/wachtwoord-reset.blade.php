@extends('layouts.auth')

@section('title', 'Wachtwoord herstellen — Nexora')
@section('subtitle', 'Kies je nieuwe wachtwoord')

@section('content')
    <x-ui.card padded>
        @if ($errors->any())
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <x-ui.input
                name="email"
                label="E-mailadres"
                type="email"
                required
                autocomplete="email"
                :value="old('email', $email)"
            />

            <x-ui.input
                name="password"
                label="Nieuw wachtwoord"
                type="password"
                required
                autocomplete="new-password"
                hint="Minstens 8 tekens."
            />

            <x-ui.input
                name="password_confirmation"
                label="Wachtwoord bevestigen"
                type="password"
                required
                autocomplete="new-password"
            />

            <x-ui.button type="submit" variant="primary" style="width: 100%; padding: var(--space-4) var(--space-5);">
                Wachtwoord opslaan
            </x-ui.button>
        </form>
    </x-ui.card>

    <p style="text-align: center; margin-top: var(--space-5); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
        <a href="{{ route('login') }}" style="color: var(--color-primary-600); font-weight: var(--font-weight-medium);">Terug naar inloggen</a>
    </p>
@endsection
