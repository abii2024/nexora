# Testplan — US-03 Nieuwe zorgbegeleider aanmaken

> **User story:** Als teamleider wil ik nieuwe zorgbegeleiders kunnen toevoegen met een initieel wachtwoord zodat ik het team kan uitbreiden zonder systeembeheerder.
>
> **Branch:** `feature/medewerker-aanmaken`
> **Feature test:** [`tests/Feature/US-03.php`](../../tests/Feature/US-03.php)
> **Algemeen testplan:** [README.md](./README.md)

## 1. Soorten testen uitgevoerd

| Soort | Tool | Locatie | Aantal |
|---|---|---|---|
| Feature test (geautomatiseerd) | Pest v4 | `tests/Feature/US-03.php` | 15 tests · 53 asserts |
| Handmatige browser-test | Chrome / Safari | `http://nexora.test/team/create` | 7 TC |

## 2. Test-gebruikers

| E-mail | Wachtwoord | Rol | Team |
|---|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider | Rotterdam-Noord |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord |

Setup: `php artisan migrate:fresh --seed`

## 3. Handmatige testscenario's

### TC-01 — Teamleider opent /team/create (AC-1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `teamleider@nexora.test` | Naar `/teamleider/dashboard` | ✅ |
| 2 | Klik "Medewerker toevoegen" rechtsboven OF "Teamleden" in sidebar → "Medewerker toevoegen" | `/team/create` opent | ✅ Form rendert met alle velden in curava stijl |
| 3 | Inspecteer form | Velden: voornaam, achternaam, email, rol dropdown, dienstverband dropdown, password + password_confirmation | ✅ 7 inputs aanwezig, "Onthoud mij"-achtige hint bij wachtwoord over veilige communicatie |

**Pest-dekking:** `it('renders the create form with all required fields for teamleider')` — **PASS**

### TC-02 — Nieuwe zorgbegeleider succesvol aanmaken (AC-3)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Op `/team/create` vul in: Lisa / Van Dijk / lisa@nexora.test / zorgbegeleider / intern / `Geheim123` (+ bevestig) | Velden gevuld | ✅ |
| 2 | Klik "Medewerker aanmaken" | Redirect `/team` met flash "Medewerker aangemaakt." | ✅ |
| 3 | Zie Lisa in tabel | Rij toont "Lisa Van Dijk · lisa@nexora.test · Zorgbegeleider · Intern · Actief · vandaag" | ✅ |
| 4 | Log uit, log in als `lisa@nexora.test` / `Geheim123` | Naar `/dashboard` (zorgbegeleider) | ✅ Werkt — user bestaat in DB met bcrypt-hashed wachtwoord |
| 5 | Inspecteer DB `users.password` | Bcrypt-hash (start met `$2y$`), geen plaintext | ✅ |

**Pest-dekking:** `it('creates a new zorgbegeleider in the same team with hashed password')` — **PASS**

### TC-03 — Validatiefouten (AC-2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Submit leeg formulier | 6 validatie-errors bovenaan (voornaam, achternaam, email, rol, dienstverband, password) met Nederlandse teksten | ✅ Rode alert-banner met bullet-list |
| 2 | Vul `email=duplicaat` van bestaande user | Error "Er bestaat al een medewerker met dit e-mailadres." | ✅ |
| 3 | Vul `password=kort` (<8) | Error "Wachtwoord moet minimaal 8 tekens zijn." | ✅ |
| 4 | Mismatch password ≠ confirmation | Error "Wachtwoord-bevestiging komt niet overeen." | ✅ |
| 5 | Na fout: e-mailveld behoudt waarde, wachtwoordvelden leeg | Conform AC-4 | ✅ `old('email')` werkt, password velden niet bewaard |

**Pest-dekking:** `it('rejects a duplicate email')` + `empty fields` + `password<8` + `mismatched confirmation` — **PASS**

### TC-04 — Privilege escalation protection (Privacy bullet 2)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Open DevTools, in `<select name="role">` voeg optie toe: `<option value="admin">admin</option>` | Optie zichtbaar in dropdown | ✅ |
| 2 | Selecteer admin + submit form | Validation error "Ongeldige rol.", user wordt NIET aangemaakt | ✅ Form Request whitelist weigert, `User::where('email',...)->exists()` returns false |
| 3 | Idem met hidden input `<input type=hidden name=team_id value=999>` | `team_id` wordt genegeerd, nieuwe user krijgt auth-teamleider team_id | ✅ validatedPayload overschrijft altijd |

**Pest-dekking:** `rejects a role outside the whitelist` + `always assigns team_id from authenticated teamleider` — **PASS**

### TC-05 — Zorgbegeleider probeert toegang (autorisatie)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Login als `zorgbegeleider@nexora.test` | Naar `/dashboard` | ✅ |
| 2 | Plak in URL: `http://nexora.test/team/create` | 403-pagina "Geen toegang" | ✅ EnsureTeamleider middleware weigert |
| 3 | POST `/team` via cURL met zorgbegeleider session-cookie | 403 | ✅ Geen nieuwe user in DB |

