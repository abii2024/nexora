@extends('layouts.app')

@section('title', '403 — Geen toegang')

@section('content')
    @auth
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: var(--space-16) var(--space-6);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--color-danger-bg); display: inline-flex; align-items: center; justify-content: center; color: var(--color-danger-dark); margin-bottom: var(--space-6);">
                <x-layout.icon name="shield" :size="40" />
            </div>
            <h1 style="font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); color: var(--color-ink-900); letter-spacing: var(--letter-spacing-tight); margin-bottom: var(--space-2);">Geen toegang</h1>
            <p style="max-width: 480px; color: var(--color-text-secondary); font-size: var(--font-size-base); line-height: var(--line-height-relaxed); margin-bottom: var(--space-8);">
                Je hebt niet de juiste rechten om deze pagina te bekijken. Neem contact op met je teamleider als je denkt dat dit een fout is.
            </p>
            <x-ui.button variant="primary" :href="auth()->user()->role === 'teamleider' ? route('teamleider.dashboard') : route('dashboard')">
                Terug naar dashboard
            </x-ui.button>
        </div>
    @else
        <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: var(--space-6); background: var(--color-canvas);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--color-danger-bg); display: inline-flex; align-items: center; justify-content: center; color: var(--color-danger-dark); margin-bottom: var(--space-6);">
                <x-layout.icon name="shield" :size="40" />
            </div>
            <h1 style="font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); color: var(--color-ink-900); letter-spacing: var(--letter-spacing-tight); margin-bottom: var(--space-2);">Geen toegang</h1>
            <p style="max-width: 480px; color: var(--color-text-secondary); font-size: var(--font-size-base); line-height: var(--line-height-relaxed); margin-bottom: var(--space-8);">
                Je hebt geen toegang tot deze pagina. Log in om door te gaan.
            </p>
            <x-ui.button variant="primary" :href="url('/login')">Inloggen</x-ui.button>
        </div>
    @endauth
@endsection
