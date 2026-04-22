@extends('layouts.app')

@section('title', 'Dashboard — Nexora')

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-2xl font-bold text-slate-900">Welkom, {{ auth()->user()->name }}</h1>
            <p class="text-slate-600 mt-1">Zorgbegeleider dashboard</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Mijn cliënten</h2>
                <p class="text-3xl font-bold text-indigo-700 mt-2">0</p>
                <p class="text-sm text-slate-500 mt-1">Nog geen gekoppelde cliënten</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Concept-uren</h2>
                <p class="text-3xl font-bold text-indigo-700 mt-2">0</p>
                <p class="text-sm text-slate-500 mt-1">Nog niet ingediend</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Goedgekeurd deze week</h2>
                <p class="text-3xl font-bold text-emerald-600 mt-2">0 u</p>
                <p class="text-sm text-slate-500 mt-1">Week {{ now()->weekOfYear }}</p>
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
            <h3 class="font-semibold text-indigo-900">Aan de slag</h3>
            <p class="text-indigo-800 mt-1 text-sm">
                Cliëntenoverzicht, urenregistratie en profielbeheer komen beschikbaar naarmate
                de volgende user stories gebouwd worden.
            </p>
        </div>
    </div>
@endsection
