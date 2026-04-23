<?php

/*
 * US-12 — Uren indienen, terugtrekken en opnieuw indienen
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: POST /uren/{id}/indienen (concept → ingediend) alleen als isIndienbaar()
 *  - AC-2: UrenIngediendNotification naar alle teamleiders van eigen team
 *  - AC-3: POST /uren/{id}/terugtrekken (ingediend → concept), niet na goedkeur/afkeur
 *  - AC-4: afgekeurde entries tonen notitie; resubmit wist afkeur_reden
 *  - AC-5: UrenregistratieService::transition() valideert hele matrix centraal
 */

use App\Enums\UrenStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Client;
use App\Models\Team;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Notifications\UrenIngediendNotification;
use App\Services\UrenregistratieService;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima El Amrani',
    ]);
    $this->tweedeTeamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Karim Yildiz',
    ]);
    $this->vreemdeTeamleider = User::factory()->teamleider()->create([
        'team_id' => $this->otherTeam->id,
        'name' => 'Peter Bakker',
    ]);

    $this->zorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
    ]);
    $this->anderZorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Mo Hassan',
    ]);

    $this->client = Client::factory()->create([
        'team_id' => $this->team->id,
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
    ]);
    $this->client->caregivers()->attach($this->zorg->id, [
        'role' => Client::ROLE_PRIMAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);

    $this->service = app(UrenregistratieService::class);
});

function makeConcept(User $user, Client $client, array $overrides = []): Urenregistratie
{
    return Urenregistratie::factory()
        ->concept()
        ->for($user, 'user')
        ->for($client)
        ->create(array_merge([
            'starttijd' => '09:00:00',
            'eindtijd' => '12:30:00',
            'uren' => 3.5,
        ], $overrides));
}

/* ──────────────────────────────────────────────────────────────────
 * AC-1: indienen — concept → ingediend
 * ────────────────────────────────────────────────────────────────── */

it('submits a valid concept entry and transitions to ingediend', function () {
    Notification::fake();
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)
        ->post(route('uren.submit', $uren))
        ->assertRedirect(route('uren.index', ['status' => 'ingediend']));

    expect($uren->refresh()->status)->toBe(UrenStatus::Ingediend);
});

it('rejects submit when uren is zero', function () {
    $uren = makeConcept($this->zorg, $this->client, ['uren' => 0.0]);

    $this->actingAs($this->zorg)
        ->post(route('uren.submit', $uren))
        ->assertStatus(422);

    expect($uren->refresh()->status)->toBe(UrenStatus::Concept);
});

it('isIndienbaar returns false when client_id is null', function () {
    $uren = makeConcept($this->zorg, $this->client);
    $uren->client_id = null;

    expect($uren->isIndienbaar())->toBeFalse();
});

it('denies submit by a non-owner with 403', function () {
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->anderZorg)
        ->post(route('uren.submit', $uren))
        ->assertForbidden();
});

it('denies submit by teamleider with 403', function () {
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->teamleider)
        ->post(route('uren.submit', $uren))
        ->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: notification naar alle teamleiders in eigen team
 * ────────────────────────────────────────────────────────────────── */

it('sends UrenIngediendNotification to every teamleider in the own team', function () {
    Notification::fake();
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)->post(route('uren.submit', $uren));

    Notification::assertSentTo($this->teamleider, UrenIngediendNotification::class);
    Notification::assertSentTo($this->tweedeTeamleider, UrenIngediendNotification::class);
});

it('does not notify teamleiders from other teams', function () {
    Notification::fake();
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)->post(route('uren.submit', $uren));

    Notification::assertNotSentTo($this->vreemdeTeamleider, UrenIngediendNotification::class);
});

it('does not notify zorgbegeleiders', function () {
    Notification::fake();
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)->post(route('uren.submit', $uren));

    Notification::assertNotSentTo($this->anderZorg, UrenIngediendNotification::class);
});

