<?php

use App\Http\Middleware\CheckActiveUser;
use App\Http\Middleware\EnsureTeamleider;
use App\Http\Middleware\EnsureZorgbegeleider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CheckActiveUser draait globaal in de web-groep zodat elke
        // request een directe is_active-check doet na login (US-06 AC-2).
        // AuthenticateSession (US-16) koppelt session-cookie aan password-hash
        // zodat Auth::logoutOtherDevices($newPassword) andere sessies invalideert.
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            CheckActiveUser::class,
        ]);

        $middleware->alias([
            'teamleider' => EnsureTeamleider::class,
            'zorgbegeleider' => EnsureZorgbegeleider::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