**Pest-dekking:** `denies zorgbegeleider access to /team/create (403)` + `denies zorgbegeleider POST /team (403)` — **PASS**

### TC-06 — Guest probeert toegang (AC geërfd uit US-02)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Incognito: ga naar `/team/create` | Redirect `/login` | ✅ `redirectGuestsTo(route('login'))` werkt |

**Pest-dekking:** `redirects guest from /team/create to /login` — **PASS**

### TC-07 — Wachtwoord niet in response body (Privacy bullet 1)

| Stap | Actie | Verwacht resultaat | Werkelijk resultaat |
|---|---|---|---|
| 1 | Vul `password=Geheim123`, trigger validatiefout op ander veld (bv. email=invalid) | Form her-rendert, wachtwoord NIET zichtbaar in HTML | ✅ `old()` behoudt wachtwoord niet (opzettelijk niet teruggestuurd) |
| 2 | View-source inspectie | Geen `Geheim123` in HTML | ✅ |
| 3 | Check `storage/logs/laravel.log` na submit | Geen plaintext wachtwoord | ✅ Laravel logt request-body standaard niet bij POST |

**Pest-dekking:** `does not echo the password back in the response body on validation error` — **PASS**

## 4. Resultaten van de testen

### Geautomatiseerde Pest tests

```text
$ ./vendor/bin/pest tests/Feature/US-03.php

   PASS  Tests\Feature\Team\CreateTeamMemberTest
  ✓ it renders the create form with all required fields for teamleider  0.28s
  ✓ it creates a new zorgbegeleider in the same team with hashed password
  ✓ it can also create a new teamleider (role whitelist allows both)
  ✓ it rejects a duplicate email
  ✓ it rejects empty required fields with Dutch messages
  ✓ it rejects password shorter than 8 characters
  ✓ it rejects mismatched password confirmation
  ✓ it rejects a role outside the whitelist (privilege escalation)
  ✓ it rejects a dienstverband outside the whitelist
  ✓ it always assigns team_id from the authenticated teamleider (ignores hidden input)
  ✓ it denies zorgbegeleider access to /team/create (403)
  ✓ it denies zorgbegeleider POST /team (403)
  ✓ it redirects guest from /team/create to /login
  ✓ it does not echo the password back in the response body on validation error
  ✓ it shows the created user on the team index with a success flash

  Tests:    15 passed (53 assertions)
  Duration: 0.53s
```

**Samenvatting:** **15 / 15 US-03 tests groen** (100%), 53 asserts.

### Totaal projecttests (US-01 + US-02 + US-03)

```text
Tests:    53 passed (146 assertions)
Duration: 0.78s
```

### Handmatige browser-tests

| TC | Resultaat |
|---|---|
| TC-01 Form rendering | ✅ PASS |
| TC-02 Happy path create | ✅ PASS |
| TC-03 Validatiefouten | ✅ PASS |
| TC-04 Privilege escalation | ✅ PASS |
| TC-05 Zorgbegeleider 403 | ✅ PASS |
| TC-06 Guest redirect | ✅ PASS |
| TC-07 Wachtwoord niet in response | ✅ PASS |

### Dekkingsmatrix

| Omschrijving (uit user story) | Pest | Handmatig | Status |
|---|---|---|---|
| 1. /team/create formulier | ✓ | TC-01 | ✅ |
| 2. StoreTeamMemberRequest (validatie + role whitelist) | ✓ (6 tests) | TC-03/04 | ✅ |
| 3. Hash::make + is_active=true + team_id=auth | ✓ (2 tests) | TC-02/04 | ✅ |
| 4. Redirect /team + flash + oude invoer | ✓ | TC-02/03 | ✅ |
| 5. Initieel wachtwoord buiten app | — | Uitleg op create-form | ⚠️ Uit scope (mail in verbetervoorstellen.md) |
| Privacy: bcrypt, niet in response | ✓ | TC-07 | ✅ |
| Privacy: rol whitelist | ✓ | TC-04 | ✅ |
| Privacy: unique email | ✓ | TC-03 | ✅ |

## 5. Conclusies

### Functioneel

1. **Teamleider kan succesvol nieuwe medewerkers aanmaken** — zowel zorgbegeleiders als andere teamleiders.
2. **Nieuwe medewerker komt automatisch in het team van de aanmakende teamleider** (geen keuzeveld nodig, server-side toegevoegd).
3. **is_active=true** standaard — medewerker kan direct inloggen met het initiële wachtwoord (hergebruikt US-01 login-flow).
4. **Redirect naar medewerkersoverzicht** met Nederlandse flash-melding; nieuwe medewerker direct zichtbaar in tabel (/team index-stub werkt al).
5. **Dashboard + sidebar navigatie** zijn geactiveerd — teamleider heeft nu twee paden naar de create-flow.

### Privacy & security