it('notification payload includes type uren_ingediend', function () {
    $uren = makeConcept($this->zorg, $this->client);
    $this->actingAs($this->zorg)->post(route('uren.submit', $uren));

    $note = \DB::table('notifications')->where('notifiable_id', $this->teamleider->id)->first();
    $data = json_decode($note->data, true);
    expect($data['type'])->toBe('uren_ingediend');
    expect($data['uren_id'])->toBe($uren->id);
    expect($data['submitted_by_user_id'])->toBe($this->zorg->id);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: terugtrekken — ingediend → concept
 * ────────────────────────────────────────────────────────────────── */

it('withdraws an ingediend entry back to concept', function () {
    $uren = Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->zorg)
        ->post(route('uren.withdraw', $uren))
        ->assertRedirect(route('uren.index', ['status' => 'concept']));

    expect($uren->refresh()->status)->toBe(UrenStatus::Concept);
});

it('rejects withdraw from goedgekeurd', function () {
    $uren = Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->zorg)
        ->post(route('uren.withdraw', $uren))
        ->assertForbidden();

    expect($uren->refresh()->status)->toBe(UrenStatus::Goedgekeurd);
});

it('rejects withdraw from afgekeurd', function () {
    $uren = Urenregistratie::factory()->afgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->zorg)
        ->post(route('uren.withdraw', $uren))
        ->assertForbidden();

    expect($uren->refresh()->status)->toBe(UrenStatus::Afgekeurd);
});

it('rejects withdraw from concept', function () {
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)
        ->post(route('uren.withdraw', $uren))
        ->assertForbidden();
});

it('denies withdraw by non-owner with 403', function () {
    $uren = Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->anderZorg)
        ->post(route('uren.withdraw', $uren))
        ->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: afgekeurde entries + resubmit
 * ────────────────────────────────────────────────────────────────── */

it('shows the teamleider_notitie on edit page for an afgekeurd entry', function () {
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create(['afkeur_reden' => 'Tijden kloppen niet — check je aantekeningen.']);

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertOk();
    $response->assertSee('Teamleider-notitie bij afkeur');
    $response->assertSee('Tijden kloppen niet — check je aantekeningen.');
});

it('resubmit clears afkeur_reden and transitions to ingediend', function () {
    Notification::fake();
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create([
            'afkeur_reden' => 'Tijden kloppen niet.',
            'starttijd' => '09:00:00',
            'eindtijd' => '12:00:00',
            'uren' => 3.0,
        ]);

    $response = $this->actingAs($this->zorg)
        ->post(route('uren.resubmit', $uren), [
            'client_id' => $this->client->id,
            'datum' => now()->format('Y-m-d'),
            'starttijd' => '10:00',
            'eindtijd' => '13:30',
            'notities' => 'Tijden gecorrigeerd.',
        ]);

    $response->assertRedirect(route('uren.index', ['status' => 'ingediend']));
    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Ingediend);
    expect($uren->afkeur_reden)->toBeNull();
    expect((float) $uren->uren)->toBe(3.5);
});

it('resubmit notifies all teamleiders again', function () {
    Notification::fake();
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create(['starttijd' => '09:00:00', 'eindtijd' => '12:00:00', 'uren' => 3.0]);

    $this->actingAs($this->zorg)->post(route('uren.resubmit', $uren), [
        'client_id' => $this->client->id,
        'datum' => now()->format('Y-m-d'),
        'starttijd' => '10:00',
        'eindtijd' => '13:30',
        'notities' => null,
    ]);

    Notification::assertSentTo($this->teamleider, UrenIngediendNotification::class);
    Notification::assertSentTo($this->tweedeTeamleider, UrenIngediendNotification::class);
});

