<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Autorisatie voor Client-resources (US-02 Rolgebaseerde toegang).
 *
 * Regels:
 *  - Inactieve gebruikers krijgen nooit toegang.
 *  - Teamleider mag alleen cliënten uit eigen team zien/muteren.
 *  - Zorgbegeleider mag alleen cliënten zien/muteren waar hij via
 *    `client_caregivers` aan gekoppeld is.
 *  - Alleen teamleider mag cliënten aanmaken/verwijderen/herstellen
 *    (aligneert met US-07/US-10 businessregels).
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Client $client): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isTeamleider()) {
            return $user->team_id === $client->team_id;
        }

        if ($user->isZorgbegeleider()) {
            return $client->caregivers()
                ->where('users.id', $user->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isTeamleider();
    }

    public function update(User $user, Client $client): bool
    {
        // Teamleider kan binnen eigen team updaten.
        // Zorgbegeleider kan alleen cliënt-zorggegevens updaten als gekoppeld
        // (preciezere split komt in US-10 via aparte policy methods).
        return $this->view($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->is_active
            && $user->isTeamleider()
            && $user->team_id === $client->team_id;
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->delete($user, $client);
    }

    public function forceDelete(User $user, Client $client): bool
    {
        // Permanent verwijderen is altijd geblokkeerd vanuit UI — zie US-10
        // "Permanente verwijdering is NIET mogelijk". Alleen via Artisan.
        return false;
    }
}
