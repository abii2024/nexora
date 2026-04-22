<?php

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Services\ClientService;

beforeEach(function () {
    $this->service = new ClientService;

    $this->teamA = Team::factory()->create();
    $this->teamB = Team::factory()->create();

    $this->teamleiderA = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
    $this->zorgA1 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
    $this->zorgA2 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
    $this->zorgB = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);
    $this->inactive = User::factory()->zorgbegeleider()->inactive()->create(['team_id' => $this->teamA->id]);

    // 3 cliënten in teamA, 1 in teamB
    $this->c1 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C1']);
    $this->c2 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C2']);
    $this->c3 = Client::factory()->create(['team_id' => $this->teamA->id, 'voornaam' => 'C3']);
    $this->c4 = Client::factory()->create(['team_id' => $this->teamB->id, 'voornaam' => 'C4']);

    // zorgA1 gekoppeld aan C1+C2. zorgA2 aan C3.
    $this->c1->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_PRIMAIR]);
    $this->c2->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_SECUNDAIR]);
    $this->c3->caregivers()->attach($this->zorgA2->id, ['role' => Client::ROLE_PRIMAIR]);
});

/*
 * US-02 AC-4: Teamleider ziet alleen clients van eigen team.
 */
it('returns all clients in own team for a teamleider', function () {
    $ids = $this->service->scopedForUser($this->teamleiderA)->pluck('id')->toArray();

    expect($ids)->toHaveCount(3);
    expect($ids)->toContain($this->c1->id, $this->c2->id, $this->c3->id);
    expect($ids)->not->toContain($this->c4->id);
});

/*
 * US-02 AC-5: Zorgbegeleider ziet alleen eigen gekoppelde clients.
 */
it('returns only linked clients for a zorgbegeleider (US-02 AC-5 kern)', function () {
    $ids = $this->service->scopedForUser($this->zorgA1)->pluck('id')->toArray();

    expect($ids)->toHaveCount(2);
    expect($ids)->toContain($this->c1->id, $this->c2->id);
    expect($ids)->not->toContain($this->c3->id);
    expect($ids)->not->toContain($this->c4->id);
});

it('does not leak clients across zorgbegeleiders in the same team', function () {
    $a1Ids = $this->service->scopedForUser($this->zorgA1)->pluck('id')->toArray();
    $a2Ids = $this->service->scopedForUser($this->zorgA2)->pluck('id')->toArray();

    expect(array_intersect($a1Ids, $a2Ids))->toBeEmpty();
    expect($a2Ids)->toBe([$this->c3->id]);
});

it('returns empty set for zorgbegeleider with no linked clients', function () {
    $ids = $this->service->scopedForUser($this->zorgB)->pluck('id')->toArray();

    expect($ids)->toBe([]);
});

it('returns empty set for inactive user as defense in depth', function () {
    $ids = $this->service->scopedForUser($this->inactive)->pluck('id')->toArray();

    expect($ids)->toBe([]);
});

/*
 * Scope-correctness bij mutatie — re-koppelen propageert direct.
 */
it('reflects caregiver changes immediately in the scope', function () {
    expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(2);

    $this->c3->caregivers()->attach($this->zorgA1->id, ['role' => Client::ROLE_TERTIAIR]);
    expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(3);

    $this->c1->caregivers()->detach($this->zorgA1->id);
    expect($this->service->scopedForUser($this->zorgA1)->count())->toBe(2);
});