it('denies resubmit on a concept entry with 403', function () {
    $uren = makeConcept($this->zorg, $this->client);

    $this->actingAs($this->zorg)
        ->post(route('uren.resubmit', $uren), [
            'client_id' => $this->client->id,
            'datum' => now()->format('Y-m-d'),
            'starttijd' => '09:00',
            'eindtijd' => '12:00',
        ])
        ->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: centrale transition() matrix
 * ────────────────────────────────────────────────────────────────── */

it('transition concept to ingediend succeeds when indienbaar', function () {
    Notification::fake();
    $uren = makeConcept($this->zorg, $this->client);

    $this->service->transition($uren, UrenStatus::Ingediend, $this->zorg);

    expect($uren->refresh()->status)->toBe(UrenStatus::Ingediend);
});

it('transition ingediend to concept succeeds', function () {
    $uren = Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $this->service->transition($uren, UrenStatus::Concept, $this->zorg);

    expect($uren->refresh()->status)->toBe(UrenStatus::Concept);
});

it('transition afgekeurd to ingediend succeeds', function () {
    Notification::fake();
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create([
            'afkeur_reden' => 'Check tijden.',
            'starttijd' => '09:00:00',
            'eindtijd' => '12:00:00',
            'uren' => 3.0,
        ]);

    $this->service->transition($uren, UrenStatus::Ingediend, $this->zorg);

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Ingediend);
    expect($uren->afkeur_reden)->toBeNull();
});

it('transition rejects concept to goedgekeurd', function () {
    $uren = makeConcept($this->zorg, $this->client);

    expect(fn () => $this->service->transition($uren, UrenStatus::Goedgekeurd, $this->zorg))
        ->toThrow(InvalidStateTransitionException::class);
});

it('transition rejects concept to afgekeurd', function () {
    $uren = makeConcept($this->zorg, $this->client);

    expect(fn () => $this->service->transition($uren, UrenStatus::Afgekeurd, $this->zorg))
        ->toThrow(InvalidStateTransitionException::class);
});

it('transition rejects goedgekeurd to any other state', function () {
    $uren = Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    foreach ([UrenStatus::Concept, UrenStatus::Ingediend, UrenStatus::Afgekeurd] as $to) {
        expect(fn () => $this->service->transition($uren->fresh(), $to, $this->zorg))
            ->toThrow(InvalidStateTransitionException::class);
    }
});

it('transition rejects afgekeurd to concept (must go via resubmit)', function () {
    $uren = Urenregistratie::factory()->afgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    expect(fn () => $this->service->transition($uren, UrenStatus::Concept, $this->zorg))
        ->toThrow(InvalidStateTransitionException::class);
});

it('transition rejects submit when entry is not indienbaar', function () {
    $uren = makeConcept($this->zorg, $this->client, ['uren' => 0.0]);

    expect(fn () => $this->service->transition($uren, UrenStatus::Ingediend, $this->zorg))
        ->toThrow(InvalidStateTransitionException::class);
});

/* ──────────────────────────────────────────────────────────────────
 * Helpers + enum
 * ────────────────────────────────────────────────────────────────── */

it('UrenStatus transition helpers return correct values', function () {
    expect(UrenStatus::Concept->isSubmittable())->toBeTrue();
    expect(UrenStatus::Ingediend->isSubmittable())->toBeFalse();
    expect(UrenStatus::Ingediend->isWithdrawable())->toBeTrue();
    expect(UrenStatus::Concept->isWithdrawable())->toBeFalse();
    expect(UrenStatus::Afgekeurd->canResubmit())->toBeTrue();
    expect(UrenStatus::Goedgekeurd->canResubmit())->toBeFalse();
});

it('isIndienbaar returns false when required fields missing', function () {
    $uren = makeConcept($this->zorg, $this->client);
    expect($uren->isIndienbaar())->toBeTrue();

    $uren->uren = 0.0;
    expect($uren->isIndienbaar())->toBeFalse();
});

/* ──────────────────────────────────────────────────────────────────
 * UI regressie
 * ────────────────────────────────────────────────────────────────── */

it('index shows Indienen button for own concept entries', function () {
    makeConcept($this->zorg, $this->client);

    $response = $this->actingAs($this->zorg)->get(route('uren.index'));

    $response->assertOk();
    $response->assertSee('Indienen');
});

it('index shows Terugtrekken button for ingediend tab', function () {
    Urenregistratie::factory()->ingediend()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.index', ['status' => 'ingediend']));

    $response->assertOk();
    $response->assertSee('Terugtrekken');
});

it('index goedgekeurd tab shows Read-only without action forms', function () {
    Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $response = $this->actingAs($this->zorg)->get(route('uren.index', ['status' => 'goedgekeurd']));

    $response->assertOk();
    $response->assertSee('Read-only');
    $response->assertDontSee('Indienen</button>', false);
    $response->assertDontSee('Terugtrekken</button>', false);
});
