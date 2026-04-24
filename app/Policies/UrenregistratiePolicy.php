<?php

namespace App\Policies;

use App\Models\Urenregistratie;
use App\Models\User;

/**
 * Autorisatie voor urenregistratie (US-11 + US-12 + US-13).
 *
 *  - Zorgbegeleider: eigen uren — aanmaken, bewerken mits editable status
 *  - Teamleider: uren van eigen-team begeleiders — view + approve/reject (US-13)
 *  - Niemand mag delete (uren zijn immutable na indienen; concept-verwijdering via US-12 "intrekken")
 */
class UrenregistratiePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isZorgbegeleider() || $user->isTeamleider());
    }

    public function view(User $user, Urenregistratie $uren): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isTeamleider()) {
            return $uren->user?->team_id === $user->team_id;
        }

        return $uren->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isZorgbegeleider();
    }

    /**
     * US-11 AC-5: bewerken mag alleen bij concept/afgekeurd, én alleen eigenaar.
     */
    public function update(User $user, Urenregistratie $uren): bool
    {
        if (!$user->is_active) {
            return false;
        }

        return $uren->user_id === $user->id
            && $uren->status->isEditable();
    }

    public function delete(User $user, Urenregistratie $uren): bool
    {
        return false;
    }

    /**
     * US-12 AC-1: alleen eigenaar mag eigen concept-entry indienen.
     */
    public function submit(User $user, Urenregistratie $uren): bool
    {
        return $user->is_active
            && $uren->user_id === $user->id
            && $uren->status->isSubmittable();
    }

    /**
     * US-12 AC-3: alleen eigenaar mag ingediende entry terugtrekken.
     */
    public function withdraw(User $user, Urenregistratie $uren): bool
    {
        return $user->is_active
            && $uren->user_id === $user->id
            && $uren->status->isWithdrawable();
    }

    /**
     * US-12 AC-4: alleen eigenaar mag afgekeurde entry corrigeren + opnieuw indienen.
     */
    public function resubmit(User $user, Urenregistratie $uren): bool
    {
        return $user->is_active
            && $uren->user_id === $user->id
            && $uren->status->canResubmit();
    }
}
