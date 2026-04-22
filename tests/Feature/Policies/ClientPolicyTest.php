<?php

use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Policies\ClientPolicy;

beforeEach(function () {
    $this->policy = new ClientPolicy;
    $this->teamA = Team::factory()->create(['name' => 'Team A']);
    $this->teamB = Team::factory()->create(['name' => 'Team B']);

    $this->teamleiderA = User::factory()->teamleider()->create(['team_id' => $this->teamA->id]);
    $this->teamleiderB = User::factory()->teamleider()->create(['team_id' => $this->teamB->id]);

    $this->zorgA1 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
    $this->zorgA2 = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamA->id]);
    $this->zorgB = User::factory()->zorgbegeleider()->create(['team_id' => $this->teamB->id]);

    $this->inactive = User::factory()->zorgbegeleider()->inactive()->create(['team_id' => $this->teamA->id]);

    $this->clientA = Client::factory()->create(['team_id' => $this->teamA->id]);
    $this->clientB = Client::factory()->create(['team_id' => $this->teamB->id]);

    // zorgA1 gekoppeld aan clientA als primair
    $this->clientA->caregivers()->attach($this->zorgA1->id, [
        'role' => Client::ROLE_PRIMAIR,
    ]);
});

/*
 * US-02 AC-4: Teamleider ziet eigen team.
 */
it('allows teamleider to view clients in their own team', function () {
    expect($this->policy->view($this->teamleiderA, $this->clientA))->toBeTrue();
});

it('denies teamleider viewing clients from another team', function () {
    expect($this->policy->view($this->teamleiderA, $this->clientB))->toBeFalse();
    expect($this->policy->view($this->teamleiderB, $this->clientA))->toBeFalse();
});

/*
 * US-02 AC-2 + AC-5: Zorgbegeleider alleen via client_caregivers.
 */
it('allows zorgbegeleider to view a client they are linked to', function () {
    expect($this->policy->view($this->zorgA1, $this->clientA))->toBeTrue();
});

it('denies zorgbegeleider viewing a client they are NOT linked to (US-02 AC-2 kern)', function () {
    // zorgA2 zit in zelfde team als clientA maar is niet gekoppeld -> geen toegang.
    expect($this->policy->view($this->zorgA2, $this->clientA))->toBeFalse();
});

it('denies zorgbegeleider viewing clients from a different team regardless of pivot', function () {
    expect($this->policy->view($this->zorgB, $this->clientA))->toBeFalse();
    expect($this->policy->view($this->zorgB, $this->clientB))->toBeFalse();
});

/*
 * Defense in depth: inactieve users nooit toegang.
 */
it('denies inactive users even when they would otherwise be linked', function () {
    $this->clientA->caregivers()->attach($this->inactive->id, ['role' => Client::ROLE_TERTIAIR]);

    expect($this->policy->view($this->inactive, $this->clientA))->toBeFalse();
    expect($this->policy->viewAny($this->inactive))->toBeFalse();
});

/*
 * create: alleen teamleider.
 */
it('only allows teamleider to create clients', function () {
    expect($this->policy->create($this->teamleiderA))->toBeTrue();
    expect($this->policy->create($this->zorgA1))->toBeFalse();
    expect($this->policy->create($this->inactive))->toBeFalse();
});

/*
 * update volgt view-regels (via delegatie).
 */
it('allows update for users who can view', function () {
    expect($this->policy->update($this->teamleiderA, $this->clientA))->toBeTrue();
    expect($this->policy->update($this->zorgA1, $this->clientA))->toBeTrue();
});

it('denies update for users who cannot view', function () {
    expect($this->policy->update($this->teamleiderB, $this->clientA))->toBeFalse();
    expect($this->policy->update($this->zorgA2, $this->clientA))->toBeFalse();
});

/*
 * delete: alleen teamleider binnen eigen team.
 */
it('allows only teamleider of own team to delete', function () {
    expect($this->policy->delete($this->teamleiderA, $this->clientA))->toBeTrue();
    expect($this->policy->delete($this->teamleiderB, $this->clientA))->toBeFalse();
    expect($this->policy->delete($this->zorgA1, $this->clientA))->toBeFalse();
});

/*
 * forceDelete: nooit via UI (US-10 business-rule).
 */
it('never allows forceDelete from UI', function () {
    expect($this->policy->forceDelete($this->teamleiderA, $this->clientA))->toBeFalse();
});

/*
 * viewAny: active users mogen index openen (scoping gebeurt in Service).
 */
it('allows any active user to call viewAny', function () {
    expect($this->policy->viewAny($this->teamleiderA))->toBeTrue();
    expect($this->policy->viewAny($this->zorgA1))->toBeTrue();
    expect($this->policy->viewAny($this->inactive))->toBeFalse();
});
