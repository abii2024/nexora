<?php

/*
 * US-16 — Profielbeheer (eigen gegevens + wachtwoord wijzigen)
 *
 * Dekt alle 5 acceptatiecriteria:
 *  - AC-1: /profiel toont huidige name + email pre-filled
 *  - AC-2: Naam / e-mail wijzigen → opgeslagen, password onveranderd
 *  - AC-3: Password zonder correct current_password → 422, oud password blijft
 *  - AC-4: Request met role=teamleider of is_active=false → genegeerd
 *  - AC-5: Succesvolle password-wijziging → andere sessies geïnvalideerd
 */

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'password' => Hash::make('oudwachtwoord-sterk'),
    ]);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-1: profielpagina
 * ────────────────────────────────────────────────────────────────── */

it('renders profile page with current name and email', function () {
    $response = $this->actingAs($this->user)->get(route('profiel.show'));

    $response->assertOk();
    $response->assertSee('Profiel');
    $response->assertSee('value="Jeroen Bakker"', false);
    $response->assertSee('value="jeroen@nexora.test"', false);
});

it('redirects guests from the profile page to login', function () {
    $response = $this->get(route('profiel.show'));

    $response->assertRedirect(route('login'));
});

it('shows the profile link in the sidebar for authenticated users', function () {
    $response = $this->actingAs($this->user)->get(route('profiel.show'));

    $response->assertSee(route('profiel.show'), false);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-2: naam / email updaten zonder password-wijziging
 * ────────────────────────────────────────────────────────────────── */

it('updates only the name without touching password', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakkerman',
        'email' => 'jeroen@nexora.test',
    ]);

    $response->assertRedirect(route('profiel.show'));
    $this->user->refresh();
    expect($this->user->name)->toBe('Jeroen Bakkerman');
    expect(Hash::check('oudwachtwoord-sterk', $this->user->password))->toBeTrue();
});

it('updates only the email without touching password', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'nieuw@nexora.test',
    ]);

    $response->assertRedirect(route('profiel.show'));
    $this->user->refresh();
    expect($this->user->email)->toBe('nieuw@nexora.test');
    expect(Hash::check('oudwachtwoord-sterk', $this->user->password))->toBeTrue();
});

it('accepts unchanged email (own email via unique-ignore)', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
    ]);

    $response->assertRedirect(route('profiel.show'));
    $response->assertSessionDoesntHaveErrors('email');
});

it('rejects email change to another users email', function () {
    User::factory()->zorgbegeleider()->create([
        'team_id' => $this->team->id,
        'email' => 'bezet@nexora.test',
    ]);

    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => 'Jeroen Bakker',
            'email' => 'bezet@nexora.test',
        ]);

    $response->assertSessionHasErrors('email');
    $this->user->refresh();
    expect($this->user->email)->toBe('jeroen@nexora.test');
});

it('validates name and email are required', function () {
    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => '',
            'email' => '',
        ]);

    $response->assertSessionHasErrors(['name', 'email']);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-3: password met current_password-verificatie
 * ────────────────────────────────────────────────────────────────── */

it('rejects password change when current_password is missing', function () {
    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => 'Jeroen Bakker',
            'email' => 'jeroen@nexora.test',
            'password' => 'nieuw-wachtwoord-sterk',
            'password_confirmation' => 'nieuw-wachtwoord-sterk',
        ]);

    $response->assertSessionHasErrors('current_password');
    $this->user->refresh();
    expect(Hash::check('oudwachtwoord-sterk', $this->user->password))->toBeTrue();
});

it('rejects password change when current_password is wrong', function () {
    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => 'Jeroen Bakker',
            'email' => 'jeroen@nexora.test',
            'current_password' => 'onjuist-wachtwoord',
            'password' => 'nieuw-wachtwoord-sterk',
            'password_confirmation' => 'nieuw-wachtwoord-sterk',
        ]);

    $response->assertSessionHasErrors('current_password');
    $this->user->refresh();
    expect(Hash::check('oudwachtwoord-sterk', $this->user->password))->toBeTrue();
});

