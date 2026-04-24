# Projectverslag — Nexora

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Auteur:** Abdisamad (abii2024)
> **Examen:** PvB Software Developer Niveau 4 (14–25 april 2026)
> **Versie:** 1.5 — bijgewerkt tijdens sprint 3 (US-09 + US-10 + US-11 lokaal afgerond)

Dit document is het **levend procesverslag** van Nexora. Het beschrijft hoe het project is opgebouwd, welke keuzes zijn gemaakt, welke sprints zijn afgerond, wat daarin gebouwd is en wat nog volgt. Het wordt bij elke sprint-afronding bijgewerkt.

Voor de technische onderbouwing van keuzes (AVG, beveiliging, ethiek) zie [ontwerpdocument.md](ontwerpdocument.md) en de drie deeldocumenten in `ontwerpdocument/`. Voor de testaanpak zie [testplan/README.md](testplan/README.md).

---

## 1. Projectcontext & stakeholders

### Wat is Nexora?

Nexora is een **webapplicatie voor beschermd-wonen-zorgorganisaties** die zorgbegeleiders en teamleiders ondersteunt bij het dagelijks werk:

- **Cliëntdossiers** beheren (persoonsgegevens, zorgtype, status)
- **Begeleiders koppelen** aan cliënten (primair / secundair / tertiair)
- **Urenregistratie** per cliënt — van concept tot goedkeuring
- **Teambeheer** (medewerkers toevoegen, rol wijzigen, deactiveren)
- **Rolgebaseerde toegang** — zorgbegeleiders zien alleen eigen caseload

### Primaire stakeholders

| Stakeholder | Belang in de app |
|---|---|
| **Zorgbegeleider** | Eigen caseload inzien, uren registreren, overdracht voorbereiden |
| **Teamleider** | Team + cliëntbeheer, uren beoordelen, rapportages genereren |
| **Organisatie / beschermd-wonen-instelling** | AVG-compliant dossierbeheer, auditeerbaarheid, Wgbo-bewaartermijnen |
| **Toezichthouders (AP, IGJ)** | Traceerbaarheid via audit-logs, privacy-waarborgen |
| **Cliënt** | Indirecte belanghebbende — zijn data moet veilig, transparant en minimaal verwerkt worden |

### Wettelijke kaders

- **AVG (art. 5, 9, 30, 32)** — grondslag, bijzondere persoonsgegevens, verwerkingsregister, beveiligingsmaatregelen
- **Wgbo** — 20-jaar bewaartermijn medisch/zorg-dossier, recht op rectificatie
- **NEN 7510** — informatiebeveiliging in de zorg (als leidraad)

---

## 2. Architectuurkeuzes

| Laag | Technologie | Waarom |
|---|---|---|
| Backend-taal | **PHP 8.4** | Modern, via Laravel Herd lokaal eenvoudig |
| Webframework | **Laravel 12** | MVC, ingebakken security (CSRF, auth, policies), breed gedragen in NL |
| Database | **SQLite** (dev + examen) | Zero-config; productie-migratie naar PostgreSQL is triviaal |
| ORM | **Eloquent** | Mass-assignment protection via `$fillable`, relations, casts, query-builder |
| View-laag | **Blade** + **Tailwind CSS v4** | Server-rendered — minder complex dan SPA, sneller voor teamleider-toolkit |
| Design system | Ported uit `curava-platform` | Consistent visueel merk, hergebruikt in examenprojecten |
| Testframework | **Pest v4** + `pest-plugin-laravel` | Leesbaardere tests dan PHPUnit, zelfde backend |
| Code-style | **Laravel Pint** (PSR-12) | Afgedwongen per commit |
| Autorisatie | **Policies + Middleware** (defense in depth) | Policy per resource, middleware per rol-groep |

Alle keuzes staan technisch onderbouwd in [ontwerpdocument.md](ontwerpdocument.md).

---

## 3. Git-workflow

Het examen eist expliciet:
- Elke functionaliteit op een eigen **feature-branch**
- Samenvoegen via **pull requests**
- Meerdere commits per dag met duidelijke messages

### Vertaling naar de praktijk

- 1 user story = 1 feature-branch = 1 pull request
- Sprint-afronding = merge van alle 4 PRs + annotated git-**tag** (`sprint-1`, `sprint-2`, …)
- Commit-conventies:
  - `feat(<module>): ...` — nieuwe functionaliteit
  - `test(<module>): ...` — Pest tests
  - `docs(<module>): ...` — documentatie / testplannen
  - `chore: ...` — refactor / tooling
  - `style: apply pint PSR-12 formatting` — format-only
