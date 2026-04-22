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
<body style="background: var(--color-canvas);">
    <div style="min-height: 100vh; display: grid; grid-template-columns: 1fr;">
        {{-- Full-screen auth panel --}}
        <div style="display: flex; align-items: center; justify-content: center; padding: var(--space-6);">
            <div style="width: 100%; max-width: 420px;">
                <div style="text-align: center; margin-bottom: var(--space-8);">
                    <a href="{{ url('/') }}" style="display: inline-flex; align-items: center; gap: var(--space-1); font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-ink-900); letter-spacing: var(--letter-spacing-display); text-decoration: none;">
                        Nexora<sup style="font-size: 0.5em; color: var(--color-ink-500);">®</sup>
                    </a>
                    @hasSection('subtitle')
                        <p style="margin-top: var(--space-2); font-size: var(--font-size-sm); color: var(--color-text-secondary);">@yield('subtitle')</p>
                    @endif
                </div>

                @yield('content')

                <p style="text-align: center; margin-top: var(--space-6); font-size: var(--font-size-xs); color: var(--color-text-muted);">
                    Zorgbegeleidingssysteem voor beschermd wonen
                </p>
            </div>
        </div>
    </div>
</body>
</html>
