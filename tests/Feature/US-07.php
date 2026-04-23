<?php

/*
 * US-07 — Cliënt aanmaken met persoonsgegevens
 *
 * Dekt alle 5 acceptatiecriteria + Privacy/Security bullets:
 *  - AC-1: Teamleider /clients/create toont leeg formulier met alle secties
 *  - AC-2: Zorgbegeleider /clients/create -> 403 (ClientPolicy@create)
 *  - AC-3: Validatiefouten + old() invoer behouden
 *  - AC-4: BSN uniek (9 cijfers)
 *  - AC-5: Valide submit -> nieuwe rij + created_by_user_id + redirect /clients/{id} + flash
 */

use App\Models\Client;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam']);
    $this->otherTeam = Team::factory()->create(['name' => 'Team Amsterdam']);

    $this->teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima El Amrani',
    ]);

    $this->zorg = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
    ]);
});

function validClientPayload(array $overrides = []): array
{
    return array_merge([
        'voornaam' => 'Sanne',
        'achternaam' => 'de Wit',
        'email' => 'sanne@client.test',
        'telefoon' => '0612345678',
        'bsn' => '123456789',
        'geboortedatum' => '1980-05-17',
        'status' => Client::STATUS_ACTIEF,
        'care_type' => Client::CARE_WMO,
    ], $overrides);
}

/*
 * AC-1: Teamleider opent /clients/create → ziet leeg formulier met alle secties.
 */
it('renders the create form with all sections for a teamleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('clients.create'));

    $response->assertOk();
    $response->assertSee('Cliënt toevoegen');
    $response->assertSee('Persoonlijk');
    $response->assertSee('Contact');
    $response->assertSee('Zorg');
    $response->assertSee('name="voornaam"', false);
    $response->assertSee('name="achternaam"', false);
    $response->assertSee('name="bsn"', false);
    $response->assertSee('name="geboortedatum"', false);
    $response->assertSee('name="email"', false);
    $response->assertSee('name="telefoon"', false);
    $response->assertSee('name="status"', false);
    $response->assertSee('name="care_type"', false);
});

/*
 * AC-2: Zorgbegeleider opent /clients/create → 403 via ClientPolicy@create.
 */
it('denies zorgbegeleider access to /clients/create (403)', function () {
    $response = $this->actingAs($this->zorg)->get(route('clients.create'));

    $response->assertStatus(403);
});

it('denies zorgbegeleider POST /clients (403) and stores nothing', function () {
    $response = $this->actingAs($this->zorg)->post(route('clients.store'), validClientPayload());

    $response->assertStatus(403);
    expect(Client::count())->toBe(0);
});

it('redirects guest from /clients/create to /login', function () {
    $response = $this->get(route('clients.create'));

    $response->assertRedirect(route('login'));
});

/*
 * AC-3: Formulier zonder voornaam → blijft op /clients/create met foutmelding
 * en oude invoer behouden (old()).
 */
it('rejects submission without voornaam and preserves other input via old()', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['voornaam' => '']));

    $response->assertRedirect(route('clients.create'));
    $response->assertSessionHasErrors('voornaam');

    // old() zou email en achternaam moeten bewaren
    expect(session()->get('_old_input.achternaam'))->toBe('de Wit');
    expect(session()->get('_old_input.email'))->toBe('sanne@client.test');
    expect(Client::count())->toBe(0);
});

it('rejects empty required fields with Dutch error messages', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), []);

    $response->assertSessionHasErrors([
        'voornaam', 'achternaam', 'status', 'care_type',
    ]);
});

/*
 * AC-4: BSN dat al bestaat → validatiefout "Dit BSN is al gekoppeld".
 */
it('rejects a BSN that already exists in the clients table', function () {
    Client::factory()->create([
        'team_id' => $this->team->id,
        'bsn' => '555555555',
    ]);

    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['bsn' => '555555555']));

    $response->assertSessionHasErrors('bsn');
    expect(session('errors')->default->first('bsn'))
        ->toContain('al gekoppeld');
    expect(Client::count())->toBe(1); // alleen de factory-rij
});

it('rejects a BSN that is not exactly 9 digits', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['bsn' => '12345']));

    $response->assertSessionHasErrors('bsn');
});

it('rejects a BSN containing non-digits', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['bsn' => 'abc123def']));

    $response->assertSessionHasErrors('bsn');
});

it('accepts a nullable BSN (optioneel veld)', function () {
    $response = $this->actingAs($this->teamleider)->post(
        route('clients.store'),
        validClientPayload(['bsn' => null])
    );

    $response->assertStatus(302);
    expect(Client::where('voornaam', 'Sanne')->first()->bsn)->toBeNull();
});

/*
 * Geboortedatum moet in het verleden liggen (US-07 AC-3).
 */
