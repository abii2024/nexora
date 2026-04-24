<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * US-06: forceert dat een ingelogde user bij elke request nog actief is.
 *
 * Als `is_active` false is (bijv. door teamleider zojuist gedeactiveerd),
 * wordt de user bij de volgende request direct uitgelogd + geredirect naar
 * /login met dezelfde melding die LoginController bij direct-inloggen geeft.
 */
class CheckActiveUser
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Dit account is gedeactiveerd. Neem contact op met je teamleider.']);
        }

        return $next($request);
    }
}
