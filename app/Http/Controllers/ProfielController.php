<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profiel\UpdateProfielRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * US-16: beheer van eigen profielgegevens + wachtwoord door de ingelogde user.
 *
 * Security-waarborgen:
 *  - role / is_active / team_id zijn NIET in de UpdateProfielRequest-regels → genegeerd
 *  - password-wijziging vereist current_password-verificatie
 *  - na succesvolle password-wijziging: Auth::logoutOtherDevices
 */
class ProfielController extends Controller
{
    public function show(Request $request): View
    {
        return view('profiel.index', ['user' => $request->user()]);
    }

    public function update(UpdateProfielRequest $request): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->profielData();
        $newPassword = $request->newPassword();

        // forceFill om expliciet te zijn over WELKE velden mogen muteren.
        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
        ])->save();

        if ($newPassword !== null) {
            $user->forceFill(['password' => Hash::make($newPassword)])->save();

            // AC-5: andere sessies van dezelfde user ongeldig maken.
            Auth::logoutOtherDevices($newPassword);
        }

        return redirect()
            ->route('profiel.show')
            ->with('success', $newPassword !== null
                ? 'Profiel bijgewerkt — je bent uitgelogd op andere apparaten.'
                : 'Profiel bijgewerkt.');
    }
}
