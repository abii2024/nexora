@extends('layouts.app')

@section('title', 'Teamleider dashboard — Nexora')

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-2xl font-bold text-slate-900">Welkom, {{ auth()->user()->name }}</h1>
            <p class="text-slate-600 mt-1">
                Teamleider dashboard
                @if(auth()->user()->team)
                    · {{ auth()->user()->team->name }}
                @endif
            </p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Medewerkers</h2>
                <p class="text-3xl font-bold text-indigo-700 mt-2">0</p>
                <p class="text-sm text-slate-500 mt-1">in mijn team</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Cliënten</h2>
                <p class="text-3xl font-bold text-indigo-700 mt-2">0</p>
                <p class="text-sm text-slate-500 mt-1">actief in beheer</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Te beoordelen uren</h2>
                <p class="text-3xl font-bold text-amber-600 mt-2">0</p>
                <p class="text-sm text-slate-500 mt-1">wachten op goedkeuring</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Goedgekeurd</h2>
                <p class="text-3xl font-bold text-emerald-600 mt-2">0 u</p>
                <p class="text-sm text-slate-500 mt-1">deze week</p>
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
            <h3 class="font-semibold text-indigo-900">Volgende functionaliteiten</h3>
            <p class="text-indigo-800 mt-1 text-sm">
                Medewerkerbeheer (US-03 t/m US-06), cliëntbeheer (US-07 t/m US-10) en
                urenbeoordeling (US-13/US-14) komen beschikbaar in de volgende sprints.
            </p>
        </div>
    </div>
@endsection
