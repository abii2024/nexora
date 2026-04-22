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
