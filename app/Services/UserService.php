<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centrale plek voor User-mutaties (US-05).
 *
 * Waarom een service: AVG art. 30 eist dat elke wijziging op
 * persoonsgegevens traceerbaar is. Door update altijd via deze service
 * te laten lopen, is audit-logging een systeemgarantie — niet iets dat
 * een controller kan vergeten.
 */
class UserService
{
    /**
     * Werkt een teammember bij en legt elke veldwijziging vast in
     * user_audit_logs. Self-demotion wordt geweigerd als de user de
     * enige actieve teamleider in zijn team is.
     *
     * @param  array<string, mixed>  $payload  velden die gewijzigd kunnen worden
     *                                          (name, email, role, dienstverband)
     *
     * @throws ValidationException  wanneer self-demotion de laatste teamleider weghaalt
     */
    public function updateWithAudit(User $member, array $payload, User $changedBy): User
    {
        $this->ensureTeamRetainsTeamleider($member, $payload, $changedBy);

        $auditable = ['name', 'email', 'role', 'dienstverband'];

        return DB::transaction(function () use ($member, $payload, $changedBy, $auditable) {
            foreach ($auditable as $field) {
                if (! array_key_exists($field, $payload)) {
                    continue;
                }

                $old = $member->getOriginal($field);
                $new = $payload[$field];

                if ((string) $old === (string) $new) {
                    continue;
                }

                UserAuditLog::create([
                    'user_id' => $member->id,
                    'changed_by_user_id' => $changedBy->id,
                    'field' => $field,
                    'old_value' => $old,
                    'new_value' => $new,
                ]);

                $member->{$field} = $new;
            }

            $member->save();

            return $member;
        });
    }

    /**
     * Deactiveert een teamlid (US-06). Blokkeert self-deactivation en
     * voorkomt dat de laatste actieve teamleider wordt uitgeschakeld.
     *
     * Legt een audit-rij vast en invalideert alle session-rijen van de
     * target user zodat andere devices direct uitloggen (defense-in-depth
     * naast CheckActiveUser middleware).
     *
     * @throws ValidationException
     */
    public function deactivate(User $member, User $changedBy): User
    {
        if ($member->id === $changedBy->id) {
            throw ValidationException::withMessages([
                'is_active' => 'Je kunt jezelf niet deactiveren.',
            ]);
        }

        if ($member->isTeamleider()) {
            $otherActive = User::query()
                ->where('team_id', $member->team_id)
                ->where('id', '!=', $member->id)
                ->where('role', User::ROLE_TEAMLEIDER)
                ->where('is_active', true)
                ->count();

            if ($otherActive === 0) {
                throw ValidationException::withMessages([
                    'is_active' => 'Je kunt de enige actieve teamleider van het team niet deactiveren.',
                ]);
            }
        }

        if (! $member->is_active) {
            return $member; // idempotent — al inactief
        }

        return DB::transaction(function () use ($member, $changedBy) {
            UserAuditLog::create([
                'user_id' => $member->id,
                'changed_by_user_id' => $changedBy->id,
                'field' => 'is_active',
                'old_value' => '1',
                'new_value' => '0',
            ]);

            $member->is_active = false;
            $member->save();

            $this->invalidateSessionsFor($member);

            return $member;
        });
    }

    /**
     * Heractiveert een teamlid (US-06). Wachtwoord blijft ongewijzigd —
     * user kan direct weer inloggen met bestaand wachtwoord.
     */
    public function activate(User $member, User $changedBy): User
    {
        if ($member->is_active) {
            return $member; // idempotent — al actief
        }

        return DB::transaction(function () use ($member, $changedBy) {
            UserAuditLog::create([
                'user_id' => $member->id,
                'changed_by_user_id' => $changedBy->id,
                'field' => 'is_active',
                'old_value' => '0',
                'new_value' => '1',
            ]);

            $member->is_active = true;
            $member->save();

            return $member;
        });
    }

    /**
     * Verwijdert openstaande web-sessies voor een user uit de database-
     * session store, zodat andere devices bij de volgende request niet
     * meer herkend worden. Werkt alleen als SESSION_DRIVER=database.
     *
     * Fallback: CheckActiveUser middleware vangt de rest op bij elke
     * authenticated request (zelfs met file-session driver werkt dat).
     */
    protected function invalidateSessionsFor(User $member): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = config('session.table', 'sessions');

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        DB::table($table)->where('user_id', $member->id)->delete();
    }

    /**
     * Verhindert dat een team zonder actieve teamleider komt te staan.
     *
     * Regel: als de target-user zijn eigen rol wijzigt van teamleider naar
     * iets anders, moet er nog minstens één andere actieve teamleider in
     * zijn team over zijn.
     */
    protected function ensureTeamRetainsTeamleider(User $member, array $payload, User $changedBy): void
    {
        $isSelfEdit = $member->id === $changedBy->id;
        $wasTeamleider = $member->role === User::ROLE_TEAMLEIDER;
        $newRole = $payload['role'] ?? $member->role;
        $becomesZorgbegeleider = $newRole !== User::ROLE_TEAMLEIDER;

        if (! ($isSelfEdit && $wasTeamleider && $becomesZorgbegeleider)) {
            return;
        }

        $otherActiveTeamleidersInTeam = User::query()
            ->where('team_id', $member->team_id)
            ->where('id', '!=', $member->id)
            ->where('role', User::ROLE_TEAMLEIDER)
            ->where('is_active', true)
            ->count();

        if ($otherActiveTeamleidersInTeam === 0) {
            throw ValidationException::withMessages([
                'role' => 'Je kunt je eigen teamleider-rol niet verwijderen zolang je de enige teamleider van je team bent.',
            ]);
        }
    }
}
