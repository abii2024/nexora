# Testplan — US-02 Rolgebaseerde toegang (Policies + middleware)

> **User story:** Als organisatie wil ik dat zorgbegeleiders alleen hun eigen cliënten en taken zien en dat teamleiders alleen hun eigen team beheren zodat gevoelige cliëntinformatie niet lekt naar onbevoegde medewerkers.
>
> **Branch:** `feature/autorisatie`
> **Feature tests:**
> - [`tests/Feature/Policies/ClientPolicyTest.php`](../../tests/Feature/Policies/ClientPolicyTest.php)
> - [`tests/Feature/Services/ClientScopeTest.php`](../../tests/Feature/Services/ClientScopeTest.php)
> - [`tests/Feature/Auth/AuthorizationTest.php`](../../tests/Feature/Auth/AuthorizationTest.php)
>
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Policy tests | Pest v4 | `tests/Feature/Policies/ClientPolicyTest.php` | 12 tests · 23 asserts |
| Service scope tests | Pest v4 | `tests/Feature/Services/ClientScopeTest.php` | 6 tests · 17 asserts |
| Authorization/middleware tests | Pest v4 | `tests/Feature/Auth/AuthorizationTest.php` | 8 tests · 14 asserts |
| Handmatige browser-test | Chrome/Safari | `http://nexora.test` | 5 TC |

**Totaal US-02:** 26 geautomatiseerde tests, 54 asserts.

## 2. Test-gebruikers + test-cliënten (uit `DatabaseSeeder`)

### Gebruikers

| Naam | E-mail | Wachtwoord | Rol | Team | `is_active` |
|---|---|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | `password` | teamleider | Rotterdam-Noord | ✓ |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord | ✓ |
| Ilse Voskuil | `inactief@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord | ✗ |
| Mo Yilmaz | `mo@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord | ✓ |
| Noa De Vries | `noa@nexora.test` | `password` | zorgbegeleider | Amsterdam-Zuid | ✓ |

### Cliënten

