<?php

/*
 * US-13 — Uren goedkeuren of afkeuren als teamleider
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: /teamleider/uren toont ingediende uren gegroepeerd per medewerker
 *  - AC-2: Goedkeuren → status=Goedgekeurd + UrenGoedgekeurdNotification naar zorgbeg
 *  - AC-3: Afkeuren → verplichte teamleider_notitie min 10 tekens (via AfkeurUrenRequest)
 *  - AC-4: Afgekeurde entry toont reden-banner bij zorgbeg + resubmit werkt (US-12 hergebruik)
 *  - AC-5: Zorgbegeleider op /teamleider/uren/{id}/goedkeuren → 403 via middleware + policy
 */

use App\Enums\UrenStatus;
use App\Models\Client;
use App\Models\Team;
use App\Models\Urenregistratie;
use App\Models\User;
use App\Notifications\UrenAfgekeurdNotification;
use App\Notifications\UrenGoedgekeurdNotification;
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
    $this->vreemdeTeamleider = User::factory()->teamleider()->create([
        'team_id' => $this->otherTeam->id,
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
    $this->client->caregivers()->attach($this->anderZorg->id, [
        'role' => Client::ROLE_SECUNDAIR,
        'created_by_user_id' => $this->teamleider->id,
    ]);
});

function makeIngediend(User $zorg, Client $client, array $overrides = []): Urenregistratie
{
    return Urenregistratie::factory()
        ->ingediend()
        ->for($zorg, 'user')
        ->for($client)
        ->create(array_merge([
            'datum' => now()->subDays(2)->format('Y-m-d'),
            'starttijd' => '09:00:00',
            'eindtijd' => '12:30:00',
            'uren' => 3.5,
        ], $overrides));
}

/* ──────────────────────────────────────────────────────────────────
 * AC-1: /teamleider/uren index
 * ────────────────────────────────────────────────────────────────── */

it('shows teamleider uren index with all ingediende entries grouped by user', function () {
    makeIngediend($this->zorg, $this->client);
    makeIngediend($this->zorg, $this->client);
    makeIngediend($this->anderZorg, $this->client);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.index'));

    $response->assertOk();
    $response->assertSee('Uren beoordelen');
    $response->assertSee('Jeroen Bakker');
    $response->assertSee('Mo Hassan');
    expect($response->viewData('totalRows'))->toBe(3);
    expect($response->viewData('groups'))->toHaveCount(2);
});

it('shows empty-state when no ingediende uren', function () {
    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.index'));

    $response->assertOk();
    $response->assertSee('Niets te beoordelen');
});

it('excludes non-ingediende entries (concept, goedgekeurd, afgekeurd)', function () {
    Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();
    Urenregistratie::factory()->afgekeurd()->for($this->zorg, 'user')->for($this->client)->create();
    makeIngediend($this->zorg, $this->client);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.index'));

    expect($response->viewData('totalRows'))->toBe(1);
});

it('only shows ingediende uren from own team', function () {
    makeIngediend($this->zorg, $this->client);

    $foreignClient = Client::factory()->create(['team_id' => $this->otherTeam->id]);
    $foreignZorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->otherTeam->id]);
    makeIngediend($foreignZorg, $foreignClient);

    $response = $this->actingAs($this->teamleider)->get(route('teamleider.uren.index'));

    expect($response->viewData('totalRows'))->toBe(1);
});

it('denies index for zorgbegeleider (middleware)', function () {
    $response = $this->actingAs($this->zorg)->get(route('teamleider.uren.index'));

    $response->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: goedkeuren
 * ────────────────────────────────────────────────────────────────── */

it('approves an ingediende entry and notifies the zorgbegeleider', function () {
    Notification::fake();
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->teamleider)
        ->post(route('teamleider.uren.approve', $uren))
        ->assertRedirect(route('teamleider.uren.index'));

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Goedgekeurd);
    expect($uren->goedgekeurd_door_user_id)->toBe($this->teamleider->id);
    expect($uren->beoordeeld_op)->not->toBeNull();

    Notification::assertSentTo($this->zorg, UrenGoedgekeurdNotification::class);
});

