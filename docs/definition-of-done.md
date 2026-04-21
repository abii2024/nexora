# Definition of Done — Nexora PvB Software Developer Niveau 4

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Auteur:** Abdisamad (abii2024)
> **Examen:** PvB Software Developer Niveau 4
> **Versie:** 1.0 — 21 april 2026

---

## 1. Inleiding

Dit document beschrijft de **Definition of Done (DoD)** voor alle user stories in het Nexora-project. De DoD is het contract tussen ontwikkelaar en Product Owner (de PvB-examinator): een user story is **pas klaar** als aan **álle** criteria in dit document is voldaan. Gedeeltelijk klaar telt als "in progress", niet als "done".

De DoD wordt opgenomen in het ontwerpdocument en dient als referentie bij:
- Het schrijven en reviewen van code
- Het verplaatsen van een Trello-kaart naar kolom **Done**
- Het indienen van een pull request
- De definitieve examen-oplevering op Canvas

### Waarom een DoD?

- **Kwaliteitsborging**: elke story voldoet aan dezelfde meetbare standaard
- **Transparantie**: de examinator weet precies wat hij mag verwachten
- **Examendiscipline**: voorkomt "bijna-klaar" stories die onbetrouwbaar zijn
- **Traceerbaarheid**: werk is aantoonbaar aanwezig in code, tests, docs en screenshots

---

## 2. Algemene DoD (geldt voor élke user story)

Een user story is pas DONE als de volgende 10 punten **allemaal** zijn afgevinkt:

- [ ] **Functionaliteit werkt** zoals beschreven in de **acceptatiecriteria** op de Trello-kaart
- [ ] **Code staat op feature-branch** (`feature/{korte-naam}`) in GitHub
- [ ] **Pull Request geopend** via `gh pr create` met referentie naar de user story
- [ ] **PR is gemerged naar `main`** na self-review
- [ ] **Pest feature test(s)** dekken minstens 1 happy-path + 1 unhappy-path per AC
- [ ] **Testsuite groen**: `php artisan test --compact` toont 0 failures
- [ ] **Code-style schoon**: `vendor/bin/pint --dirty --format agent` meldt 0 wijzigingen
- [ ] **Handmatig getest in browser** per relevante rol (zorgbegeleider + teamleider)
- [ ] **Screenshot toegevoegd** in `docs/screenshots/features/` met datum in bestandsnaam
- [ ] **Trello-kaart** verplaatst naar kolom **Done** en gekoppelde checklists afgevinkt

---

## 3. Technische vereisten

### 3.1 Verplichte stack

| Laag | Technologie | Versie |
|---|---|---|
| Runtime | PHP | 8.4.x |
| Framework | Laravel | 12.x |
| Templating | Blade (geen Livewire/Vue/React/Inertia) | — |
| Styling | Tailwind CSS (utility classes direct in Blade) | v4.x |
| Database | SQLite (`database/database.sqlite`) | — |
| Testing | Pest | v4.x |
| Code-style | Laravel Pint | v1.x |

**Niet-toegestaan** (harde grens — buiten exam-scope):
- MySQL, PostgreSQL of andere RDBMS
- Livewire, Vue, React, Inertia, Alpine.js framework-gebruik
- Externe mail-services (Resend, Mailgun, SendGrid)
- Real-time WebSocket/channels (Pusher, Reverb)
- 2FA, API-laag (REST/GraphQL), Elasticsearch
- Mobile app, externe HIS/EHR-koppelingen

### 3.2 Architecturale patronen

- **MVC-patroon** — geen god-controllers; één controller per domein
- **Form Requests** — álle validatie via `php artisan make:request` (nooit `$request->validate([...])` inline)
- **Policies** — resource-autorisatie via `app/Policies/{Resource}Policy.php` en `$this->authorize()` in controller
- **Middleware** — rol-gates in `bootstrap/app.php` (Laravel 12-structuur, **niet** in `app/Http/Kernel.php`)
- **Services** — business-logic met meerdere stappen in `app/Services/{Resource}Service.php`
- **Eloquent** — alle queries via Eloquent / query builder; **geen** `DB::raw()` met user input
- **Named routes** — altijd `route('naam.actie')`, nooit hardcoded URL's in views of redirects
- **Casts** — via `casts()` method op het model (Laravel 11+), niet `$casts` property

### 3.3 Naamconventies

| Type | Conventie | Voorbeeld |
|---|---|---|
| Models | PascalCase | `Urenregistratie`, `ClientCaregiver` |
| Controllers | PascalCase + `Controller` suffix | `ClientController`, `TeamleiderUrenController` |
| Variabelen / methodes | camelCase | `$activeClients`, `approveUren()` |
| DB-kolommen | snake_case | `team_leader_id`, `submitted_at` |
| Form Requests | Pascal + `Request` suffix | `StoreClientRequest`, `AfkeurUrenRequest` |
| Policies | Pascal + `Policy` suffix | `ClientPolicy`, `UrenregistratiePolicy` |
| Views | `resources/views/{module}/{actie}.blade.php` | `clients/create.blade.php` |