it('accepts password change with correct current_password', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'current_password' => 'oudwachtwoord-sterk',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertRedirect(route('profiel.show'));
    $this->user->refresh();
    expect(Hash::check('nieuw-wachtwoord-sterk', $this->user->password))->toBeTrue();
    expect(Hash::check('oudwachtwoord-sterk', $this->user->password))->toBeFalse();
});

it('validates password min length 8', function () {
    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => 'Jeroen Bakker',
            'email' => 'jeroen@nexora.test',
            'current_password' => 'oudwachtwoord-sterk',
            'password' => 'kort',
            'password_confirmation' => 'kort',
        ]);

    $response->assertSessionHasErrors('password');
});

it('validates password confirmation must match', function () {
    $response = $this->actingAs($this->user)
        ->from(route('profiel.show'))
        ->patch(route('profiel.update'), [
            'name' => 'Jeroen Bakker',
            'email' => 'jeroen@nexora.test',
            'current_password' => 'oudwachtwoord-sterk',
            'password' => 'nieuw-wachtwoord-sterk',
            'password_confirmation' => 'anders-ingevoerd',
        ]);

    $response->assertSessionHasErrors('password');
});

/* ──────────────────────────────────────────────────────────────────
 * AC-4: mass-assignment probe — role / is_active / team_id genegeerd
 * ────────────────────────────────────────────────────────────────── */

it('ignores role in request body', function () {
    $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'role' => User::ROLE_TEAMLEIDER,
    ]);

    $this->user->refresh();
    expect($this->user->role)->toBe(User::ROLE_ZORGBEGELEIDER);
});

it('ignores is_active in request body', function () {
    $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'is_active' => false,
    ]);

    $this->user->refresh();
    expect($this->user->is_active)->toBeTrue();
});

it('ignores team_id in request body', function () {
    $otherTeam = Team::factory()->create();

    $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'team_id' => $otherTeam->id,
    ]);

    $this->user->refresh();
    expect($this->user->team_id)->toBe($this->team->id);
});

/* ──────────────────────────────────────────────────────────────────
 * AC-5: andere sessies uitgelogd na password-change
 * ────────────────────────────────────────────────────────────────── */

it('rotates password-hash so AuthenticateSession invalidates other sessions', function () {
    // Ingelogd-zijn is nodig voor de current_password-validatie.
    $oldHash = $this->user->password;

    $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'current_password' => 'oudwachtwoord-sterk',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ])->assertRedirect();

    $this->user->refresh();
    // De hash is daadwerkelijk geroteerd — AuthenticateSession gebruikt dit
    // om cookies van andere sessies ongeldig te maken bij de volgende request.
    expect($this->user->password)->not->toBe($oldHash);
    expect(Hash::check('nieuw-wachtwoord-sterk', $this->user->password))->toBeTrue();
});

it('shows success flash with devices-logout hint when password changed', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Jeroen Bakker',
        'email' => 'jeroen@nexora.test',
        'current_password' => 'oudwachtwoord-sterk',
        'password' => 'nieuw-wachtwoord-sterk',
        'password_confirmation' => 'nieuw-wachtwoord-sterk',
    ]);

    $response->assertRedirect();
    expect(session('success'))->toContain('uitgelogd op andere apparaten');
});

it('shows success flash without devices-logout hint when only profile changed', function () {
    $response = $this->actingAs($this->user)->patch(route('profiel.update'), [
        'name' => 'Nieuwe Naam',
        'email' => 'jeroen@nexora.test',
    ]);

    $response->assertRedirect();
    expect(session('success'))->not->toContain('uitgelogd op andere apparaten');
});

/* ──────────────────────────────────────────────────────────────────
 * Guest + regressie
 * ────────────────────────────────────────────────────────────────── */

it('rejects PATCH /profiel for guests', function () {
    $response = $this->patch(route('profiel.update'), [
        'name' => 'Hacker',
        'email' => 'hacker@nexora.test',
    ]);

    $response->assertRedirect(route('login'));
});

it('works for teamleider role too', function () {
    $teamleider = User::factory()->teamleider()->create([
        'team_id' => $this->team->id,
        'name' => 'Fatima',
        'email' => 'fatima@nexora.test',
    ]);

    $response = $this->actingAs($teamleider)->get(route('profiel.show'));

    $response->assertOk();
    $response->assertSee('value="Fatima"', false);
});