it('does not notify other zorgbegeleiders on approve', function () {
    Notification::fake();
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->teamleider)->post(route('teamleider.uren.approve', $uren));

    Notification::assertNotSentTo($this->anderZorg, UrenGoedgekeurdNotification::class);
});

it('denies approve for zorgbegeleider with 403', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->zorg)
        ->post(route('teamleider.uren.approve', $uren))
        ->assertForbidden();
});

it('denies approve for teamleider of a different team with 403', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->vreemdeTeamleider)
        ->post(route('teamleider.uren.approve', $uren))
        ->assertForbidden();
});

it('denies approve when entry is already goedgekeurd', function () {
    $uren = Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->teamleider)
        ->post(route('teamleider.uren.approve', $uren))
        ->assertForbidden();
});

it('denies approve when entry is still concept', function () {
    $uren = Urenregistratie::factory()->concept()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->teamleider)
        ->post(route('teamleider.uren.approve', $uren))
        ->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: afkeuren + verplichte teamleider_notitie
 * ────────────────────────────────────────────────────────────────── */

it('rejects afkeur when teamleider_notitie is missing', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $response = $this->actingAs($this->teamleider)
        ->from(route('teamleider.uren.index'))
        ->post(route('teamleider.uren.reject', $uren), []);

    $response->assertSessionHasErrors('teamleider_notitie');
    expect($uren->refresh()->status)->toBe(UrenStatus::Ingediend);
});

it('rejects afkeur when teamleider_notitie is under 10 chars', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $response = $this->actingAs($this->teamleider)
        ->from(route('teamleider.uren.index'))
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => 'Te kort.',
        ]);

    $response->assertSessionHasErrors('teamleider_notitie');
    expect($uren->refresh()->status)->toBe(UrenStatus::Ingediend);
});

it('rejects afkeur when teamleider_notitie is only whitespace', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $response = $this->actingAs($this->teamleider)
        ->from(route('teamleider.uren.index'))
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => '             ',
        ]);

    $response->assertSessionHasErrors('teamleider_notitie');
});

it('accepts afkeur with valid notitie and stores afkeur_reden', function () {
    Notification::fake();
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->teamleider)
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => 'Starttijd klopt niet — check je rooster.',
        ])
        ->assertRedirect(route('teamleider.uren.index'));

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Afgekeurd);
    expect($uren->afkeur_reden)->toBe('Starttijd klopt niet — check je rooster.');
    expect($uren->goedgekeurd_door_user_id)->toBe($this->teamleider->id);

    Notification::assertSentTo($this->zorg, UrenAfgekeurdNotification::class);
});

it('denies afkeur for zorgbegeleider with 403', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->zorg)
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => 'Deze notitie is lang genoeg.',
        ])
        ->assertForbidden();
});

it('denies afkeur for teamleider of a different team with 403', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $this->actingAs($this->vreemdeTeamleider)
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => 'Deze notitie is lang genoeg.',
        ])
        ->assertForbidden();
});