- **Never** `--force-push`, **never** `--amend` op gepushte commits
- PR-titels: `US-NN: <korte titel>`; PR-body linkt naar user-stories.md + testplan + commits

GitHub-repo: [abii2024/nexora](https://github.com/abii2024/nexora)

---

## 4. Sprintindeling

16 user stories → **4 sprints van 4 stories**, gegroepeerd per functioneel domein:

| Sprint | Stories | Domein |
|---|---|---|
| Sprint 1 | US-01 · US-02 · US-03 · US-04 | Auth + team basis |
| Sprint 2 | US-05 · US-06 · US-07 · US-08 | Team compleet + cliënt basis |
| Sprint 3 | US-09 · US-10 · US-11 · US-12 | Cliënt compleet + uren basis |
| Sprint 4 | US-13 · US-14 · US-15 · US-16 | Uren compleet + auth afronding |

---

## 5. Procesverloop

### 🏗 Pre-work — setup + design-port

- **`feature/setup`** (PR #1): Laravel 12-skeleton, SQLite, Pint, Tailwind v4, base layout, 403-errorpage
- **`chore/design-system-curava`** (PR #2): design tokens (mint primary, ink neutrals), Blade UI-components (`<x-ui.button>`, `<x-ui.card>`, `<x-ui.stats-card>`, `<x-ui.alert>`, `<x-ui.badge>`, `<x-ui.empty-state>`), sidebar + topbar, `layouts.app` + `layouts.auth`
- Resultaat: alle verdere US'en bouwen visueel consistent op deze basis

### 🧾 Sprint 1 — Auth + team basis (afgerond)

| US | Titel | PR | Pest tests | Asserts |
|---|---|---|---|---|
| US-01 | Inloggen op Nexora | [#3](https://github.com/abii2024/nexora/pull/3) | 10 | 37 |
| US-02 | Rolgebaseerde toegang (Policies + middleware) | [#4](https://github.com/abii2024/nexora/pull/4) | 26 | 54 |
| US-03 | Nieuwe zorgbegeleider aanmaken | [#5](https://github.com/abii2024/nexora/pull/5) | 15 | 53 |
| US-04 | Medewerkersoverzicht met zoek en filter | [#6](https://github.com/abii2024/nexora/pull/6) | 19 | 61 |
| **Subtotaal** | | 4 PRs | **70** | **205** |

**Kerntechnologieën geïntroduceerd in sprint 1:**
- `LoginController` met bcrypt, session-regeneratie, throttling (`RateLimiter`), user-enumeration-protection
- `EnsureTeamleider` + `EnsureZorgbegeleider` middleware
- `ClientPolicy` + `UserPolicy` + Laravel 12 auto-discovery
- `ClientService::scopedForUser` — single source of truth voor scope-regels
- Seeder met 3 test-users + 1 team
- Paginatie + filters in `TeamController::index`

**Tag:** [`sprint-1`](https://github.com/abii2024/nexora/tree/sprint-1)

### 👥 Sprint 2 — Team compleet + cliënt basis (afgerond)

| US | Titel | PR | Pest tests | Asserts |
|---|---|---|---|---|
| US-05 | Teamlid bewerken (rol + dienstverband) | [#7](https://github.com/abii2024/nexora/pull/7) | 16 | 54 |
| US-06 | Teamlid deactiveren en heractiveren | [#8](https://github.com/abii2024/nexora/pull/8) | 19 | 62 |
| US-07 | Cliënt aanmaken met persoonsgegevens | [#9](https://github.com/abii2024/nexora/pull/9) | 21 | 75 |
| US-08 | Cliënten koppelen aan begeleiders | [#10](https://github.com/abii2024/nexora/pull/10) | 29 | 70 |
| **Subtotaal** | | 4 PRs | **85** | **261** |

**Kerntechnologieën geïntroduceerd in sprint 2:**
- `user_audit_logs` tabel (AVG art. 30) — immutable log van elke veldwijziging
- `UserService::updateWithAudit` + self-demotion guard
- `CheckActiveUser` middleware — runtime sessie-invalidatie
- `clients` + `client_caregivers` productieklaar (was stub in US-02)
- **Partial unique indexes** (DB-level, raw SQL) voor max 1 primair + 1 secundair per cliënt
- `ClientService::computeCaregiverRoles` + `syncCaregivers` (delete-insert in transactie)
- `ClientCaregiverAssignedNotification` (database-only channel)
- Test-refactor: `tests/Feature/*Test.php` → `tests/Feature/US-XX.php` (één bestand per user story)

**Tag:** [`sprint-2`](https://github.com/abii2024/nexora/tree/sprint-2)

### 📋 Sprint 3 — Cliënt compleet + uren basis (bezig)

| US | Titel | PR | Pest tests | Asserts |
|---|---|---|---|---|
| US-09 | Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter | #11 | 27 | 67 |
| US-10 | Cliënt bewerken en archiveren (statusbeheer + soft delete) | — (lokaal, sprint-batch) | 31 | 74 |
| US-11 | Concept-uren aanmaken en bewerken | — (lokaal, sprint-batch) | 28 | 77 |
| US-12 | Uren indienen, terugtrekken en opnieuw indienen | — | — | — |

**Kerntechnologieën geïntroduceerd in sprint 3 (US-09 + US-10 + US-11):**
- `ClientService::getPaginated` met filter-whitelist (search / status / care_type / sort) + `->with(['caregivers', 'team'])` eager loading (US-09)
- Rol-specifieke view-branching in `clients/index.blade.php` — teamleider ziet tabel + totaal-banner, zorgbegeleider ziet kaart-grid + eigen-caseload-banner (US-09)
- Herbruikbare `<x-clients.filter-bar>` Blade-component met query-string-preservation via `withQueryString()` (US-09)
- Drie verschillende empty-states (filters-leeg / teamleider-leeg / zorgbeg-leeg) via `<x-ui.empty-state>` (US-09)
- N+1-regressie-test met `DB::listen` — harde bovengrens op aantal queries bij paginatie (US-09)
- `SoftDeletes`-trait + `deleted_at` kolom op `clients` (Wgbo-compliant archiveren, US-10)
- `client_status_logs` audit-tabel + `ClientStatusLog` model (immutable — `UPDATED_AT=null` zoals `UserAuditLog` uit US-05)
- `UpdateClientRequest` met `Rule::unique->ignore($id)->whereNull('deleted_at')` voor BSN (US-10)
- `ClientService::update/archive/restore` — `update()` logt status-diff alleen bij daadwerkelijke wijziging (US-10)
- Route-volgorde + `whereNumber('client')` om conflict tussen `/clients/archive` en `/clients/{id}` te voorkomen (US-10)
- Permanente verwijdering bewust UI-onbereikbaar: geen route + `forceDelete`-policy returnt false (dataverlies-preventie, US-10)
- `App\Enums\UrenStatus` — PHP 8.4 backed-string enum (Concept / Ingediend / Goedgekeurd / Afgekeurd) + helpers `label()`, `badgeTone()`, `isEditable()` (US-11)
- `urenregistraties` tabel — user_id / client_id / datum / starttijd / eindtijd / uren(decimal 5,2) / notities / status (US-11)
- `UrenregistratieService::computeDuration` — integer-seconds → decimaal (geen float-wobble) (US-11)
- `UrenregistratiePolicy`: `update()` vereist eigenaar + `status->isEditable()`; `delete()` altijd false (US-11)
- `user_id` + `status` buiten `$fillable` — altijd via `service->create($user, $payload)` (US-11)
- Status-tabs via URL `/uren?status=…` (shareable links, werkt zonder JS) (US-11)

### 🕐 Sprint 4 — Uren compleet + auth afronding (gepland)

| US | Titel | Scope-preview |
|---|---|---|
| US-13 | Uren goedkeuren of afkeuren als teamleider | `goedkeur`/`afkeur` state-transitions, verplichte afkeurreden |
| US-14 | Urenoverzicht met filters | 3 filters (status/medewerker/week) + samenvattende header |
| US-15 | Wachtwoord vergeten & resetten via e-maillink | `Password::sendResetLink`, token lifecycle, user-enumeration-protection |
| US-16 | Profielbeheer (eigen gegevens + wachtwoord) | `/profiel`, `current_password` rule, `logoutOtherDevices` |

---

## 6. Testen — huidige stand

**Framework:** Pest v4 met `RefreshDatabase` trait (SQLite in-memory).

**Totaal na US-10 (tijdens sprint 3):** 215 tests · 609 asserts · Duration ≈ 2,4s · **alle groen**.

### Examen-eisen testrapportage — dekking

Elk per-US testplan (`docs/testplan/US<NN>-*.md`) dekt de 6 verplichte elementen uit de examen-opdracht:

| # | Examen-eis | Plaats in US-testplan |
|---|---|---|
| 1 | Testplan: welke soort testen + hoe omgegaan | §1 + algemeen [README.md](testplan/) |
| 2 | Testscenario's met **verwachte + werkelijke** resultaten | §3 (TC-XX tabellen, 4-koloms) |
| 3 | Resultaten van de testen | §4 (Pest-output + handmatige TC-samenvatting + dekkingsmatrix) |
| 4 | Getrokken conclusies uit de testen | §5 (Functioneel / Privacy / Code kwaliteit / Openstaand / Eindoordeel) |
| 5 | Analyse van gebruikte informatiebronnen (testresultaten, feedback, retrospective, bugs) | §6 (tabel met 7 bronnen per US) |
| 6 | Interpretatie van bevindingen uit verschillende bronnen | §7 (4-6 genummerde verbindende inzichten) |

**Bronnen die momenteel beschikbaar zijn:** Pest-testoutput, eigen bug-meldingen tijdens development, Trello AC/DoD checkboxes, `user-stories.md`, ontwerpdocumenten, `eisen-wensen-uitgangspunten.md`.

**Bronnen die later beschikbaar worden:** presentatie-feedback (na sprint 4), retrospective-input (einde project). Deze worden op dat moment teruggevoegd aan elk testplan §6.

| US | Bestand | Tests | Asserts |
|---|---|---|---|
| US-01 | [tests/Feature/US-01.php](../tests/Feature/US-01.php) | 10 | 37 |
| US-02 | [tests/Feature/US-02.php](../tests/Feature/US-02.php) | 26 | 54 |
| US-03 | [tests/Feature/US-03.php](../tests/Feature/US-03.php) | 15 | 53 |
| US-04 | [tests/Feature/US-04.php](../tests/Feature/US-04.php) | 19 | 61 |
| US-05 | [tests/Feature/US-05.php](../tests/Feature/US-05.php) | 16 | 54 |
| US-06 | [tests/Feature/US-06.php](../tests/Feature/US-06.php) | 19 | 62 |
| US-07 | [tests/Feature/US-07.php](../tests/Feature/US-07.php) | 21 | 75 |
| US-08 | [tests/Feature/US-08.php](../tests/Feature/US-08.php) | 29 | 70 |
| US-09 | [tests/Feature/US-09.php](../tests/Feature/US-09.php) | 27 | 67 |
| US-10 | [tests/Feature/US-10.php](../tests/Feature/US-10.php) | 31 | 74 |
| Voorbeelden | tests/Feature/ExampleTest.php | 2 | 2 |
| **Totaal** | | **215** | **609** |

Per-US testscenario's + handmatige TC's staan in [docs/testplan/](testplan/). Screenshots-checklists staan in [docs/screenshots/](screenshots/) — deze worden gebundeld opgeleverd aan het einde van het project.

**Code-style:** Pint draait cleanshot bij elke feature-branch (afgedwongen pre-commit in workflow).

---

## 7. ERD & datamodel-overzicht

Zie [docs/erd-files/erd.mmd](erd-files/) voor de volledige Mermaid-ERD. Tabellen die inmiddels bestaan:

| Tabel | Sprint | Doel |
|---|---|---|
| `users` | 1 (US-01) | Login + rol + team + is_active |
| `teams` | 1 (US-01) | Teamindeling per organisatie |
| `clients` | 2 (US-02 stub, US-07 volledig, US-10 soft_deletes) | Cliëntdossier |
| `client_caregivers` | 2 (US-02 stub, US-08 volledig) | Pivot met role primair/secundair/tertiair |
| `user_audit_logs` | 2 (US-05) | Immutable log voor AVG art. 30 |
| `notifications` | 2 (US-08) | Laravel-default polymorphic notifications |
| `client_status_logs` | 3 (US-10) | Immutable log van statuswijzigingen (AVG art. 5) |

Nog te komen in sprint 3/4: `urenregistratie`, `client_status_logs`, `password_reset_tokens` (default Laravel).

---

## 8. Rol-matrix

| Functie | Gast | Zorgbegeleider | Teamleider |
|---|:---:|:---:|:---:|
| Inloggen | ✅ | ✅ | ✅ |
| Dashboard | ❌ | ✅ eigen | ✅ eigen (teamleider-variant) |
| `/team` medewerkers-overzicht | ❌ | ❌ | ✅ |
| Medewerker aanmaken | ❌ | ❌ | ✅ |
| Medewerker bewerken / deactiveren | ❌ | ❌ | ✅ |
| `/clients` overzicht | ❌ | ✅ eigen koppelingen | ✅ eigen team |
| Cliënt aanmaken | ❌ | ❌ | ✅ |
| Cliënt bewerken + archiveren (US-10) | ❌ | ❌ | ✅ |
| Gearchiveerd-overzicht + herstellen | ❌ | ❌ | ✅ |
| Begeleiders koppelen (US-08) | ❌ | ❌ | ✅ |
| Uren registreren (US-11) | ❌ | ✅ eigen | ✅ |
| Uren goedkeuren (US-13) | ❌ | ❌ | ✅ |
| Profiel wijzigen (US-16) | ❌ | ✅ eigen | ✅ eigen |

Defense in depth wordt op 3 lagen afgedwongen: **middleware** (route-groep) → **policy** (resource) → **service-guard** (business rule).

---

## 9. Documenten-index

| Type | Pad |
|---|---|
| Dit procesverslag | [`docs/projectverslag.md`](projectverslag.md) |
| Ontwerpdocument (keuzes-onderbouwing) | [`docs/ontwerpdocument.md`](ontwerpdocument.md) |
| Definition of Done | [`docs/definition-of-done.md`](definition-of-done.md) |
| User stories (16 stuks) | [`docs/user-stories.md`](user-stories.md) |
| Eisen, wensen & uitgangspunten | [`docs/eisen-wensen-uitgangspunten.md`](eisen-wensen-uitgangspunten.md) |
| Testplan-overzicht | [`docs/testplan/README.md`](testplan/) |
| Per-US testplannen | [`docs/testplan/US{01-08}-*.md`](testplan/) |
| Screenshots per US | [`docs/screenshots/us{01-08}-*/README.md`](screenshots/) |
| ERD | [`docs/erd-files/`](erd-files/) |
| Wireframes | [`docs/wireframes/`](wireframes/) |
| Flowcharts (urenworkflow) | [`docs/flowchart-files/`](flowchart-files/) |
| Use-case-diagrammen | [`docs/usecase-files/`](usecase-files/) |
| Sprint-backlog screenshots | [`docs/sprint-backlog-screenshots/`](sprint-backlog-screenshots/) |

---

## 10. Verbetervoorstellen (post-examen)

Zaken die **buiten scope** vallen van de 16 user stories maar waarvan Nexora zou profiteren in een vervolgversie:

| Nr | Voorstel | Waarom buiten scope | Prioriteit |
|---|---|---|---|
| 1 | E-mailverzending bij caregiver-koppeling (US-08) | Expliciet uit user-story, database-only |  Middel |
| 2 | E-mailnotificatie bij aanmaak nieuwe medewerker (US-03) | "Initieel wachtwoord buiten app" — expliciet out-of-scope | Middel |
| 3 | BSN-encryptie-at-rest | AVG art. 32 "passende maatregelen" — vereist key-management | Hoog (productie) |
| 4 | Audit-viewer UI op team-member edit-pagina | Nu alleen via tinker zichtbaar | Laag |
| 5 | Retentiebeleid `user_audit_logs` | 5-7 jaar wettelijk, nu geen auto-cleanup | Middel (productie) |
| 6 | Bell-icon met unread-notification-count in topbar | Topbar heeft icon, API nog niet gekoppeld | Laag |
| 7 | 2FA voor teamleider-accounts | NEN 7510 sterk aanbevolen | Middel |
| 8 | Automatische deployment (CI + Laravel Forge) | Nu alleen lokaal Herd | Middel |

---

## 11. Reflectie tussen sprints

**Wat goed ging tot nu toe:**
- Pest-first aanpak — elke AC een test vóór afronding → geen regressies in sprint 2
- Sprint-tags op GitHub — examenreviewer kan snapshots ophalen
- Commit-historie is doorzoekbaar (scoped prefixes + NL body)
- Design-system eerst porten bleek cruciaal: US-02 t/m US-08 hergebruiken de 7 UI-components zonder duplicatie

**Wat anders had gekund:**
- In US-02 was de `Client` + `client_caregivers` migratie al aangemaakt voor autorisatie-tests. Achteraf had ik misschien zuiverder een aparte "auth-infrastructure" story kunnen opzetten. De huidige opzet werkt maar de US-02 → US-07 → US-08 groei is verspreid over 3 PRs waar sommigen 1 gecombineerde PR hadden verwacht.
- `tests/Feature/Auth/LoginTest.php` → `tests/Feature/US-01.php` rename kwam pas in sprint 2 — had beter vanaf het begin gekund (4 renames gespaard).

**Afspraken die houden:**
- Screenshots + handmatige browser-tests in één batch aan het einde van alle 16 US's
- Geen `--force-push`, geen `--amend`
- Elke sprint eindigt met annotated git-tag + 4 merged PRs

---

**Laatst bijgewerkt:** einde sprint 2 — 2026-04-23.
**Volgende update:** na afronding sprint 3 (US-09 t/m US-12).