---

## 4. Tests (verplicht per user story)

### 4.1 Minimumvereisten

- Minimaal **één Pest feature test per acceptatiecriterium**
- Per story **minstens één unhappy-path test** uit deze lijst:
  - `assertForbidden()` of `assertStatus(403)` voor ongeautoriseerde rol
  - `assertSessionHasErrors([...])` voor ongeldig Form Request
  - `assertRedirect(route('login'))` voor unauthenticated
  - `assertDatabaseMissing()` bij geweigerde mutatie
- **Factories** gebruikt voor testdata — nooit `User::create([...])` inline
- **Factory states** waar nuttig: `User::factory()->teamleider()->create()`
- **`RefreshDatabase` trait** op alle feature tests (via `tests/Pest.php` of per file)
- Tests zijn **idempotent** — draaien in willekeurige volgorde geven hetzelfde resultaat

### 4.2 Commando's

```bash
# Alle tests
php artisan test --compact

# Filter op één test
php artisan test --compact --filter=InloggenTest

# Met coverage (optioneel, niet vereist)
php artisan test --coverage --min=70
```

### 4.3 Testplan-documentatie

Per user story een testplan-bestand in `docs/testplan/`:

```
docs/testplan/
├── US01-inloggen.md
├── US02-rolgebaseerde-toegang.md
├── ...
└── US16-deactiveren.md
```

Elk bestand bevat een tabel:

| TS-ID | Scenario | Voorwaarde | Stappen | Verwacht resultaat | Status |
|---|---|---|---|---|---|
| TS01-01 | Succesvolle login | Actieve user, juiste credentials | 1. Open /login 2. Vul in 3. Submit | Redirect naar /dashboard | ✅ |

---

## 5. Code-style & kwaliteit

- **Pint schoon** vóór elke commit: `vendor/bin/pint --dirty --format agent`
- **Geen `--test`** — Pint mag wijzigingen aanbrengen, commit die mee
- **Geen `env()`** buiten `config/*.php` files — gebruik `config('app.naam')`
- **Casts en `$fillable`** ingevuld op elk Model
- **Return type hints** op alle methodes en functies
- **Type hints** op parameters
- **PHPDoc** alleen waar de signature het niet uitlegt (geen redundante docblocks)

---

## 6. Security & privacy (hard in code)

### 6.1 Verplicht per feature

- **Wachtwoord-hashing** via `Hash::make()` (bcrypt) — nooit plain, nooit MD5/SHA
- **CSRF-token** op elke `<form method="POST">` via `@csrf`
- **Blade `{{ }}`** voor alle output (auto-escape) — `{!! !!}` alleen met expliciete rationale
- **Mass-assignment** beschermd via `$fillable` whitelist op elk Model
- **Rol-scoped queries** — zorgbegeleider ziet uitsluitend eigen toegewezen cliënten:
  ```php
  $clients = Client::whereHas('caregivers', fn($q) =>
      $q->where('user_id', auth()->id())
  )->get();
  ```
- **BSN / geboortedatum / adres** — alleen opslaan wanneer noodzakelijk (dataminimalisatie AVG art. 5)
- **`.env`** staat in `.gitignore` — credentials nooit in repo

### 6.2 Verplicht per autorisatie-beslissing

- **Policy** voor resource-niveau autorisatie
- **Middleware** voor rol-niveau autorisatie (in `bootstrap/app.php`)
- **Beide samen** — niet één van twee (defense in depth)
- Elke 403 heeft een **Pest test** die dit bevestigt

---

## 7. Documentatie in `/docs/`

Per user story of per sprint bijwerken:

| Bestand | Trigger voor bijwerken |
|---|---|
| `docs/definition-of-done.md` | Alleen bij wijziging van DoD-beleid |
| `docs/ontwerpdocument.md` | Bij architectuur-keuzes of wireframe-wijzigingen |
| `docs/erd.md` + `erd.png` | Nieuwe tabel / relatie / FK |
| `docs/use-case-diagram.md` | Nieuwe rol-actie toegevoegd |
| `docs/flowchart-urenworkflow.md` | Wijziging van status-transitie |
| `docs/testplan/USxx-{naam}.md` | Per user story: testscenario's |
| `docs/testrapport.md` | Per sprint: resultaat per AC + screenshots |
| `docs/retrospective.md` | Einde sprint |
| `docs/verbetervoorstellen.md` | Bij elke observatie, met bron + MoSCoW |
| `docs/examenverslag.md` | Continu — index van alle sub-artefacten |

---

## 8. Screenshot-ritueel (dagelijks)

Screenshots opslaan met datum in bestandsnaam (bv. `2026-04-21_login-happy-path.png`):

| Wat | Locatie | Frequentie |
|---|---|---|
| Trello-scrumboard (na kolom-beweging) | `docs/screenshots/scrumboard/` | Na elke story-move |
| `php artisan test --compact` output | `docs/screenshots/tests/` | Bij nieuwe test-run |
| GitHub commit-historie + branches | `docs/screenshots/git/` | Minstens dagelijks |
| Werkende feature per rol in browser | `docs/screenshots/features/` | Per user story |

