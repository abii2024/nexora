# Code-bewijslast — uitgewerkte functionaliteiten per user story

> **Examen-eis:** *Screenshots van code van de uitgewerkte functionaliteiten*
> **Werkproces:** B1-K1-W3 — *Realiseert software*
> **Datum:** 2026-05-12

Dit document bundelt **code-bewijslast** per user story. Voor elke US zijn de kern-files opgesomd, één representatief code-fragment ingesloten (PHP, met syntax-highlighting), en **GitHub-permalinks** opgenomen zodat de examinator de code direct in de live repo kan inspecteren. De volledige tests per US staan in [`tests/Feature/US-NN.php`](https://github.com/abii2024/nexora/tree/main/tests/Feature).

> **Alternatief voor IDE-screenshots:** voor identieke bewijswaarde — maar zonder kwaliteitsverlies door PNG-compressie — zijn de code-fragmenten hieronder *direct uit de bron* opgenomen via GitHub-permalinks naar specifieke regelnummers. Klik op een link en je ziet exact dezelfde code in de productieversie van de repo.

---

## US-01 — Inloggen op Nexora

**Branch:** `feature/authenticatie` · **PR:** [#3](https://github.com/abii2024/nexora/pull/3) · **Tests:** 10 (37 asserts) · **AC-dekking:** 6 AC's groen

**Kern-files:**
- [`app/Http/Controllers/Auth/LoginController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/Auth/LoginController.php) — login-flow met rate-limit + bcrypt + session-regeneratie
- [`app/Http/Requests/Auth/LoginRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Auth/LoginRequest.php) — input-validatie
- [`tests/Feature/US-01.php`](https://github.com/abii2024/nexora/blob/main/tests/Feature/US-01.php) — 10 Pest tests
- [`resources/views/auth/login.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/auth/login.blade.php) — login-formulier

**Kern-snippet — `LoginController::store()`** (rate-limit + enumeration-protection + actief-check + role-redirect):

```php
public function store(LoginRequest $request): RedirectResponse
{
    $this->ensureIsNotRateLimited($request);

    $credentials = $request->only('email', 'password');
    $user = User::where('email', $credentials['email'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        RateLimiter::hit($this->throttleKey($request));
        throw ValidationException::withMessages([
            'email' => __('De ingevoerde gegevens zijn onjuist.'),
        ])->redirectTo(route('login'));
    }

    if (!$user->is_active) {
        throw ValidationException::withMessages([
            'email' => __('Dit account is gedeactiveerd. Neem contact op met je teamleider.'),
        ])->redirectTo(route('login'));
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();
    RateLimiter::clear($this->throttleKey($request));

    return $this->redirectByRole($user);
}
```

**Waarom deze code-keuze:** identieke foutmelding voor *onbekend e-mailadres* en *fout wachtwoord* voorkomt user-enumeration. `Hash::check()` is constant-time tegen timing-attacks. `session()->regenerate()` voorkomt session-fixation. `RateLimiter` blokkeert 5+ pogingen per `email|ip`-combinatie.

---

## US-02 — Rolgebaseerde toegang (Policies + middleware)

**Branch:** `feature/autorisatie` · **PR:** [#4](https://github.com/abii2024/nexora/pull/4) · **Tests:** 26 (54 asserts)

**Kern-files:**
- [`app/Http/Middleware/EnsureTeamleider.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Middleware/EnsureTeamleider.php)
- [`app/Http/Middleware/EnsureZorgbegeleider.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Middleware/EnsureZorgbegeleider.php)
- [`app/Policies/ClientPolicy.php`](https://github.com/abii2024/nexora/blob/main/app/Policies/ClientPolicy.php)
- [`app/Policies/UserPolicy.php`](https://github.com/abii2024/nexora/blob/main/app/Policies/UserPolicy.php)
- [`tests/Feature/US-02.php`](https://github.com/abii2024/nexora/blob/main/tests/Feature/US-02.php)

**Kern-snippet — `EnsureTeamleider` middleware:**

```php
class EnsureTeamleider
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isTeamleider()) {
            abort(403);
        }

        return $next($request);
    }
}
```

**Waarom:** middleware = eerste autorisatie-laag (route-level), Policies = tweede laag (resource-level). **Defense in depth**: een zorgbegeleider die `/team` raakt krijgt 403 vóór de controller draait. In de controller checkt `$this->authorize(...)` of die specifieke gebruiker dit specifieke object mag bewerken.

---

## US-03 — Nieuwe zorgbegeleider aanmaken

**Branch:** `feature/medewerker-aanmaken` · **PR:** [#5](https://github.com/abii2024/nexora/pull/5) · **Tests:** 15 (53 asserts)

**Kern-files:**
- [`app/Http/Controllers/TeamController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/TeamController.php) (`create`/`store`)
- [`app/Http/Requests/Team/StoreTeamMemberRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Team/StoreTeamMemberRequest.php)
- [`app/Services/UserService.php`](https://github.com/abii2024/nexora/blob/main/app/Services/UserService.php) (`createTeamMember`)
- [`tests/Feature/US-03.php`](https://github.com/abii2024/nexora/blob/main/tests/Feature/US-03.php)

**Waarom:** rol kan **niet** via mass-assignment gezet worden (niet in `$fillable`). `UserService::createTeamMember()` is de single-source-of-truth voor accountcreatie — bcrypt-wachtwoord, `is_active=true`, `team_id` van actor, audit-log entry.

---

## US-04 — Medewerkersoverzicht met zoek en filter

**Branch:** `feature/medewerkers-overzicht` · **PR:** [#6](https://github.com/abii2024/nexora/pull/6) · **Tests:** 19 (61 asserts)

**Kern-files:**
- [`app/Http/Controllers/TeamController.php#index`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/TeamController.php) — paginatie + filters
- [`resources/views/team/index.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/team/index.blade.php) — tabel met zoek + filter UI
- [`tests/Feature/US-04.php`](https://github.com/abii2024/nexora/blob/main/tests/Feature/US-04.php)

**Waarom:** filter-whitelist voorkomt SQL-injection (whitelist `['actief','inactief','alle']`). `withQueryString()` zorgt dat paginatie-links de filters behouden — geen state-loss bij doorklikken.

---

## US-05 — Teamlid bewerken (rol + dienstverband)

**Branch:** `feature/teamlid-bewerken` · **PR:** [#7](https://github.com/abii2024/nexora/pull/7) · **Tests:** 16 (54 asserts)

**Kern-files:**
- [`app/Services/UserService.php`](https://github.com/abii2024/nexora/blob/main/app/Services/UserService.php) — `updateWithAudit()` + self-demotion-guard
- [`app/Http/Requests/Team/UpdateTeamMemberRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Team/UpdateTeamMemberRequest.php)
- [`app/Models/UserAuditLog.php`](https://github.com/abii2024/nexora/blob/main/app/Models/UserAuditLog.php) — immutable audit-log model

**Kern-snippet — audit-log voor AVG art. 30:**

```php
public function updateWithAudit(User $member, array $payload, User $changedBy): User
{
    $this->ensureTeamRetainsTeamleider($member, $payload, $changedBy);

    $auditable = ['name', 'email', 'role', 'dienstverband'];

    return DB::transaction(function () use ($member, $payload, $changedBy, $auditable) {
        foreach ($auditable as $field) {
            if (!array_key_exists($field, $payload)) continue;
            $old = $member->getOriginal($field);
            $new = $payload[$field];
            if ((string) $old === (string) $new) continue;

            UserAuditLog::create([
                'user_id' => $member->id,
                'changed_by_user_id' => $changedBy->id,
                'field' => $field,
                'old_value' => $old,
                'new_value' => $new,
            ]);
        }
        $member->update($payload);
        return $member;
    });
}
```

**Waarom:** elke wijziging op auditeerbare velden krijgt een onveranderlijke regel in `user_audit_logs`. `UPDATED_AT=null` op het model voorkomt mutatie achteraf. `ensureTeamRetainsTeamleider()` voorkomt dat de laatste teamleider zichzelf demoteert en het team onbeheerbaar maakt.

---

## US-06 — Teamlid deactiveren en heractiveren

**Branch:** `feature/teamlid-deactiveren` · **PR:** [#8](https://github.com/abii2024/nexora/pull/8) · **Tests:** 19 (62 asserts)

**Kern-files:**
- [`app/Services/UserService.php#deactivate`](https://github.com/abii2024/nexora/blob/main/app/Services/UserService.php)
- [`app/Http/Middleware/CheckActiveUser.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Middleware/CheckActiveUser.php) — runtime sessie-invalidatie

**Waarom:** `is_active=false` zetten is niet genoeg — een al ingelogde gebruiker heeft nog een geldige sessie. `CheckActiveUser`-middleware draait **elke request** en logt de gebruiker meteen uit als `is_active=false`. Dat is defense in depth (login-block + runtime-block).

---

## US-07 — Cliënt aanmaken met persoonsgegevens

**Branch:** `feature/client-aanmaken` · **PR:** [#9](https://github.com/abii2024/nexora/pull/9) · **Tests:** 21 (75 asserts)

**Kern-files:**
- [`app/Http/Controllers/ClientController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/ClientController.php)
- [`app/Http/Requests/Clients/StoreClientRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Clients/StoreClientRequest.php)
- [`database/migrations/2026_04_22_134727_create_clients_table.php`](https://github.com/abii2024/nexora/blob/main/database/migrations/2026_04_22_134727_create_clients_table.php)

**Waarom:** BSN-veld nu in plaintext in dev, productie-roadmap is encryption-at-rest (zie projectverslag §10 verbetervoorstel #3). Geboortedatum als `date`-type voor leeftijdsberekening + range-validatie (geen geboortedatum in de toekomst).

---

## US-08 — Cliënten koppelen aan begeleiders (primair/secundair/tertiair)

**Branch:** `feature/client-begeleiders-koppelen` · **PR:** [#10](https://github.com/abii2024/nexora/pull/10) · **Tests:** 29 (70 asserts)

**Kern-files:**
- [`app/Services/ClientService.php`](https://github.com/abii2024/nexora/blob/main/app/Services/ClientService.php) — `syncCaregivers()` + `computeCaregiverRoles()`
- [`database/migrations/2026_04_23_072143_add_primary_secondary_partial_unique_to_client_caregivers.php`](https://github.com/abii2024/nexora/blob/main/database/migrations/2026_04_23_072143_add_primary_secondary_partial_unique_to_client_caregivers.php)
- [`app/Notifications/ClientCaregiverAssignedNotification.php`](https://github.com/abii2024/nexora/blob/main/app/Notifications/ClientCaregiverAssignedNotification.php)

**Kern-snippet — partial unique indexes op DB-niveau:**

```php
public function up(): void
{
    DB::statement(
        "CREATE UNIQUE INDEX client_caregivers_primary_unique
         ON client_caregivers(client_id) WHERE role = 'primair'"
    );

    DB::statement(
        "CREATE UNIQUE INDEX client_caregivers_secondary_unique
         ON client_caregivers(client_id) WHERE role = 'secundair'"
    );
}
```

**Waarom:** application-logic kan racen bij gelijktijdige edits. Een **partial unique index** in SQLite forceert op DB-niveau dat er max 1 primair én max 1 secundair per cliënt bestaat — tertiair heeft geen constraint (kan onbeperkt). Bij role-swap (primair → secundair én secundair → primair tegelijk) gebruikt `syncCaregivers()` een tussenstap via `tertiair` om constraint-violations te omzeilen.

---

## US-09 — Cliëntenoverzicht met rol-gebaseerde weergave

**Branch:** `feature/clienten-overzicht` · **PR:** [#11](https://github.com/abii2024/nexora/pull/11) · **Tests:** 27 (67 asserts)

**Kern-files:**
- [`app/Services/ClientService.php#getPaginated`](https://github.com/abii2024/nexora/blob/main/app/Services/ClientService.php)
- [`resources/views/clients/index.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/clients/index.blade.php) — rol-specifieke view-branching
- [`resources/views/components/clients/filter-bar.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/components/clients/filter-bar.blade.php)

**Waarom:** filter-whitelist (search / status / care_type / sort) + `->with(['caregivers', 'team'])` eager loading tegen N+1. `tests/Feature/US-09.php` heeft een `DB::listen`-regressietest die faalt als iemand de eager loading verwijdert.

---

## US-10 — Cliënt bewerken en archiveren (statusbeheer + soft delete)

**Branch:** `feature/client-bewerken-archiveren` · **PR:** [#12](https://github.com/abii2024/nexora/pull/12) · **Tests:** 31 (74 asserts)

**Kern-files:**
- [`app/Http/Controllers/ClientController.php#archive`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/ClientController.php) — soft-delete via `SoftDeletes`-trait
- [`app/Models/Client.php`](https://github.com/abii2024/nexora/blob/main/app/Models/Client.php)
- [`database/migrations/2026_04_23_120000_add_soft_deletes_to_clients_table.php`](https://github.com/abii2024/nexora/blob/main/database/migrations/2026_04_23_120000_add_soft_deletes_to_clients_table.php)
- [`app/Models/ClientStatusLog.php`](https://github.com/abii2024/nexora/blob/main/app/Models/ClientStatusLog.php) — immutable audit-log

**Waarom:** archiveren ≠ verwijderen. Wgbo-bewaartermijn 20 jaar voor zorgdossier ⇒ **`deleted_at`** vult de soft-delete in zonder data te verliezen. `forceDelete` is **bewust** UI-onbereikbaar (geen route + Policy returnt `false`). Status-wijzigingen worden gelogd in `client_status_logs`.

---

## US-11 — Concept-uren aanmaken en bewerken

**Branch:** `feature/concept-uren-aanmaken` · **PR:** [#13](https://github.com/abii2024/nexora/pull/13) · **Tests:** 28 (77 asserts)

**Kern-files:**
- [`app/Enums/UrenStatus.php`](https://github.com/abii2024/nexora/blob/main/app/Enums/UrenStatus.php) — backed-string-enum + helpers
- [`app/Services/UrenregistratieService.php`](https://github.com/abii2024/nexora/blob/main/app/Services/UrenregistratieService.php) — `computeDuration()`
- [`app/Http/Requests/Uren/StoreUrenRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Uren/StoreUrenRequest.php)

**Kern-snippet — backed enum als single-source-of-truth voor state-machine:**

```php
enum UrenStatus: string
{
    case Concept = 'concept';
    case Ingediend = 'ingediend';
    case Goedgekeurd = 'goedgekeurd';
    case Afgekeurd = 'afgekeurd';

    public function isEditable(): bool
    {
        return in_array($this, [self::Concept, self::Afgekeurd], true);
    }

    public function isSubmittable(): bool { return $this === self::Concept; }
    public function isWithdrawable(): bool { return $this === self::Ingediend; }
    public function canResubmit(): bool { return $this === self::Afgekeurd; }
}
```

**Waarom:** `user_id` + `status` buiten `$fillable` op het model ⇒ klant kan geen status overschrijven via mass-assignment. `computeDuration()` rekent in **integer-seconds** i.p.v. floats — voorkomt floating-point-wobble bij decimalen (5.4999999h vs 5.5h).

---

## US-12 — Uren indienen, terugtrekken en opnieuw indienen

**Branch:** `feature/uren-indienen-terugtrekken` · **PR:** [#14](https://github.com/abii2024/nexora/pull/14) · **Tests:** 31 (62 asserts)

**Kern-files:**
- [`app/Services/UrenregistratieService.php#transition`](https://github.com/abii2024/nexora/blob/main/app/Services/UrenregistratieService.php) — state-machine
- [`app/Exceptions/InvalidStateTransitionException.php`](https://github.com/abii2024/nexora/blob/main/app/Exceptions/InvalidStateTransitionException.php) — render naar HTTP 422
- [`app/Notifications/UrenIngediendNotification.php`](https://github.com/abii2024/nexora/blob/main/app/Notifications/UrenIngediendNotification.php)

**Kern-snippet — centrale state-machine met allowed-matrix:**

```php
public function transition(Urenregistratie $uren, UrenStatus $to, User $actor): void
{
    $from = $uren->status;

    $allowed = match ($from) {
        UrenStatus::Concept => [UrenStatus::Ingediend],
        UrenStatus::Ingediend => [UrenStatus::Concept, UrenStatus::Goedgekeurd, UrenStatus::Afgekeurd],
        UrenStatus::Afgekeurd => [UrenStatus::Ingediend],
        UrenStatus::Goedgekeurd => [],
    };

    if (!in_array($to, $allowed, true)) {
        throw new InvalidStateTransitionException($from, $to);
    }

    DB::transaction(function () use ($uren, $to, $actor) {
        if ($uren->status === UrenStatus::Afgekeurd && $to === UrenStatus::Ingediend) {
            $uren->afkeur_reden = null;
        }
        $uren->status = $to;
        $uren->save();
        // notifications volgen...
    });
}
```

**Waarom:** **één** allowed-matrix per from-state. Sprint 4 (US-13 goedkeuren/afkeuren) hergebruikt deze methode zonder de matrix te wijzigen — **Open/Closed-principe** in actie. Afkeur-reden wist automatisch bij opnieuw-indienen zodat geen oude rejection-context blijft hangen.

---

## US-13 — Uren goedkeuren of afkeuren als teamleider

**Branch:** `feature/uren-goedkeuren-afkeuren` · **PR:** [#15](https://github.com/abii2024/nexora/pull/15) · **Tests:** 27 (63 asserts)

**Kern-files:**
- [`app/Http/Controllers/Teamleider/TeamleiderUrenController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/Teamleider/TeamleiderUrenController.php)
- [`app/Services/UrenregistratieService.php#approve`](https://github.com/abii2024/nexora/blob/main/app/Services/UrenregistratieService.php) (gebruikt `transition()` van US-12)
- [`app/Http/Requests/Uren/AfkeurUrenRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Uren/AfkeurUrenRequest.php)

**Kern-snippet — approve via transition (OCP) + audit-metadata via forceFill:**

```php
public function approve(Urenregistratie $uren, User $teamleider): void
{
    DB::transaction(function () use ($uren, $teamleider) {
        $this->transition($uren, UrenStatus::Goedgekeurd, $teamleider);

        $uren->forceFill([
            'goedgekeurd_door_user_id' => $teamleider->id,
            'beoordeeld_op' => now(),
        ])->save();

        if ($uren->user) {
            Notification::send($uren->user, new UrenGoedgekeurdNotification($uren, $teamleider));
        }
    });
}
```

**Waarom:** `forceFill` is bewust gebruikt zodat `goedgekeurd_door_user_id` en `beoordeeld_op` *niet* in `$fillable` hoeven — een zorgbegeleider kan ze nooit via mass-assignment overschrijven. Notification is **database channel** (geen mailer-overhead voor in-app meldingen).

---

## US-14 — Urenoverzicht met filters (teamleider)

**Branch:** `feature/uren-overzicht-met-filters` · **PR:** [#16](https://github.com/abii2024/nexora/pull/16) · **Tests:** 22 (44 asserts)

**Kern-files:**
- [`app/Services/UrenregistratieService.php#getPaginatedForTeamleider`](https://github.com/abii2024/nexora/blob/main/app/Services/UrenregistratieService.php) — filter-whitelist + sort + paginatie
- [`resources/views/components/uren/filter-bar.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/components/uren/filter-bar.blade.php) — HTML `<input type="week">` ISO 8601

**Waarom:** filters (`status`, `medewerker`, `week`) en sort (`datum`, `medewerker`, `duur`) zijn allemaal **whitelist**-gevalideerd — onmogelijk om via query-string een willekeurig SQL-kolom-naam in te jecten. Week-filter parset ISO-week-string `YYYY-Www` naar start/end-of-week. `withQueryString()` op de paginator behoudt filters bij doorklikken. Tegen N+1: `DB::listen`-regressietest.

---

## US-15 — Wachtwoord vergeten & resetten via e-maillink

**Branch:** `feature/wachtwoord-vergeten` · **PR:** [#17](https://github.com/abii2024/nexora/pull/17) · **Tests:** 16 (46 asserts)

**Kern-files:**
- [`app/Http/Controllers/Auth/ForgotPasswordController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/Auth/ForgotPasswordController.php)
- [`app/Http/Controllers/Auth/ResetPasswordController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/Auth/ResetPasswordController.php)
- [`app/Notifications/WachtwoordResetNotification.php`](https://github.com/abii2024/nexora/blob/main/app/Notifications/WachtwoordResetNotification.php)
- [`resources/views/emails/wachtwoord-reset.blade.php`](https://github.com/abii2024/nexora/blob/main/resources/views/emails/wachtwoord-reset.blade.php) — NL markdown-template

**Waarom:** **enumeration-protection** — identieke flash-message voor "bestaand" en "onbekend" e-mailadres voorkomt dat een aanvaller via reset-flow valide accounts kan enumereren. Token-lifetime: 60 minuten (Laravel default in `config/auth.php`). Auto-login + rol-specifieke dashboard-redirect na reset. **US-17 verbetervoorstel:** mail-driver omgezet van `log` → `resend` met echte Gmail-inbox-validatie (zie [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md)).

---

## US-16 — Profielbeheer (eigen gegevens + wachtwoord wijzigen)

**Branch:** `feature/profielbeheer` · **PR:** [#18](https://github.com/abii2024/nexora/pull/18) · **Tests:** 21 (52 asserts)

**Kern-files:**
- [`app/Http/Controllers/ProfielController.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Controllers/ProfielController.php)
- [`app/Http/Requests/Profiel/UpdateProfielRequest.php`](https://github.com/abii2024/nexora/blob/main/app/Http/Requests/Profiel/UpdateProfielRequest.php) — `current_password`-rule + email-unique-ignore
- [`bootstrap/app.php`](https://github.com/abii2024/nexora/blob/main/bootstrap/app.php) — `AuthenticateSession`-middleware geconfigureerd

**Kern-snippet — `forceFill` + `logoutOtherDevices`:**

```php
public function update(UpdateProfielRequest $request): RedirectResponse
{
    $user = $request->user();
    $payload = $request->profielData();
    $newPassword = $request->newPassword();

    // forceFill om expliciet te zijn over WELKE velden mogen muteren.
    $user->forceFill([
        'name' => $payload['name'],
        'email' => $payload['email'],
    ])->save();

    if ($newPassword !== null) {
        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        Auth::logoutOtherDevices($newPassword);
    }
    // ...
}
```

**Waarom:** `forceFill` is opzettelijk — `role`, `is_active`, `team_id` zitten *bewust niet* in deze whitelist. Mass-assignment-probes (`tests/Feature/US-16.php`) verifiëren dat een gebruiker zichzelf niet kan promoten naar teamleider via een gewijzigd POST-payload. `logoutOtherDevices` invalideert alle andere sessies wanneer wachtwoord wijzigt — voorkomt dat een gestolen sessie blijft werken.

---

## US-17 — Reset-mail end-to-end via Resend (verbetervoorstel uit Opdracht 4)

**Branch:** direct op `main` · **Commit:** [`9262d66`](https://github.com/abii2024/nexora/commit/9262d66) · **Doc:** [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md)

**Kern-files:**
- [`.env.example`](https://github.com/abii2024/nexora/blob/main/.env.example) — `MAIL_MAILER=resend` + `RESEND_KEY` placeholder
- [`composer.json`](https://github.com/abii2024/nexora/blob/main/composer.json) — `resend/resend-laravel` dependency
- [`database/seeders/DatabaseSeeder.php`](https://github.com/abii2024/nexora/blob/main/database/seeders/DatabaseSeeder.php) — teamleider met echt Gmail-adres voor mail-test

**Waarom:** zie de uitgebreide analyse in [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md). De `WachtwoordResetNotification` uit US-15 is **ongewijzigd** — alleen de transport-laag is omgewisseld (driver `log` → `resend`). Bewijs in productie: schermafbeelding 3 van de Gmail-inbox in opdracht-4-README §7.

---

## Bijlage: complete test-suite

Alle 16 user stories hebben een dedicated test-file: [`tests/Feature/US-NN.php`](https://github.com/abii2024/nexora/tree/main/tests/Feature). Totaal: **360 Pest-tests / 953 asserts — allemaal groen**. Per-sprint en per-US testaantallen staan in [`projectverslag.md §5`](../projectverslag.md) en [`testplan/README.md §6`](../testplan/README.md).

Reproduceren lokaal:
```bash
php artisan test
# of per US:
php artisan test --filter US-08
```
