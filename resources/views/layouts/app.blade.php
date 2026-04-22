<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nexora')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @auth
        <div class="shell">
            <x-layout.sidebar />

            <div class="shell-content">
                <x-layout.topbar />

                <main class="shell-main">
                    @if(session('success'))
                        <div style="margin-bottom: var(--space-5);">
                            <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
                        </div>
                    @endif

                    @if(session('error'))
                        <div style="margin-bottom: var(--space-5);">
                            <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <main style="min-height: 100vh; display: flex; flex-direction: column;">
            @if(session('success'))
                <div style="max-width: 480px; margin: var(--space-5) auto 0;">
                    <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
                </div>
            @endif

            @yield('content')
        </main>
    @endauth
</body>
</html>
