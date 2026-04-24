<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /**
     * AC-3: reset-form uit de e-maillink (bevat token + email in query-string).
     */
    public function show(Request $request, string $token): View
    {
        return view('auth.wachtwoord-reset', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * AC-3 + AC-4 + AC-5:
     *  - password → bcrypt via User::$casts['password' => 'hashed']
     *  - `Password::reset` valideert token + lifetime; gebruikt/verlopen → fout
     *  - Auto-login na succesvolle reset → redirect naar rol-specifieke dashboard
     */
    public function store(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => null,
                ])->save();

                Auth::login($user);
            }
        );

        if ($status !== Password::PasswordReset) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        $user = $request->user();
        $target = $user && $user->isTeamleider()
            ? route('teamleider.dashboard')
            : route('dashboard');

        return redirect($target)->with('status', 'Je wachtwoord is bijgewerkt en je bent ingelogd.');
    }
}