---

## 9. Git & Pull Request workflow

### 9.1 Branches

- **Nooit direct naar `main`** voor feature-werk
- Feature-branch-naam: `feature/{korte-naam}` (bv. `feature/uren-indienen`)
- Docs-branch: `docs/{onderwerp}` (bv. `docs/definition-of-done`)
- Fix-branch: `fix/{korte-naam}` (alleen bij bugfix buiten user-story-scope)

### 9.2 Commits

- **Meerdere commits per dag** — toont progressie
- Commit-message bevat **user-story-ID of naam** (bv. `feat(US01): login-form validatie`)
- **Geen** `wip`, `update`, `fix` als standalone message
- **Geen** `--no-verify`, **geen** `--amend` op reeds gepushte commits
- **Geen** `reset --hard` zonder expliciet verzoek

### 9.3 Pull Request

```bash
gh pr create --base main --title "feat(US01): login-form + tests" --body "..."
```

- Titel verwijst naar user-story-ID
- Body bevat korte test-plan (bulleted checklist)
- Self-review vóór merge (eigen diff nalopen)
- Geen force-push naar main — ooit

---

## 10. Demo & handmatige verificatie

Vóór een story naar **Done** mag:

1. **Start Herd**: `https://nexora.test` draait
2. **Fresh DB**: `php artisan migrate:fresh --seed` — verifieert dat seeder + migrations werken
3. **Login als zorgbegeleider**: happy-path doorlopen van de user story
4. **Login als teamleider**: happy-path doorlopen (indien rol-specifiek)
5. **Unhappy-path**: test minimaal één fout-scenario (foute input, verkeerde rol)
6. **Screenshot** maken en opslaan in `docs/screenshots/features/`

---

## 11. Out-of-scope — géén hacks om dit heen

De volgende zaken **mogen niet gebouwd worden** tijdens de sprint (maar kunnen wél als verbetervoorstel in `docs/verbetervoorstellen.md` met MoSCoW-prioriteit):

- Mobiele app
- Externe koppelingen (HIS/EHR, facturatie aan zorgverzekeraars)
- Videobellen / real-time chat
- 2FA (wel: sterke wachtwoordregels)
- Notificatiesysteem via e-mail/SMS (wel: Laravel database-channel notifications)
- API-laag (REST/GraphQL)
- Elasticsearch / externe zoekindex
- Auditlog-framework van derden (wel: eigen `*_audit_logs` tabel per resource)

Als de PO iets uit deze lijst vraagt → registreren in `verbetervoorstellen.md` met MoSCoW, **niet** bouwen.

---

## 12. DoD quick-reference checklist

Plak dit in elke PR-description of bij elke story-review:

```
Definition of Done:
[ ] Acceptatiecriteria op Trello afgevinkt
[ ] Feature-branch gemaakt + PR geopend
[ ] Pest tests groen (happy + unhappy)
[ ] php artisan test --compact = 0 failures
[ ] vendor/bin/pint --dirty --format agent = 0 wijzigingen
[ ] Policy + Middleware aanwezig (indien autorisatie)
[ ] Form Request gebruikt (geen inline validate)
[ ] Handmatig getest als zorgbegeleider én teamleider
[ ] Screenshot in docs/screenshots/features/
[ ] Testplan bijgewerkt in docs/testplan/USxx-{naam}.md
[ ] ERD/flowchart bijgewerkt (indien schema-wijziging)
[ ] Trello-kaart in kolom Done
```

---

## 13. Relatie met Trello-kaarten

Elke user story op het Trello-bord **Nexora-platform** heeft twee checklists:

1. **Acceptatiecriteria** (5 items) — functionele eisen per story
2. **Definition of Done** (verschillend aantal items per story) — technische eisen specifiek voor die story

De **story-specifieke DoD-checklist op Trello** is een subset van dit document, aangevuld met items die uniek zijn voor die story (bv. *"Migratie `create_client_caregivers_table`"* bij Story 6).

De **generieke DoD** in dit document geldt altijd, óók als een Trello-item het niet expliciet noemt.

### Match-regel

Bij twijfel geldt de strengste regel:
- Als Trello zegt "Pest test dekt AC1" en dit document zegt "Pest tests dekken alle AC + 1 unhappy-path" → volg **dit document**.
- Als dit document zegt "Pint schoon" en Trello noemt Pint niet → Pint is alsnog verplicht.

---

## 14. Versiebeheer van deze DoD

Wijzigingen aan deze DoD **tijdens de sprint** zijn alleen toegestaan als:
- Ze zijn besproken met de PO (of vastgelegd in `docs/examenverslag.md` sectie *"Overleg-afspraken"*)
- Ze alleen toevoegingen zijn (regels versoepelen tijdens sprint = rode vlag voor examinator)

Versiehistorie:

| Versie | Datum | Wijziging |
|---|---|---|
| 1.0 | 2026-04-21 | Initiële DoD opgesteld vóór start sprint |

---

**Einde Definition of Done.**
