@extends('layouts.app')

@section('title', '403 — Geen toegang')

@section('content')
    <div class="flex flex-col items-center justify-center text-center py-20">
        <h1 class="text-6xl font-bold text-red-600 mb-4">403</h1>
        <p class="text-xl text-slate-700 mb-2">Geen toegang</p>
        <p class="text-slate-600 mb-8 max-w-md">
            Je hebt niet de juiste rechten om deze pagina te bekijken. Neem contact op met je teamleider als je denkt dat dit een fout is.
        </p>
        <a href="{{ url('/') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition">
            Terug naar home
        </a>
    </div>
@endsection
