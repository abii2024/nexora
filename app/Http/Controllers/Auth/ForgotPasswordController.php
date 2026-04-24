<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * AC-1: formulier voor e-mail invoeren.
     */
    public function show(): View
    {
        return view('auth.wachtwoord-vergeten');
    }

    /**
     * AC-1 + AC-2: `Password::sendResetLink` geeft altijd dezelfde UX-melding
     * terug — ongeacht of de user bestaat. Dit voorkomt e-mail-enumeration.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            'Als dit adres bij een Nexora-account hoort, is er een reset-link verstuurd. Controleer ook je spam-folder.'
        );
    }
}
