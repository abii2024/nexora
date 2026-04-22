@extends('layouts.app')

@section('title', 'Inloggen — Nexora')

@section('content')
    <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-indigo-700">Nexora</h1>
                <p class="text-slate-600 mt-2">Log in op je account</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-8 border border-slate-200">
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mailadres</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Wachtwoord</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2">Onthoud mij</span>
                        </label>

                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800">Wachtwoord vergeten?</a>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-md transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Inloggen
                    </button>
                </form>
            </div>

            <p class="text-center text-sm text-slate-500 mt-6">
                Geen account? Neem contact op met je teamleider.
            </p>
        </div>
    </div>
@endsection