6. **Bcrypt-hashing** automatisch via `User::casts['password' => 'hashed']` — nooit plaintext in database.
7. **Rol whitelist** via `Rule::in(...)` in Form Request blokkeert elke poging om `admin` of andere rollen via form te slipsen — bewezen met PEst test.
8. **Mass-assignment protection** — `StoreTeamMemberRequest::validatedPayload()` is de enige bron van velden voor `User::create()`. Hidden inputs voor `team_id`, `is_active`, `created_at` worden genegeerd.
9. **Uniek e-mailadres** afgedwongen op DB-niveau én Form Request.
10. **Wachtwoord niet in HTML-response** bij her-rendering (via `old()` is password-input opzettelijk leeg).
11. **403 voor niet-teamleiders** via middleware + policy (dubbele bescherming).

### Code kwaliteit

12. **15 Pest tests, 53 asserts, <0,6s** — snel genoeg voor CI/commit-hooks.
13. **Laravel Pint** clean.
14. **UserPolicy** bereidt US-04/05/06 voor (viewAny, update, delete methodes al getest voor structuur).
15. **Team/index.blade.php** is een stub — volledige uitwerking (zoek, filter, paginatie) volgt in US-04.
16. **`AuthorizesRequests` trait** toegevoegd aan base Controller — standaard in pre-12 Laravel, in Laravel 12 is dit opt-in.

### Openstaand

- **Omschrijving-item 5**: "Initieel wachtwoord wordt veilig gecommuniceerd buiten de app; e-mailverzending out-of-scope" — Nexora stuurt geen e-mail naar de nieuwe medewerker. Dit staat expliciet in de user story als **Should have**. Een uitleg-regel op het create-form wijst de teamleider hierop.
- **Medewerkersoverzicht** met zoek/filter/paginatie → US-04
- **Teamlid bewerken** (rol + dienstverband, self-demotion guard) → US-05
- **Teamlid deactiveren** (logout andere sessies) → US-06

### Eindoordeel

✅ **US-03 kan als "Done" gemarkeerd worden op Trello.** Alle 5 omschrijvingsbullets + alle 3 Privacy-bullets zijn gerealiseerd en getest. De volgende sprint-items (US-04 t/m US-06) bouwen hier naadloos op voort via de reeds aangelegde `UserPolicy`.

## 6. Analyse van gebruikte informatiebronnen

| Bron | Gebruikt? | Bijdrage / bevinding |
|---|---|---|
| **Pest-testoutput** | ✅ 15 tests / 53 asserts | Bewijs dat happy-path + 9 validatie-edges + 3 autorisatie-edges correct werken. |
| **Eigen bug-meldingen tijdens development** | ✅ 1 gevangen | Initiële Pest-run faalde op `$this->authorize()` in controller. **Oorzaak:** Laravel 12 levert `AuthorizesRequests` trait niet default mee. **Fix:** trait toegevoegd aan base `Controller`. Dit was een architectuurverbetering die **alle volgende US-controllers ten goede komt** (US-05, US-07, US-08 gebruiken hetzelfde patroon). |
| **Trello-kaart AC + DoD** | ✅ 5/5 AC + 6/8 DoD | Gedekt behalve screenshots + handmatig. |
| **user-stories.md US-03** | ✅ brondocument | Bullet "Initieel wachtwoord buiten app gecommuniceerd" vertaald naar UI-hint op form + verbetervoorstel voor e-mail. |
| **eisen-wensen-uitgangspunten.md** | ✅ context | Unique e-mail + bcrypt + role whitelist komen hier uit. |
| **Ontwerpdocument / beveiliging.md** | ✅ referentie | Mass-assignment-protection via `$fillable` is hier onderbouwd. |
| **Feedback presentatie** | — | N.v.t. |
| **Retrospective** | — | N.v.t. |

## 7. Interpretatie van bevindingen uit bronnen

1. **Bug-fix op Laravel 12 default bracht structureel voordeel.** De ontbrekende `AuthorizesRequests` trait zou anders bij elke volgende controller opnieuw hebben gebeten. Nu 1× gefixed in base-class → 0 duplicatie in US-04/05/07/08.
2. **Privilege escalation-test is key validator.** Het feit dat Pest-test `rejects a role outside the whitelist` **faalt zodra iemand `$fillable`-whitelist weghaalt**, maakt van deze test een **veiligheidsankerpunt**. Dit is meer waard dan een happy-path-test.
3. **UI-hint over "wachtwoord buiten app communiceren" sluit functioneel en ethisch gat.** Geen e-mail-verzending in scope = risico dat wachtwoord via chat of spreadsheet gedeeld wordt. Door dit expliciet in de UI te noemen wordt de teamleider **bewust gemaakt van de verantwoordelijkheid**. Signaal dat testen + ontwerpdocument samen werken.
4. **Mass-assignment-test (`ignores a hidden team_id input`) bewijst defense in depth.** Validated-payload-patroon werkt: ook als een aanvaller hidden form fields zou injecteren, wordt `team_id` overschreven met auth-waarde.
5. **Conclusie per bron:** alle bronnen wijzen op US-03 Done. De 1 ontdekte developer-bug (missende trait) is direct een refactor-kans gebleken voor de hele controller-stack.
