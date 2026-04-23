# Testplan Nexora

> **Project:** Nexora — Zorgbegeleidingssysteem voor beschermd wonen
> **Framework:** Laravel 12 + PHP 8.4
> **Testframework:** [Pest](https://pestphp.com/) v4 + pest-plugin-laravel
> **Database (tests):** SQLite in-memory via `RefreshDatabase` trait
> **Scope:** 16 user stories uit [user-stories.md](../user-stories.md) — US-01 t/m US-16

Dit document beschrijft **welke soorten testen** worden uitgevoerd op Nexora en **hoe** met de testen wordt omgegaan. Per user story wordt een apart testplan bijgehouden in `docs/testplan/US<NN>-<naam>.md` met concrete testscenario's, verwachte + werkelijke resultaten en conclusies.

## 1. Soorten testen

### 1.1 Pest feature tests (geautomatiseerd)

**Wat**: End-to-end HTTP-testen die een echte request naar de applicatie doen (via Laravel's `TestCase`), de database raken en de volledige request-pipeline doorlopen (middleware, Form Requests, Policies, Controllers, Eloquent, Blade render).

**Waarom**: Dekt elk acceptatiecriterium automatisch af. Draait bij elke commit — regressie-bescherming voor latere sprints.

**Waar**: `tests/Feature/US-NN.php`.

**Voorbeeld uit US-01:**
```php
it('logs in an active zorgbegeleider and redirects to /dashboard', function () {
    $user = User::factory()->zorgbegeleider()->create([...]);
    $response = $this->post('/login', [...]);
    $response->assertRedirect(route('dashboard'));
    expect(auth()->check())->toBeTrue();
});
```

**Uitvoeren:**
```bash
./vendor/bin/pest                     # alles
./vendor/bin/pest tests/Feature/US-01.php   # één US
```

### 1.2 Handmatige browser-tests

**Wat**: Stap-voor-stap uitvoeren van testscenario's via [http://nexora.test](http://nexora.test) in Chrome/Safari.

**Waarom**: Validatie van UX, rendering, JavaScript-interacties en visuele correctheid. Dekt wat Pest niet kan zien (CSS, animaties, focus states).

**Waar gedocumenteerd**: Per US in de "Handmatige testscenario's"-sectie van het US-testplan, met TC-XX tabellen.

**Output**: Screenshots in `docs/screenshots/us<nn>-<naam>/`, checklist per TC.

### 1.3 Unit tests

**Wat**: Geïsoleerde testen voor business-rules in Services/Models (bijv. `ClientService::computeCaregiverRoles()` in US-08, state-transitions in US-12).

**Waar**: `tests/Unit/...`.

**Gebruik**: Pas vanaf US-08/US-12 relevant — simpele CRUD-US's hebben genoeg aan feature tests.

## 2. Hoe we met de testen omgaan

### 2.1 Per user story

1. **Acceptatiecriteria → tests**: elke AC uit de user story krijgt **minimaal 1** Pest test met een duidelijke `it('...')` beschrijving.
2. **Alternatieve scenario's** worden verplicht meegenomen (zie §3).
3. **Testplan-document** `docs/testplan/US<NN>-<naam>.md` bevat:
   - Test-gebruikers tabel
   - TC-XX tabel per scenario (verwacht + werkelijk resultaat)
   - Pest-dekkings-mapping
   - Testresultaten (Pest output)
   - Conclusies
4. **Screenshots** in `docs/screenshots/us<nn>-<naam>/` met README-checklist.
5. **US wordt pas "klaar"** als alle Pest tests groen zijn + handmatige TC's afgevinkt.

### 2.2 Testdata

Alle testdata komt uit **Laravel Factories** en **Seeders** — **nooit** handgeschreven inserts of SQL.

**Factories** (`database/factories/`):
- `UserFactory` met states: `->teamleider()`, `->zorgbegeleider()`, `->inactive()`
- `TeamFactory` (mit `faker->company()`)
- Vanaf US-07: `ClientFactory`, vanaf US-11: `UrenregistratieFactory`, etc.

**Seeders** (`database/seeders/`):
- `DatabaseSeeder` zaait 3 test-users + 1 team voor handmatige tests:
  - `teamleider@nexora.test` / `password`
  - `zorgbegeleider@nexora.test` / `password`
  - `inactief@nexora.test` / `password`

**In Pest** gebruiken we `RefreshDatabase` — elke test draait tegen een schone in-memory SQLite (geconfigureerd in `phpunit.xml`). De handmatige browser-tests gebruiken de lokale `database/database.sqlite`.

### 2.3 Falende tests

Wanneer een Pest test faalt wordt:
1. De Pest-output **integraal overgenomen** in het US-testplan onder "Resultaten".
2. De **foutmelding** + stacktrace vastgelegd.
3. De oorzaak **gediagnosticeerd** (code-bug, test-bug, acceptance-criterium te strikt/te los).
4. De fix **terug-getest** — test moet groen worden voordat de commit door kan.

### 2.4 Screenshots & proof-of-work

Per US:
- **Werkende functionaliteit** — screenshot van de feature in de browser
- **Pest terminal output** — screenshot van `./vendor/bin/pest --filter=...` met groene vinkjes
- **Falende test (indien van toepassing)** — screenshot van de foutmelding

Worden opgeslagen in `docs/screenshots/us<nn>-<naam>/`.

## 3. Verplichte alternatieve scenario's per US

Voor **elke** user story worden minimaal deze alternatieve paden getest (naar gelang van toepassing):

| Categorie | Voorbeeld-test |
|---|---|
| **Leeg formulier** | Verplichte velden leeg → validatie-fout + behoudt andere invoer |
| **Ongeldige invoer** | Fout e-mailformaat, negatieve getallen, te lange tekst |
| **Ongeautoriseerde gebruiker** | Guest probeert auth-route → redirect `/login` |
| **Verkeerde rol** | Zorgbegeleider probeert teamleider-route → 403 |
| **Bestaat niet** | 404 op `/clients/9999` bij onbestaande resource |
| **Dubbele waarde** | Unieke constraint (bijv. e-mail) wordt afgedwongen |
| **State-transitie ongeldig** | Uren goedkeuren vanuit concept-status → afgewezen |

## 4. Uitvoering & rapportage

### Commando's

```bash
# Alle tests
./vendor/bin/pest

# Eén US
./vendor/bin/pest tests/Feature/US-01.php

# Coverage (indien xdebug/pcov actief)
./vendor/bin/pest --coverage
```

### Samenvattend rapport

In het **examenverslag** (apart document, niet in deze repo) komt:
- Overzicht: 16 US's × aantal tests per US × pass/fail
- Per US: link naar testplan + screenshots
- Eindconclusie over test-dekking

## 5. Tools & conventies

- **Assertions**: bij voorkeur Pest's `expect(...)` boven PHPUnit's `$this->assertSomething`.
- **Test-namen** in het Engels, beschrijvend: `it('logs in an active zorgbegeleider and redirects to /dashboard')`.
- **Lange tests** opsplitsen — max ~30 regels per `it()`-blok.
- **`beforeEach`** gebruiken voor gedeelde setup (bijv. `$this->team = Team::factory()->create()`).
- **Test-data isolatie** via `RefreshDatabase` — geen test mag leaken naar een andere.

## 6. Per-US testplannen

- [US-01 Inloggen](./US01-inloggen.md) ✅ — 10 Pest tests, 37 asserts
- [US-02 Rolgebaseerde toegang (Policies + middleware)](./US02-rolgebaseerde-toegang.md) ✅ — 26 Pest tests, 54 asserts
- [US-03 Nieuwe zorgbegeleider aanmaken](./US03-medewerker-aanmaken.md) ✅ — 15 Pest tests, 53 asserts
- [US-04 Medewerkersoverzicht met zoek en filter](./US04-medewerkers-overzicht.md) ✅ — 19 Pest tests, 61 asserts
- [US-05 Teamlid bewerken (rol + dienstverband)](./US05-teamlid-bewerken.md) ✅ — 16 Pest tests, 54 asserts
- [US-06 Teamlid deactiveren en heractiveren](./US06-teamlid-deactiveren.md) ✅ — 19 Pest tests, 62 asserts
- [US-07 Cliënt aanmaken met persoonsgegevens](./US07-client-aanmaken.md) ✅ — 21 Pest tests, 75 asserts
- [US-08 Cliënten koppelen aan begeleiders (primair/secundair/tertiair)](./US08-caregivers-koppeling.md) ✅ — 29 Pest tests, 70 asserts
- US-09 t/m US-16 — komen

**Sprint 1 afgerond** — 72 tests, 207 asserts, Pint clean.
**Sprint 2 afgerond** — US-05 ✓ · US-06 ✓ · US-07 ✓ · US-08 ✓ (157 tests · 468 asserts totaal)

---

*Laatst bijgewerkt: {{ laatste-commit }} — dit document volgt de groei van het project.*
