@extends('layouts.app')

@section('title', 'Nexora — Zorgbegeleidingssysteem')

@section('content')
    @auth
        {{-- Ingelogde users zien hun eigen dashboard direct via redirect, maar voor de zekerheid: --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Welkom bij Nexora</h1>
                <p class="page-subtitle">Ga naar je dashboard om verder te werken.</p>
            </div>
            <div class="page-actions">
                <x-ui.button variant="primary" :href="auth()->user()->role === 'teamleider' ? route('teamleider.dashboard') : route('dashboard')">
                    Naar dashboard
                </x-ui.button>
            </div>
        </div>
    @else
        <section style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-8); background: linear-gradient(180deg, var(--color-canvas) 0%, var(--color-canvas-raised) 100%);">
            <div style="max-width: 640px; text-align: center;">
                <div style="display: inline-flex; align-items: center; gap: var(--space-1); font-size: var(--font-size-5xl); font-weight: var(--font-weight-bold); color: var(--color-ink-900); letter-spacing: var(--letter-spacing-tighter); margin-bottom: var(--space-4);">
                    Nexora<sup style="font-size: 0.35em; color: var(--color-ink-500); font-weight: var(--font-weight-semibold);">®</sup>
                </div>

                <p style="font-size: var(--font-size-xl); color: var(--color-text-secondary); line-height: var(--line-height-relaxed); max-width: 540px; margin: 0 auto var(--space-8);">
                    Zorgbegeleidingssysteem voor beschermd wonen.<br>
                    Cliëntdossiers, teambeheer en urenregistratie op één platform.
                </p>

                <div style="display: flex; gap: var(--space-3); justify-content: center;">
                    <x-ui.button variant="primary" :href="url('/login')">
                        Inloggen
                        <x-layout.icon name="arrow-right" :size="16" />
                    </x-ui.button>
                </div>

                <p style="margin-top: var(--space-12); font-size: var(--font-size-xs); color: var(--color-text-muted); letter-spacing: var(--letter-spacing-wider); text-transform: uppercase;">
                    MBO Examenproject · Abdisamad · 2026
                </p>
            </div>
        </section>
    @endauth
@endsection
