<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Notifications\ClientCaregiverAssignedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Centrale plek voor cliënt-queries en -mutaties.
 *
 * US-02: scopedForUser (scope-regels op één plek voor AVG-auditeerbaarheid).
 * US-07: create (DB-transactie + audit-velden).
 * US-08: computeCaregiverRoles + syncCaregivers (rol-toewijzing + notifications).
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

    /**
     * Maakt een nieuwe cliënt binnen een DB-transactie (US-07).
     *
     * @param  array<string, mixed>  $payload  uit StoreClientRequest
     */
    public function create(array $payload): Client
    {
        return DB::transaction(fn () => Client::create($payload));
    }

    /**
     * US-08 AC-1 + AC-2: bepaalt rol per userId.
     *
     * Regels:
     *  - Primair is de $explicitPrimaryId (indien opgegeven en aanwezig in $userIds),
     *    anders het eerste element van $userIds.
     *  - Tweede element in resterende volgorde = secundair.
     *  - Alle overige = tertiair.
     *  - Duplicates in $userIds worden gededupliceerd, volgorde behouden.
     *
     * @param  list<int>  $userIds
     * @return array<int, string>  userId => role
     */
    public function computeCaregiverRoles(array $userIds, ?int $explicitPrimaryId = null): array
    {
        $unique = array_values(array_unique(array_map('intval', $userIds)));

        if ($unique === []) {
            return [];
        }

        // Promote explicit primary to first position if present.
        if ($explicitPrimaryId !== null && in_array($explicitPrimaryId, $unique, true)) {
            $unique = array_values(array_filter($unique, fn ($id) => $id !== $explicitPrimaryId));
            array_unshift($unique, $explicitPrimaryId);
        }

        $roles = [];
        foreach ($unique as $i => $id) {
            $roles[$id] = match ($i) {
                0 => Client::ROLE_PRIMAIR,
                1 => Client::ROLE_SECUNDAIR,
                default => Client::ROLE_TERTIAIR,
            };
        }

        return $roles;
    }

    /**
     * US-08: synchroniseert caregivers voor een client via delete-insert patroon.
     *
     *  - Transactioneel (AVG + Multi-caregiver integriteit).
     *  - Verwijdert pivots die niet meer in de nieuwe set zitten.
     *  - Update bestaande pivots met nieuwe rol (zonder detach+attach, zodat
     *    gekoppelde data zoals created_by_user_id behouden blijft).
     *  - Voegt nieuwe koppelingen toe met created_by_user_id = $changedBy.
     *  - Notification::send alleen naar NIEUWE koppelingen (AC-4).
     *
     * @param  list<int>  $userIds
     */
    public function syncCaregivers(Client $client, array $userIds, ?int $primaryId, User $changedBy): void
    {
        DB::transaction(function () use ($client, $userIds, $primaryId, $changedBy) {
            $roles = $this->computeCaregiverRoles($userIds, $primaryId);

            $currentIds = $client->caregivers()->pluck('users.id')->all();
            $newIds = array_keys($roles);

            // 1. Remove: bestaande die niet meer aangevinkt zijn.
            $toRemove = array_diff($currentIds, $newIds);
            if ($toRemove !== []) {
                $client->caregivers()->detach($toRemove);
            }

            // 2. Stagefase: alle blijvende koppelingen tijdelijk naar 'tertiair'
            //    zodat partial unique indexes (primair+secundair) niet botsen
            //    bij role-swaps (bijv. A→primair→secundair + B→secundair→primair).
            //    Tertiair heeft geen unique constraint — veilig tussenstation.
            $commonIds = array_intersect($currentIds, $newIds);
            if ($commonIds !== []) {
                DB::table('client_caregivers')
                    ->where('client_id', $client->id)
                    ->whereIn('user_id', $commonIds)
                    ->update([
                        'role' => Client::ROLE_TERTIAIR,
                        'updated_at' => now(),
                    ]);
            }

            // 3. Upsert: assign de uiteindelijke rollen.
            $nieuwGekoppeld = [];
            foreach ($newIds as $userId) {
                $role = $roles[$userId];

                if (in_array($userId, $currentIds, true)) {
                    $client->caregivers()->updateExistingPivot($userId, [
                        'role' => $role,
                        'updated_at' => now(),
                    ]);
                } else {
                    $client->caregivers()->attach($userId, [
                        'role' => $role,
                        'created_by_user_id' => $changedBy->id,
                    ]);
                    $nieuwGekoppeld[] = ['user_id' => $userId, 'role' => $role];
                }
            }

            // 4. Notifications voor NIEUWE koppelingen (AC-4).
            foreach ($nieuwGekoppeld as $entry) {
                $user = User::find($entry['user_id']);
                if ($user) {
                    Notification::send(
                        $user,
                        new ClientCaregiverAssignedNotification($client, $entry['role'], $changedBy)
                    );
                }
            }
        });
    }
}
