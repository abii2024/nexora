@extends('layouts.auth')

@section('title', 'Inloggen — Nexora')
@section('subtitle', 'Log in op je account')

@section('content')
    <x-ui.card padded>
        @if ($errors->any())
            <div style="margin-bottom: var(--space-5);">
                <x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
            @csrf

            <x-ui.input
                name="email"
                label="E-mailadres"
                type="email"
                required
                autocomplete="email"
                autofocus
                placeholder="naam@nexora.test"
            />

            <x-ui.input
                name="password"
                label="Wachtwoord"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />

            <div style="display: flex; align-items: center; justify-content: space-between; font-size: var(--font-size-sm);">
                <label style="display: inline-flex; align-items: center; gap: var(--space-2); color: var(--color-text-secondary); cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: var(--color-ink-900);">
                    <span>Onthoud mij</span>
                </label>

                <a href="#" style="color: var(--color-primary-600); font-weight: var(--font-weight-medium);" title="Komt in US-15">
                    Wachtwoord vergeten?
                </a>
            </div>

            <x-ui.button type="submit" variant="primary" style="width: 100%; padding: var(--space-4) var(--space-5);">
                Inloggen
            </x-ui.button>
        </form>
    </x-ui.card>

    <p style="text-align: center; margin-top: var(--space-5); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
        Geen account? Neem contact op met je teamleider.
    </p>
@endsection