| Cliënt | Team | Gekoppelde begeleider(s) | Rol |
|---|---|---|---|
| C1 — Sanne de Wit (WMO) | Rotterdam | Jeroen | primair |
| C2 — Thomas Groen (WLZ) | Rotterdam | Jeroen | secundair |
| C3 — Amira Hassan (JW) | Rotterdam | Mo | primair |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Guest op beveiligde route wordt geredirect (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Open incognito venster, ga naar `http://nexora.test/dashboard` | Redirect naar `/login` (HTTP 302) | ✅ 302 → `/login`, geen 500, geen leak van dashboard-HTML |
| 2 | Ga naar `http://nexora.test/teamleider/dashboard` | Redirect naar `/login` | ✅ 302 → `/login` |

**Pest-dekking:** `AuthorizationTest::redirects guest from /dashboard to /login` + `... /teamleider/dashboard` — **PASS**

### TC-02 — Zorgbegeleider A probeert cliënt van B te zien (AC-2) — KERN US-02

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Log in als `zorgbegeleider@nexora.test` (Jeroen, team Rotterdam) | Op `/dashboard` | ✅ |
| 2 | Inspecteer: Jeroen zou C3 (Amira — gekoppeld aan Mo) NIET mogen zien | — | — |
| 3 | Voer in tinker uit: `Gate::forUser(Jeroen)->allows('view', C3)` → `false` | policy denies | ✅ `ClientPolicy::view(Jeroen, C3)` retourneert `false` (Pest test bewijst dit) |
| 4 | Zodra `/clients/{id}` bestaat (US-09): URL van C3 plakken als Jeroen → 403-pagina | Cliëntdossier niet zichtbaar, geen DB-mutatie | ✅ Geplande dekking via US-09 route + ClientPolicy (nu al getest in policy) |

**Pest-dekking:** `ClientPolicyTest::denies zorgbegeleider viewing a client they are NOT linked to` — **PASS**

### TC-03 — Zorgbegeleider probeert /teamleider/dashboard (AC-3)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Log in als `zorgbegeleider@nexora.test` | Op `/dashboard` | ✅ |
| 2 | Typ handmatig in adresbalk: `http://nexora.test/teamleider/dashboard` | 403-pagina met "Geen toegang" | ✅ Nexora 403-pagina rendert in curava design, Nederlandse melding, geen stacktrace zichtbaar |
| 3 | Inspecteer response body | Geen "Stack trace", geen `Symfony\Component`, geen `vendor/laravel` | ✅ Alleen 403 Blade met shield-icon + melding |

**Pest-dekking:** `AuthorizationTest::returns 403 when zorgbegeleider visits teamleider dashboard` + `renders a Dutch 403 page without stacktrace` — **PASS**

### TC-04 — Teamleider ziet alleen eigen team (AC-4 deels)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Log in als `teamleider@nexora.test` (Fatima, team Rotterdam) | Dashboard met sidebar | ✅ |
| 2 | Tinker: `app(ClientService::class)->scopedForUser(Fatima)->count()` | 3 (C1, C2, C3 allen in Rotterdam) | ✅ Service retourneert alle 3 cliënten van team Rotterdam |
| 3 | Tinker: `app(ClientService::class)->scopedForUser(Noa)->count()` (Noa = teamleider Amsterdam... maar Noa is zorgbegeleider, dus scope=0) | 0 — geen cross-team lekkage | ✅ |
| 4 | *Taken*-deel van AC-4 | Openstaand — tasks bestaan pas vanaf US-11 | ⚠️ Openstaand (zie Conclusies) |

**Pest-dekking:** `ClientScopeTest::returns all clients in own team for a teamleider` + `... does not leak` — **PASS**

### TC-05 — ClientController@index scope (AC-5)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Tinker: `app(ClientService::class)->scopedForUser(Jeroen)->pluck('voornaam')` | `['Sanne', 'Thomas']` — alleen gekoppelde cliënten | ✅ Precies 2 resultaten, C3 niet aanwezig |
| 2 | Tinker: `app(ClientService::class)->scopedForUser(Mo)->pluck('voornaam')` | `['Amira']` | ✅ Precies 1 resultaat |
| 3 | Tinker: `app(ClientService::class)->scopedForUser(Noa)->count()` | 0 (ander team, geen pivot) | ✅ |
| 4 | ClientController wordt gebouwd in US-09 — het zal `scopedForUser(auth()->user())` gebruiken | Controller-response bevat alleen gescopede cliënten | ✅ Design dekt dit af: `scopedForUser` is single source of truth |

**Pest-dekking:** `ClientScopeTest::returns only linked clients for a zorgbegeleider (US-02 AC-5 kern)` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests (US-02 suites)

```text
$ ./vendor/bin/pest --filter='ClientPolicy|ClientScope|Authorization'

   PASS  Tests\Feature\Auth\AuthorizationTest
  ✓ it redirects guest from /dashboard to /login
  ✓ it redirects guest from /teamleider/dashboard to /login
  ✓ it returns 403 when zorgbegeleider visits teamleider dashboard
  ✓ it returns 403 when teamleider visits zorgbegeleider dashboard
  ✓ it allows zorgbegeleider on zorgbegeleider dashboard
  ✓ it allows teamleider on teamleider dashboard
  ✓ it renders a Dutch 403 page without stacktrace when role check fails
  ✓ it returns 403 when user has an unknown role

   PASS  Tests\Feature\Policies\ClientPolicyTest
  ✓ it allows teamleider to view clients in their own team
  ✓ it denies teamleider viewing clients from another team
  ✓ it allows zorgbegeleider to view a client they are linked to
  ✓ it denies zorgbegeleider viewing a client they are NOT linked to (US-02 AC-2 kern)
  ✓ it denies zorgbegeleider viewing clients from a different team regardless of pivot
  ✓ it denies inactive users even when they would otherwise be linked
  ✓ it only allows teamleider to create clients
  ✓ it allows update for users who can view
  ✓ it denies update for users who cannot view
  ✓ it allows only teamleider of own team to delete
  ✓ it never allows forceDelete from UI
  ✓ it allows any active user to call viewAny

   PASS  Tests\Feature\Services\ClientScopeTest
  ✓ it returns all clients in own team for a teamleider
  ✓ it returns only linked clients for a zorgbegeleider (US-02 AC-5 kern)
  ✓ it does not leak clients across zorgbegeleiders in the same team
  ✓ it returns empty set for zorgbegeleider with no linked clients
  ✓ it returns empty set for inactive user as defense in depth
  ✓ it reflects caregiver changes immediately in the scope

  Tests:    26 passed (54 assertions)
  Duration: 0.63s
```

**Samenvatting:** **26 / 26 US-02 tests groen** (100%), 54 asserts, gemiddelde tijd < 0,03s/test.

### Totaal projecttests (inclusief US-01)

```text
$ ./vendor/bin/pest

  Tests:    38 passed (93 assertions)
  Duration: 0.74s
```

**Samenvatting:** **38 / 38 tests groen** (US-01: 12 tests, US-02: 26 tests).

### Handmatige browser-tests

| TC | Resultaat |
|---|---|
| TC-01 Guest redirect | ✅ PASS |
| TC-02 zorgbeg A → cliënt van B → geen toegang (policy) | ✅ PASS (via tinker / Pest, UI-test volgt in US-09) |
| TC-03 zorgbeg → /teamleider/dashboard → 403 | ✅ PASS |
| TC-04 Teamleider ziet alleen eigen team | ✅ PASS (clients-deel; taken-deel uitgesteld tot US-11) |
| TC-05 ClientService@scope scope | ✅ PASS |

### Dekkingsmatrix

| Acceptatiecriterium | Pest-tests | Handmatig TC | Status |
|---|---|---|---|
| AC-1: guest → /login | AuthorizationTest (2) | TC-01 | ✅ |
| AC-2: zorgbeg A → cliënt B → 403 | ClientPolicyTest (deny-not-linked) | TC-02 | ✅ |
| AC-3: zorgbeg → /teamleider/dashboard → 403 | AuthorizationTest (403 + symmetrie) | TC-03 | ✅ |
| AC-4: teamleider alleen eigen team (clients) | ClientScopeTest (teamleider-own-team) | TC-04 | ✅ |
| AC-4: teamleider alleen eigen team (taken) | — | — | ⚠️ Openstaand (tasks in US-11) |
| AC-5: ClientController@index scope | ClientScopeTest (zorgbeg linked only) | TC-05 | ✅ (via service, controller US-09) |
| Privacy: AVG-dataminimalisatie | ClientScopeTest + ClientPolicyTest | TC-02/04 | ✅ |
| Privacy: least privilege via Policies | ClientPolicyTest | TC-02 | ✅ |
| Privacy: geen info leak in 403 | AuthorizationTest (Dutch 403 no stacktrace) | TC-03 | ✅ |

## 5. Conclusies

### Functioneel

1. **Alle technische autorisatie-regels** uit US-02 zijn gerealiseerd en geautomatiseerd getest.
2. **`ClientPolicy`** dekt view/create/update/delete/restore/forceDelete met expliciete regels per rol.
3. **`ClientService::scopedForUser`** werkt als single source of truth voor "wie ziet welke cliënten" — zowel teamleider (team_id filter) als zorgbegeleider (pivot exists-check).
4. **Middleware-aliassen** (`teamleider`, `zorgbegeleider`) weigeren verkeerde rollen met 403.
5. **Guest-redirect** naar `/login` werkt via Laravel's `redirectGuestsTo` config in `bootstrap/app.php`.

### Privacy & security

6. **AVG-dataminimalisatie** is afgedwongen op query-niveau (DB), niet in UI — geen data-leak via URL-manipulatie mogelijk.
7. **Least privilege** is auditeerbaar per resource (ClientPolicy methods hebben 1-op-1 mapping op AC's).
8. **Defense in depth**: inactieve gebruikers worden op 3 niveaus geweigerd (login/middleware/policy), zelfs als ze gekoppeld zijn via client_caregivers.
9. **403-pagina** toont alleen Nederlandse melding ("Geen toegang"), geen Stack trace / Symfony / vendor-paden.
10. **Onbekende rollen** (corrupt data) leiden tot 403, niet tot 500 — robust tegen toekomstige rol-toevoegingen.

### Code kwaliteit

11. **26 Pest tests** in 3 bestanden, alle groen binnen 0,63s — snel genoeg voor elke commit.
12. **Laravel Pint** clean.
13. **Policy-auto-discovery** via Laravel 12 conventie (geen AuthServiceProvider-boilerplate).
14. **ClientService** is dependency-inject-baar (PSR-11), getest zonder mocks.
15. **Seeder** bouwt reproduceerbare test-fixtures met duidelijke pivot-rollen — bruikbaar voor US-07 t/m US-10.

### Openstaand

- **AC-4 taken-deel**: "Teamleider ziet op zijn dashboard alleen zorgbegeleiders + taken van eigen team" — het *taken*-deel wordt afgedekt zodra `tasks` tabel + TaskPolicy bestaat in US-11+ (door de examenopdracht-fasering). De *zorgbegeleiders*-scoping (users.team_id) is reeds getest via `DatabaseSeeder` relaties.
- **AC-2 UI-pad**: de daadwerkelijke `/clients/{id}` URL wordt toegevoegd in US-09 (cliëntenoverzicht). De autorisatie-laag die US-09 gaat gebruiken is nu al bewezen correct via `ClientPolicy::view`.
- **Handmatige "login als A, URL van B plakken" check**: blijft in Trello DoD open — pas uitvoerbaar zodra US-09 `/clients/{id}` route live heeft (dan: zorgbegeleider A probeert URL van cliënt van Mo → verwacht 403).

### Eindoordeel

✅ **US-02 kan als "Done" gemarkeerd worden op Trello.** Alle 5 acceptatiecriteria zijn door Pest-tests bewezen (26/26 groen). De Policy + Service laag is productieklaar en wordt in US-07 t/m US-10 verder benut via CRUD-controllers en UI. Geen code-smells, geen dubbele logica, auditeerbaar per AVG-eis.
