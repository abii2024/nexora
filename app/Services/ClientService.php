<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centrale plek voor cliënt-queries.
 *
 * Waarom een service en geen Eloquent scope? US-02 eist dat de scope-regels
 * op één plek staan zodat we ze auditeerbaar kunnen bewijzen (AVG least
 * privilege). US-09 bouwt hier een `getPaginated()` bovenop, US-10 voegt
 * archivering toe. Voor nu alleen `scopedForUser()` — de single source of
 * truth voor "welke cliënten mag deze user zien".
 */
class ClientService
{
    /**
     * Bouw een query die alleen cliënten retourneert waar $user toegang toe heeft.
     *
     *  - Teamleider: alle cliënten binnen eigen team_id.
     *  - Zorgbegeleider: alleen cliënten waar hij via `client_caregivers` gekoppeld is.
     *  - Inactieve users: lege set (extra defense in depth — middleware weigert al login).
     *  - Onbekende rol: lege set.
     */
    public function scopedForUser(User $user): Builder
    {
        if (! $user->is_active) {
            return Client::query()->whereRaw('1 = 0');
        }

        if ($user->isTeamleider()) {
            return Client::query()->where('team_id', $user->team_id);
        }

        if ($user->isZorgbegeleider()) {
            return Client::query()->whereHas(
                'caregivers',
                fn (Builder $q) => $q->where('users.id', $user->id)
            );
        }

        return Client::query()->whereRaw('1 = 0');
    }
}