it('rejects a geboortedatum in the future', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload([
            'geboortedatum' => now()->addYear()->format('Y-m-d'),
        ]));

    $response->assertSessionHasErrors('geboortedatum');
});

/*
 * AC-5: Geldige invoer → nieuwe rij met created_by_user_id + team_id +
 * redirect /clients/{id} + flash success.
 */
it('creates a client with server-side audit fields and redirects to show with flash', function () {
    $response = $this->actingAs($this->teamleider)->post(route('clients.store'), validClientPayload());

    $client = Client::where('bsn', '123456789')->firstOrFail();

    $response->assertRedirect(route('clients.show', $client));
    $response->assertSessionHas('success', 'Cliënt aangemaakt.');

    expect($client->voornaam)->toBe('Sanne');
    expect($client->achternaam)->toBe('de Wit');
    expect($client->email)->toBe('sanne@client.test');
    expect($client->status)->toBe(Client::STATUS_ACTIEF);
    expect($client->care_type)->toBe(Client::CARE_WMO);
    expect($client->team_id)->toBe($this->team->id);
    expect($client->created_by_user_id)->toBe($this->teamleider->id);
    expect($client->geboortedatum->format('Y-m-d'))->toBe('1980-05-17');
});

/*
 * Mass-assignment protection: hidden hidden inputs worden genegeerd.
 */
it('ignores a hidden team_id input and uses authenticated users team_id', function () {
    $this->actingAs($this->teamleider)->post(route('clients.store'), validClientPayload([
        'team_id' => $this->otherTeam->id,  // poging
        'created_by_user_id' => 99999,      // poging
    ]));

    $client = Client::where('bsn', '123456789')->firstOrFail();
    expect($client->team_id)->toBe($this->teamleider->team_id);
    expect($client->team_id)->not->toBe($this->otherTeam->id);
    expect($client->created_by_user_id)->toBe($this->teamleider->id);
});

/*
 * Invalid email format.
 */
it('rejects an invalid email format', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['email' => 'not-an-email']));

    $response->assertSessionHasErrors('email');
});

it('accepts a nullable email', function () {
    $this->actingAs($this->teamleider)->post(route('clients.store'), validClientPayload(['email' => null]))
        ->assertStatus(302);

    expect(Client::where('bsn', '123456789')->first()->email)->toBeNull();
});

/*
 * Status/care_type whitelist.
 */
it('rejects an invalid status value', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['status' => 'ongeldig']));

    $response->assertSessionHasErrors('status');
});

it('rejects an invalid care_type value', function () {
    $response = $this->actingAs($this->teamleider)
        ->from(route('clients.create'))
        ->post(route('clients.store'), validClientPayload(['care_type' => 'particulier']));

    $response->assertSessionHasErrors('care_type');
});

/*
 * Show page toegankelijk voor teamleider (redirect-target AC-5 werkt).
 */
it('renders the show page after creation', function () {
    $this->actingAs($this->teamleider)->post(route('clients.store'), validClientPayload());
    $client = Client::where('bsn', '123456789')->firstOrFail();

    $response = $this->actingAs($this->teamleider)->get(route('clients.show', $client));

    $response->assertOk();
    $response->assertSee('Sanne de Wit');
    $response->assertSee('sanne@client.test');
    $response->assertSee('WMO');
});

/*
 * Cross-team scoping (US-02 regressie): teamleider Amsterdam ziet geen
 * Rotterdam-cliënt show-page.
 */
it('denies teamleider from another team to view a foreign client (US-02 regressie)', function () {
    $rotterdamClient = Client::factory()->create(['team_id' => $this->team->id]);
    $amsterdamLeader = User::factory()->teamleider()->create(['team_id' => $this->otherTeam->id]);

    $response = $this->actingAs($amsterdamLeader)->get(route('clients.show', $rotterdamClient));

    $response->assertStatus(403);
});

/*
 * Index toont nieuw aangemaakte cliënt (regressie op US-02 scopedForUser).
 */
it('shows the newly created client on the index for the same-team teamleider', function () {
    $this->actingAs($this->teamleider)->post(route('clients.store'), validClientPayload());

    $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

    $response->assertOk();
    $response->assertSee('Sanne de Wit');
    $response->assertSee('WMO');
    // Index toont bewust GEEN BSN (AVG-dataminimalisatie — bsn alleen op show).
    $response->assertDontSee('123456789');
});

it('does not leak clients across teams on the index', function () {
    Client::factory()->create([
        'team_id' => $this->otherTeam->id,
        'voornaam' => 'Geheim',
        'achternaam' => 'Amsterdam',
    ]);

    $response = $this->actingAs($this->teamleider)->get(route('clients.index'));

    $response->assertOk();
    $response->assertDontSee('Geheim Amsterdam');
});
