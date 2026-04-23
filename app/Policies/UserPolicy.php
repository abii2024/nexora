<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorisatie voor User-beheer (teamleden).
 *
 * Regels (US-03 + vooruitzicht op US-04/US-05/US-06):
 *  - Alleen actieve teamleiders mogen het medewerkersoverzicht bekijken en
 *    medewerkers aanmaken/bewerken/deactiveren binnen hun eigen team.
 *  - Zorgbegeleiders kunnen alleen hun eigen profiel zien (US-16) — komt later;
 *    view() dekt dat nu al af voor het eigen User-record.
 *  - Self-demotion guard (US-05): teamleider kan zichzelf niet updaten naar
 *    andere rol — bewaakt hier; UpdateTeamMemberRequest controleert extra.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isTeamleider();
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->isTeamleider() && $user->team_id === $model->team_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isTeamleider();
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_active
            && $user->isTeamleider()
            && $user->team_id === $model->team_id;
    }

    /**
     * "Delete" = deactiveren (soft disable via is_active=false).
     * Hard delete blijft geblokkeerd — Wgbo bewaarplicht (US-06).
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->is_active
            && $user->isTeamleider()
            && $user->team_id === $model->team_id;
    }

    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
