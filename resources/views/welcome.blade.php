@extends('layouts.app')

@section('title', 'Nexora — Zorgbegeleidingssysteem')

@section('content')
    <div class="flex flex-col items-center justify-center text-center py-20">
        <h1 class="text-5xl font-bold text-indigo-700 mb-4">Nexora</h1>
        <p class="text-xl text-slate-600 mb-8 max-w-2xl">
            Zorgbegeleidingssysteem voor beschermd wonen.
            Cliëntdossiers, teambeheer en urenregistratie op één platform.
        </p>
        <div class="flex gap-4">
            @guest
                <a href="{{ url('/login') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                    Inloggen
                </a>
            @endguest
            @auth
                @if(auth()->user()->role === 'teamleider')
                    <a href="{{ route('teamleider.dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                        Naar dashboard
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                        Naar dashboard
                    </a>
                @endif
            @endauth
        </div>
    </div>
@endsection