it('denies afkeur when entry is not ingediend', function () {
    $uren = Urenregistratie::factory()->goedgekeurd()->for($this->zorg, 'user')->for($this->client)->create();

    $this->actingAs($this->teamleider)
        ->post(route('teamleider.uren.reject', $uren), [
            'teamleider_notitie' => 'Deze notitie is lang genoeg.',
        ])
        ->assertForbidden();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: afgekeurde entries — zorgbeg-kant (hergebruik US-12)
 * ────────────────────────────────────────────────────────────────── */

it('shows the afkeur-reason banner on the zorgbegeleider edit page', function () {
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create(['afkeur_reden' => 'Starttijd klopt niet.']);

    $response = $this->actingAs($this->zorg)->get(route('uren.edit', $uren));

    $response->assertOk();
    $response->assertSee('Teamleider-notitie bij afkeur');
    $response->assertSee('Starttijd klopt niet.');
});

it('allows the zorgbegeleider to resubmit an afgekeurde entry', function () {
    Notification::fake();
    $uren = Urenregistratie::factory()
        ->afgekeurd()
        ->for($this->zorg, 'user')
        ->for($this->client)
        ->create([
            'afkeur_reden' => 'Starttijd klopt niet.',
            'starttijd' => '09:00:00',
            'eindtijd' => '12:00:00',
            'uren' => 3.0,
        ]);

    $this->actingAs($this->zorg)->post(route('uren.resubmit', $uren), [
        'client_id' => $this->client->id,
        'datum' => now()->subDay()->format('Y-m-d'),
        'starttijd' => '10:00',
        'eindtijd' => '13:30',
        'notities' => 'Gecorrigeerd.',
    ])->assertRedirect();

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Ingediend);
    expect($uren->afkeur_reden)->toBeNull();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: Middleware + policy-defense
 * ────────────────────────────────────────────────────────────────── */

it('redirects guests from the teamleider uren index to login', function () {
    $response = $this->get(route('teamleider.uren.index'));
    $response->assertRedirect(route('login'));
});

it('zorgbegeleider POST goedkeuren → 403 via middleware', function () {
    $uren = makeIngediend($this->zorg, $this->client);

    $response = $this->actingAs($this->zorg)
        ->post(route('teamleider.uren.approve', $uren));

    $response->assertForbidden();
    expect($uren->refresh()->status)->toBe(UrenStatus::Ingediend);
});

/* ──────────────────────────────────────────────────────────────────
 * Service-unit + payload
 * ────────────────────────────────────────────────────────────────── */

it('service approve logs beoordeeld_op and goedgekeurd_door', function () {
    $uren = makeIngediend($this->zorg, $this->client);
    app(UrenregistratieService::class)->approve($uren, $this->teamleider);

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Goedgekeurd);
    expect($uren->goedgekeurd_door_user_id)->toBe($this->teamleider->id);
    expect($uren->beoordeeld_op)->not->toBeNull();
});

it('service reject stores afkeur_reden and fires notification', function () {
    Notification::fake();
    $uren = makeIngediend($this->zorg, $this->client);

    app(UrenregistratieService::class)->reject($uren, $this->teamleider, 'Check je rooster — starttijd is niet 09:00.');

    $uren->refresh();
    expect($uren->status)->toBe(UrenStatus::Afgekeurd);
    expect($uren->afkeur_reden)->toBe('Check je rooster — starttijd is niet 09:00.');
    Notification::assertSentTo($this->zorg, UrenAfgekeurdNotification::class);
});

it('goedgekeurd notification payload contains type and teamleider-info', function () {
    $uren = makeIngediend($this->zorg, $this->client);
    $this->actingAs($this->teamleider)->post(route('teamleider.uren.approve', $uren));

    $note = \DB::table('notifications')->where('notifiable_id', $this->zorg->id)->first();
    $data = json_decode($note->data, true);

    expect($data['type'])->toBe('uren_goedgekeurd');
    expect($data['uren_id'])->toBe($uren->id);
    expect($data['goedgekeurd_door_user_id'])->toBe($this->teamleider->id);
    expect($data['goedgekeurd_door_name'])->toBe('Fatima El Amrani');
});

it('afgekeurd notification payload contains afkeur_reden', function () {
    $uren = makeIngediend($this->zorg, $this->client);
    $this->actingAs($this->teamleider)->post(route('teamleider.uren.reject', $uren), [
        'teamleider_notitie' => 'Geen client-koppeling — controleer.',
    ]);

    $note = \DB::table('notifications')
        ->where('notifiable_id', $this->zorg->id)
        ->where('type', UrenAfgekeurdNotification::class)
        ->first();
    $data = json_decode($note->data, true);

    expect($data['type'])->toBe('uren_afgekeurd');
    expect($data['afkeur_reden'])->toBe('Geen client-koppeling — controleer.');
});

/* ──────────────────────────────────────────────────────────────────
 * UI regressie
 * ────────────────────────────────────────────────────────────────── */

it('sidebar shows Uren beoordelen link for teamleider only', function () {
    $responseTL = $this->actingAs($this->teamleider)->get(route('teamleider.dashboard'));
    $responseTL->assertSee('Uren beoordelen');

    $responseZorg = $this->actingAs($this->zorg)->get(route('dashboard'));
    $responseZorg->assertDontSee('Uren beoordelen');
});
