<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
});

/*
 * US-01 Acceptatiecriterium 1:
 * Actieve zorgbegeleider met juiste credentials -> redirect /dashboard, Auth::check() = true
 */
it('logs in an active zorgbegeleider and redirects to /dashboard', function () {
    $user = User::factory()->zorgbegeleider()->create([
        'email' => 'zorg@example.test',
        'password' => 'geheim12',
        'team_id' => $this->team->id,
    ]);

    $response = $this->post('/login', [
        'email' => 'zorg@example.test',
        'password' => 'geheim12',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

/*
 * US-01 Acceptatiecriterium 2:
 * Actieve teamleider met juiste credentials -> redirect /teamleider/dashboard
 */
it('logs in an active teamleider and redirects to /teamleider/dashboard', function () {
    User::factory()->teamleider()->create([
        'email' => 'lead@example.test',
        'password' => 'geheim12',
        'team_id' => $this->team->id,
    ]);

    $response = $this->post('/login', [
        'email' => 'lead@example.test',
        'password' => 'geheim12',
    ]);

    $response->assertRedirect(route('teamleider.dashboard'));
    expect(auth()->check())->toBeTrue();
});

/*
 * US-01 Acceptatiecriterium 3:
 * Fout wachtwoord -> blijft op /login met foutmelding, sessie NIET geauthenticeerd
 */
it('rejects login with wrong password and keeps user unauthenticated', function () {
    User::factory()->zorgbegeleider()->create([
        'email' => 'zorg@example.test',
        'password' => 'correct-pw',
        'team_id' => $this->team->id,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'zorg@example.test',
        'password' => 'fout-pw',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

/*
 * US-01 Privacy bullet 4: User enumeration protection.
 * Onbekende email geeft IDENTIEKE foutmelding als fout wachtwoord.
 */
it('returns the same error message for unknown email and wrong password', function () {
    User::factory()->zorgbegeleider()->create([
        'email' => 'bekend@example.test',
        'password' => 'correct-pw',
        'team_id' => $this->team->id,
    ]);

    $wrongPassword = $this->from('/login')->post('/login', [
        'email' => 'bekend@example.test',
        'password' => 'fout-pw',
    ]);

    auth()->logout();
    session()->flush();

    $unknownEmail = $this->from('/login')->post('/login', [
        'email' => 'niet-bestaand@example.test',
        'password' => 'wat-dan-ook',
    ]);

    expect($wrongPassword->exception?->getMessage())
        ->toBe($unknownEmail->exception?->getMessage());
});

/*
 * US-01 Acceptatiecriterium 4:
 * Account met is_active = false -> login geweigerd met deactivatie-melding
 */
it('rejects login for a deactivated account with specific message', function () {
    User::factory()->zorgbegeleider()->inactive()->create([
        'email' => 'inactief@example.test',
        'password' => 'geheim12',
        'team_id' => $this->team->id,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'inactief@example.test',
        'password' => 'geheim12',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    expect(session('errors')->default->first('email'))
        ->toContain('gedeactiveerd');
    expect(auth()->check())->toBeFalse();
});

/*
 * US-01 Acceptatiecriterium 5:
 * Uitloggen -> session()->invalidate(), CSRF geregenereerd, redirect /login
 */
it('logs out an authenticated user and invalidates the session', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $this->actingAs($user);
    expect(auth()->check())->toBeTrue();

    $tokenBefore = session()->token();

    $response = $this->post('/logout');

    $response->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();
    expect(session()->token())->not->toBe($tokenBefore);
});

/*
 * US-01 Privacy bullet 3: Session fixation protection.
 * Sessie-ID wordt geregenereerd na succesvolle login.
 */
it('regenerates the session id after successful login', function () {
    User::factory()->zorgbegeleider()->create([
        'email' => 'zorg@example.test',
        'password' => 'geheim12',
        'team_id' => $this->team->id,
    ]);

    $this->startSession();
    $sessionIdBefore = session()->getId();

    $this->post('/login', [
        'email' => 'zorg@example.test',
        'password' => 'geheim12',
    ]);

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

/*
 * US-01 Omschrijving item 1: Login-scherm toont formulier.
 */
it('shows the login form with required fields', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('E-mailadres');
    $response->assertSee('Wachtwoord');
    $response->assertSee('Wachtwoord vergeten');
    $response->assertSee('Inloggen');
});

/*
 * Validatie: lege velden geven fouten.
 */
it('requires email and password', function () {
    $response = $this->from('/login')->post('/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email', 'password']);
});

/*
 * Ingelogde user op /login wordt direct doorgestuurd (geen dubbele login UI).
 */
it('redirects already-authenticated users away from the login page', function () {
    $user = User::factory()->teamleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('teamleider.dashboard'));
});
