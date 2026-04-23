<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->team = Team::factory()->create(['name' => 'Team Rotterdam-Noord']);
    $this->teamleider = User::factory()->teamleider()->create(['team_id' => $this->team->id]);
    $this->zorg = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
});

/*
 * US-03 AC-1 (formulier zichtbaar): /team/create rendert het form met alle velden.
 */
it('renders the create form with all required fields for teamleider', function () {
    $response = $this->actingAs($this->teamleider)->get(route('team.create'));

    $response->assertOk();
    $response->assertSee('Medewerker toevoegen');
    $response->assertSee('name="voornaam"', false);
    $response->assertSee('name="achternaam"', false);
    $response->assertSee('name="email"', false);
    $response->assertSee('name="role"', false);
    $response->assertSee('name="dienstverband"', false);
    $response->assertSee('name="password"', false);
    $response->assertSee('name="password_confirmation"', false);
});

/*
 * US-03 AC-3 (happy path): Teamleider maakt nieuwe zorgbegeleider aan.
 * Wachtwoord wordt bcrypt-gehasht, is_active=true, team_id = auth team.
 */
it('creates a new zorgbegeleider in the same team with hashed password', function () {
    $response = $this->actingAs($this->teamleider)->post(route('team.store'), [
        'voornaam' => 'Lisa',
        'achternaam' => 'Van Dijk',
        'email' => 'lisa@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertRedirect(route('team.index'));
    $response->assertSessionHas('success', 'Medewerker aangemaakt.');

    $new = User::where('email', 'lisa@nexora.test')->firstOrFail();
    expect($new->name)->toBe('Lisa Van Dijk');
    expect($new->role)->toBe(User::ROLE_ZORGBEGELEIDER);
    expect($new->is_active)->toBeTrue();
    expect($new->team_id)->toBe($this->teamleider->team_id);
    expect($new->dienstverband)->toBe('intern');
    expect(Hash::check('Geheim123', $new->password))->toBeTrue();
    expect($new->password)->not->toBe('Geheim123');
});

it('can also create a new teamleider (role whitelist allows both)', function () {
    $this->actingAs($this->teamleider)->post(route('team.store'), [
        'voornaam' => 'Karim',
        'achternaam' => 'El Ouali',
        'email' => 'karim@nexora.test',
        'role' => User::ROLE_TEAMLEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $new = User::where('email', 'karim@nexora.test')->firstOrFail();
    expect($new->role)->toBe(User::ROLE_TEAMLEIDER);
});

/*
 * US-03 AC-2 (validatie): email uniek.
 */
it('rejects a duplicate email', function () {
    User::factory()->zorgbegeleider()->create(['email' => 'dubbel@nexora.test']);

    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'dubbel@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertRedirect(route('team.create'));
    $response->assertSessionHasErrors('email');
});

it('rejects empty required fields with Dutch messages', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), []);

    $response->assertSessionHasErrors(['voornaam', 'achternaam', 'email', 'role', 'dienstverband', 'password']);
});

it('rejects password shorter than 8 characters', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'tom@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'kort',
        'password_confirmation' => 'kort',
    ]);

    $response->assertSessionHasErrors('password');
});

it('rejects mismatched password confirmation', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'tom@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Andersgeheim',
    ]);

    $response->assertSessionHasErrors('password');
});

/*
 * US-03 AC-2 + Privacy bullet 2: role whitelist — geen 'admin' via form (privilege escalation).
 */
it('rejects a role outside the whitelist (privilege escalation)', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'tom@nexora.test',
        'role' => 'admin',
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertSessionHasErrors('role');
    expect(User::where('email', 'tom@nexora.test')->exists())->toBeFalse();
});

it('rejects a dienstverband outside the whitelist', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'tom@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'freelance',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertSessionHasErrors('dienstverband');
});

/*
 * US-03 AC-3 kern: nieuwe user krijgt altijd team_id van de aanmakende teamleider.
 * Zelfs als een attacker een hidden 'team_id' input zou meesturen, wordt deze
 * genegeerd (validatedPayload gebruikt auth()->user()->team_id).
 */
it('always assigns team_id from the authenticated teamleider (ignores hidden input)', function () {
    $otherTeam = Team::factory()->create();

    $this->actingAs($this->teamleider)->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'tom@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
        'team_id' => $otherTeam->id, // aanvalspoging
        'is_active' => false,         // aanvalspoging
    ]);

    $new = User::where('email', 'tom@nexora.test')->firstOrFail();
    expect($new->team_id)->toBe($this->teamleider->team_id);
    expect($new->team_id)->not->toBe($otherTeam->id);
    expect($new->is_active)->toBeTrue();
});

/*
 * Autorisatie: alleen teamleider krijgt toegang.
 */
it('denies zorgbegeleider access to /team/create (403)', function () {
    $response = $this->actingAs($this->zorg)->get(route('team.create'));

    $response->assertStatus(403);
});

it('denies zorgbegeleider POST /team (403)', function () {
    $response = $this->actingAs($this->zorg)->post(route('team.store'), [
        'voornaam' => 'X',
        'achternaam' => 'Y',
        'email' => 'x@y.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertStatus(403);
    expect(User::where('email', 'x@y.test')->exists())->toBeFalse();
});

it('redirects guest from /team/create to /login', function () {
    $response = $this->get(route('team.create'));

    $response->assertRedirect(route('login'));
});

/*
 * Privacy bullet 1: wachtwoord komt NIET voor in de response body of in logs.
 */
it('does not echo the password back in the response body on validation error', function () {
    $response = $this->actingAs($this->teamleider)->from(route('team.create'))->post(route('team.store'), [
        'voornaam' => 'Tom',
        'achternaam' => 'Jansen',
        'email' => 'invalid',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response->assertSessionDoesntHaveErrors('password');
    // Follow redirect and check password is niet in gerenderde HTML old()-input.
    $followup = $this->actingAs($this->teamleider)->get(route('team.create'));
    $followup->assertDontSee('Geheim123');
});

/*
 * Team index na create: flash success + nieuwe user in tabel zichtbaar.
 */
it('shows the created user on the team index with a success flash', function () {
    $this->actingAs($this->teamleider)->post(route('team.store'), [
        'voornaam' => 'Lisa',
        'achternaam' => 'Van Dijk',
        'email' => 'lisa@nexora.test',
        'role' => User::ROLE_ZORGBEGELEIDER,
        'dienstverband' => 'intern',
        'password' => 'Geheim123',
        'password_confirmation' => 'Geheim123',
    ]);

    $response = $this->actingAs($this->teamleider)->get(route('team.index'));

    $response->assertOk();
    $response->assertSee('Lisa Van Dijk');
    $response->assertSee('lisa@nexora.test');
});
