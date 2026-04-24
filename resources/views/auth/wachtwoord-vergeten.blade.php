@extends('layouts.auth')

@section('title', 'Wachtwoord vergeten — Nexora')
@section('subtitle', 'We sturen je een reset-link per e-mail')

@section('content')
    <x-ui.card padded>
        @if (session('status'))
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>
            </div>
        @endif

        <p style="margin-bottom: var(--space-5); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
            Vul het e-mailadres in waarmee je bij Nexora bekend bent. Je ontvangt een link om je wachtwoord te herstellen. De link is <strong>60 minuten</strong> geldig.
        </p>

        <form method="POST" action="{{ route('password.email') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
            @csrf

            <x-ui.input
                name="email"
                label="E-mailadres"
                type="email"
                required
                autocomplete="email"
                autofocus
                placeholder="naam@nexora.test"
                :value="old('email')"
            />

            <x-ui.button type="submit" variant="primary" style="width: 100%; padding: var(--space-4) var(--space-5);">
                Verstuur reset-link
            </x-ui.button>
        </form>
    </x-ui.card>

    <p style="text-align: center; margin-top: var(--space-5); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
        <a href="{{ route('login') }}" style="color: var(--color-primary-600); font-weight: var(--font-weight-medium);">Terug naar inloggen</a>
    </p>
@endsection
