<?php

/*
 * US-15 — Wachtwoord vergeten & resetten via e-maillink
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: Bestaand e-mailadres → WachtwoordResetNotification verstuurd + flash
 *  - AC-2: Niet-bestaand e-mailadres → zelfde flash, GEEN e-mail verstuurd (enumeration protection)
 *  - AC-3: Geldig token + wachtwoord ≥8 + confirmed → bcrypt-hash in users.password
 *  - AC-4: Ongeldig/verlopen token → reset faalt, oud wachtwoord blijft actief
 *  - AC-5: Succesvolle reset → auto-login + redirect naar rol-specifiek dashboard
 */

use App\Models\Team;
use App\Models\User;
use App\Notifications\WachtwoordResetNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
});

/* ──────────────────────────────────────────────────────────────────
 * AC-1 + AC-2: forgot-flow + enumeration protection
 * ────────────────────────────────────────────────────────────────── */

it('renders the forgot-password form', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
    $response->assertSee('Wachtwoord vergeten');
    $response->assertSee('name="email"', false);
});

it('sends a reset notification when the email exists', function () {
    Notification::fake();
    $user = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'email' => 'jeroen@nexora.test',
    ]);

    $response = $this->post(route('password.email'), ['email' => 'jeroen@nexora.test']);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    Notification::assertSentTo($user, WachtwoordResetNotification::class);
});

it('returns the same flash for a non-existent email (enumeration protection)', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'niemand@nexora.test']);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    // Geen user, dus geen notification:
    Notification::assertNothingSent();
});

it('flashes the same UX message whether or not the email exists', function () {
    Notification::fake();
    User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'email' => 'jeroen@nexora.test',
    ]);

    $existing = $this->post(route('password.email'), ['email' => 'jeroen@nexora.test']);
    $missing = $this->post(route('password.email'), ['email' => 'niemand@nexora.test']);

    expect($existing->getSession()->get('status'))->toBe($missing->getSession()->get('status'));
});

it('validates that email is required and in valid format', function () {
    $response = $this->post(route('password.email'), ['email' => '']);
    $response->assertSessionHasErrors('email');

    $response = $this->post(route('password.email'), ['email' => 'geen-email']);
    $response->assertSessionHasErrors('email');
});

/* ──────────────────────────────────────────────────────────────────
 * Reset-form + AC-3: wachtwoord update + bcrypt
 * ────────────────────────────────────────────────────────────────── */

it('renders the reset-password form with token prefilled', function () {
    $response = $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'test@nexora.test']));

    $response->assertOk();
    $response->assertSee('Wachtwoord herstellen');
    $response->assertSee('value="abc123"', false);
    $response->assertSee('value="test@nexora.test"', false);
});

it('resets the password with a valid token and stores a bcrypt hash', function () {
    $user = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'email' => 'jeroen@nexora.test',
        'password' => Hash::make('oudwachtwoord'),
    ]);

    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'jeroen@nexora.test',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertRedirect(route('dashboard'));
    $user->refresh();
    expect(Hash::check('nieuw-wachtwoord-sterk', $user->password))->toBeTrue();
    // Geen plaintext:
    expect($user->password)->not->toBe('nieuw-wachtwoord-sterk');
    expect(str_starts_with($user->password, '$2y$'))->toBeTrue();
});

it('validates password min length 8', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'kort',
        'password_confirmation' => 'kort',
    ]);

    $response->assertSessionHasErrors('password');
});

it('validates password confirmation must match', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'anders-ingevoerd',
    ]);

    $response->assertSessionHasErrors('password');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: ongeldig / verlopen token
 * ────────────────────────────────────────────────────────────────── */

it('rejects a reset with an invalid token and keeps the old password', function () {
    $user = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'email' => 'jeroen@nexora.test',
        'password' => Hash::make('oudwachtwoord'),
    ]);

    $response = $this->post(route('password.update'), [
        'token' => 'dit-is-geen-geldig-token',
        'email' => 'jeroen@nexora.test',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertSessionHasErrors('email');
    $user->refresh();
    expect(Hash::check('oudwachtwoord', $user->password))->toBeTrue();
    expect(Hash::check('nieuw-wachtwoord-sterk', $user->password))->toBeFalse();
});

it('rejects a reset with a non-existent email', function () {
    $response = $this->post(route('password.update'), [
        'token' => 'fake',
        'email' => 'niet-bestaand@nexora.test',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertSessionHasErrors('email');
});

it('rejects reusing a token after successful reset', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
    $token = Password::createToken($user);

    // Eerste reset slaagt.
    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'eerste-reset-sterk',
        'password_confirmation' => 'eerste-reset-sterk',
    ])->assertRedirect();

    auth()->logout();

    // Tweede poging met hetzelfde token faalt.
    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'tweede-poging-sterk',
        'password_confirmation' => 'tweede-poging-sterk',
    ]);

    $response->assertSessionHasErrors('email');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: auto-login + rol-specifieke redirect
 * ────────────────────────────────────────────────────────────────── */

it('logs in and redirects zorgbegeleider to /dashboard after successful reset', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);
    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('logs in and redirects teamleider to /teamleider/dashboard after successful reset', function () {
    $user = User::factory()->teamleider()->create(['team_id' => $this->team->id]);
    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertRedirect(route('teamleider.dashboard'));
});

/* ──────────────────────────────────────────────────────────────────
 * Guest-middleware regressie
 * ────────────────────────────────────────────────────────────────── */

it('redirects authenticated users away from the forgot-password form', function () {
    $user = User::factory()->zorgbegeleider()->create(['team_id' => $this->team->id]);

    $response = $this->actingAs($user)->get(route('password.request'));

    // Guest-middleware stuurt authenticated users naar dashboard.
    $response->assertRedirect();
});

/* ──────────────────────────────────────────────────────────────────
 * UI
 * ────────────────────────────────────────────────────────────────── */

it('shows a Wachtwoord vergeten link on the login page', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('Wachtwoord vergeten?');
    $response->assertSee(route('password.request'), false);
});
