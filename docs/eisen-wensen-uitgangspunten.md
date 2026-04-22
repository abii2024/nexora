# Eisen, wensen en technische uitgangspunten — Nexora

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Auteur:** Abdisamad (abii2024)
> **Examen:** PvB Software Developer Niveau 4 (14–25 april 2026)
> **Versie:** 1.0 — 22 april 2026

---

## 1. Projectcontext

Nexora is een web-applicatie waarmee een organisatie voor beschermd wonen haar **cliëntdossiers**, **medewerkersregister** en **urenregistratie** digitaal beheert. De applicatie ondersteunt twee rollen: **zorgbegeleiders** (uitvoerend) en **teamleiders** (beherend/goedkeurend). Het systeem vervangt losse Excel-bestanden en papieren lijsten en zorgt dat zorgtijd, cliëntkoppelingen en teamwijzigingen centraal en auditeerbaar worden vastgelegd.

**Doel:** een minimaal werkbare versie (MVP) opleveren binnen de twee sprintweken van het PvB-examen, met volledige dekking van de 16 user stories uit de sprint backlog.

## 2. Stakeholders

| Rol | Belang | Betrokkenheid |
|---|---|---|
| **Zorgbegeleider** (primaire eindgebruiker) | Eenvoudig uren registreren, snel eigen cliëntenlijst zien | Dagelijkse gebruiker |
| **Teamleider** (secundaire eindgebruiker) | Team beheren, uren goedkeuren, cliëntdossiers opbouwen | Dagelijkse gebruiker + goedkeuringsflow |
| **Organisatie / directie** | Auditeerbaarheid, AVG-compliance, continuïteit | Opdrachtgever |
| **Cliënt** (indirect, géén gebruiker) | Recht op dataminimalisatie en correcte verwerking | Data-subject (AVG) |
| **Examinator (Techniek College / Praktijkleren)** | Beoordeling PvB Software Developer Niveau 4 | Reviewer |

---

## 3. Eisen (MoSCoW)

De eisen zijn geprioriteerd volgens **MoSCoW**. Alle *Must-have*-items zijn 1-op-1 gedekt door de 16 user stories in de sprint backlog ([docs/user-stories.md](user-stories.md)).

### 3.1 Must-have (MVP — alle 16 user stories)

| # | Eis | User story |
|---|---|---|
| E01 | Gebruikers kunnen inloggen met e-mail + wachtwoord, met rol-gebaseerde redirect | US-01 |
| E02 | Rolgebaseerde autorisatie via Policies + middleware; zorgbegeleiders zien alleen eigen cliënten | US-02 |
| E03 | Teamleider kan nieuwe zorgbegeleiders aanmaken | US-03 |
| E04 | Medewerkersoverzicht met zoek + filter (rol, status) | US-04 |
| E05 | Teamlid bewerken (naam, e-mail, rol, dienstverband) | US-05 |
| E06 | Teamlid deactiveren + heractiveren (behoud van historische data) | US-06 |
| E07 | Cliënt aanmaken met persoonsgegevens + zorgtype (WMO/WLZ/JW) | US-07 |
| E08 | Cliënten koppelen aan zorgbegeleiders met rol (primair/secundair/tertiair) | US-08 |
| E09 | Cliëntenoverzicht met rol-gebaseerde weergave en zoek/filter | US-09 |
| E10 | Cliënt bewerken, status wijzigen en archiveren (soft delete) | US-10 |
| E11 | Concept-uren aanmaken en bewerken per cliënt | US-11 |
| E12 | Uren indienen, terugtrekken, afgekeurde uren corrigeren en opnieuw indienen | US-12 |
| E13 | Teamleider kan ingediende uren goedkeuren of met reden afkeuren | US-13 |
| E14 | Urenoverzicht teamleider met filters op status, medewerker, week | US-14 |
| E15 | Wachtwoord-reset via e-maillink (token 60 min geldig) | US-15 |
| E16 | Eigen profiel + wachtwoord wijzigen | US-16 |

### 3.2 Should-have

- **S01** — E-mailnotificaties bij ingediende uren (nu database-channel; later Mail-channel).
- **S02** — Audit-log van wie-wanneer wat muteerde op cliëntdossier (wettelijke dossierplicht).
- **S03** — Export van urenoverzicht naar CSV/Excel voor salarisadministratie.
- **S04** — Dashboard-widgets met kerncijfers (aantal actieve cliënten, openstaande uren, etc.).

### 3.3 Could-have

- **C01** — Bulk-goedkeuring van uren per medewerker/week.
- **C02** — Zoekfilter op cliëntnaam binnen het urenformulier (type-ahead).
- **C03** — Donker/licht-themaschakelaar.
- **C04** — Meertaligheid (NL + EN + Arabisch).

### 3.4 Won't-have (buiten MVP-scope)

- **W01** — Mobiele native app (alleen mobiel-responsieve web).
- **W02** — Integratie met externe salarispakketten (Visma, AFAS, etc.).
- **W03** — Koppeling met gemeentelijke SUWInet of ZorgMail.
- **W04** — Cliënt-portaal waar cliënten zelf kunnen inloggen.

---

## 4. Wensen (voor vervolg-sprints, niet in MVP)

- Kalenderweergave voor uren (naast tabel).
- Tweestapsverificatie (2FA) voor teamleider-accounts.
- Bewaartermijn-automatisering: automatisch archiveren 20 jaar na laatste contact (Wgbo-norm).
- Shift-planning / rooster-module.
- Incidenten-registratie (MIC/MIM-meldingen).
- Rapportage-module met grafieken per cliënt/team/periode.

---

## 5. Technische uitgangspunten

### 5.1 Backend

| Onderdeel | Keuze | Onderbouwing |
|---|---|---|
| Taal | **PHP 8.4** | Strikte types, readonly properties, constructor promotion |
| Framework | **Laravel 12** | Bewezen ecosysteem, sterke auth/ORM/validatie, past binnen doorlooptijd |
| Structuur | Laravel 11+ streamlined (`bootstrap/app.php`) | Modernste conventie, minder boilerplate |
| ORM | Eloquent | N+1-preventie via eager loading, relatiemethoden, type hints |
| Validatie | Form Request classes (één per route) | Scheiden van controllerlogica, herbruikbare regels |
| Autorisatie | Policies + middleware (`EnsureTeamleider`, `EnsureZorgbegeleider`) | Single source of truth per resource; afdwingen in service-laag |
| Queues | `ShouldQueue` voor notificaties | Niet-blokkerend bij zware acties (geen blokkerende e-mails) |

### 5.2 Database

| Onderdeel | Keuze | Onderbouwing |
|---|---|---|
| Engine (dev) | **SQLite** | Geen externe server nodig tijdens examen; file-based = eenvoudig back-up + reset |
| Engine (prod-ready) | **MySQL** (of PostgreSQL) via `DB_CONNECTION` | Zelfde migraties werken cross-engine |
| Migraties | Timestamped Laravel-migraties | Reproduceerbaar schema; volgorde gegarandeerd |
| Soft delete | `clients` via `SoftDeletes`-trait | Historische cliëntdata behouden voor 20 jaar (Wgbo) zonder actief overzicht te vervuilen |
| Pivot | `client_caregivers(client_id, user_id, role)` | Ondersteunt meerdere begeleiders met rolverdeling zonder extra tabel |

### 5.3 Frontend

| Onderdeel | Keuze | Onderbouwing |
|---|---|---|
| Template-engine | **Blade** (server-rendered) | Past bij Laravel-standaard; geen SPA-complexiteit binnen 2 weken |
| CSS-framework | **Tailwind CSS v4** | Utility-first; consistent zonder eigen CSS-architectuur |
| Bundler | Vite | Snelle HMR in dev; productie-build via `npm run build` |
| Iconen | Inline SVG / Heroicons | Geen externe CDN, geen FOUC |
| Responsiveness | Mobile-first met Tailwind breakpoints (`sm:`, `md:`, `lg:`) | Wireframes bestaan in 1440×900 en 375×812 varianten |

### 5.4 Kwaliteit & tests

| Onderdeel | Keuze | Onderbouwing |
|---|---|---|
| Test-framework | **Pest 4** | Leesbare syntax, browser-testing, datasets |
| Test-strategie | Feature tests > unit tests (Laravel-idiomatisch) | HTTP-in → DB-out geeft hoogste vertrouwen |
| Coverage-doel | Alle user stories: happy path + 1 faal-pad | Minimale Definition of Done |
| Code style | **Laravel Pint** (`vendor/bin/pint`) | PSR-12 + Laravel-preset, geautomatiseerd |
| Static analysis | (optioneel) PHPStan bij uitloop | Geen blocker voor MVP |

### 5.5 Tooling & infrastructuur

| Onderdeel | Keuze | Onderbouwing |
|---|---|---|
| Versiebeheer | **Git + GitHub** (`abii2024/nexora`) | Publieke repo, zichtbaar voor examinator |
| Branching | Direct op `main` (solo-project, geen reviewer) | PR-flow is overhead zonder team |
| Lokaal draaien | **Laravel Herd** (`nexora.test`) | Zero-config dev-server, HTTPS out-of-the-box |
| IDE | VS Code + Laravel Boost MCP + Claude Code | Productiviteit via AI-assistentie |
| Documentatie | Markdown in `docs/` van de repo | Alles on-platform; examinator hoeft niets te installeren |
| Diagrammen | Mermaid (ERD, flowchart) + PlantUML (use-case) via [kroki.io](https://kroki.io) | Source-controlled (`.mmd`/`.puml`), automatisch renderbaar |

### 5.6 Beveiliging

Zie [ontwerpdocument/beveiliging.md](ontwerpdocument/beveiliging.md) voor uitwerking. Kern:

- HTTPS verplicht in productie.
- Wachtwoorden bcrypt via `Hash::make()`.
- CSRF-tokens op alle forms (Blade `@csrf`).
- Eloquent ORM voorkomt SQL-injectie; geen ruwe `DB::raw()`.
- Blade-escape (`{{ $var }}`) voorkomt XSS.
- Autorisatie-checks in controller **en** service-laag (defense in depth).
- Secrets in `.env` (niet in Git); `APP_KEY` per omgeving.

### 5.7 Privacy & ethiek

Zie [ontwerpdocument/gegevensbescherming.md](ontwerpdocument/gegevensbescherming.md) en [ontwerpdocument/verantwoorde-verwerking.md](ontwerpdocument/verantwoorde-verwerking.md). Kern:

- **Dataminimalisatie**: alleen BSN + geboortedatum + contact opslaan — geen medische diagnose of behandelplan in deze MVP.
- **Role-based access**: zorgbegeleiders zien alleen eigen cliënten (afgedwongen in query-scope).
- **Audit-trail**: created_at, updated_at, deleted_at op alle tabellen.
- **Bewaartermijn**: `clients` soft-deleted in plaats van hard-deleted ter ondersteuning van Wgbo 20-jaar dossierplicht.
- **Geen surveillance**: uren zijn zelfrapportage, geen locatietracking of geforceerde check-ins.

---

## 6. Niet-functionele eisen

| Categorie | Eis |
|---|---|
| **Performance** | Listpagina's (cliënten / uren / team) laden < 500 ms bij 100 rijen op localhost |
| **Schaalbaarheid** | Paginatie (20/pagina) op alle lijstweergaven; geen full-table-scans door database indexen op `user_id`, `client_id`, `status` |
| **Onderhoudbaarheid** | Single Responsibility per controller/service; geen business-logica in Blade-templates |
| **Bruikbaarheid** | Wireframes vastgesteld vóór implementatie; mobiel-responsief; Nederlandstalige UI |
| **Betrouwbaarheid** | Feature-tests per user story; `composer run dev` start lokaal zonder handmatige stappen |
| **Auditeerbaarheid** | Elke mutatie heeft een `updated_at` + user-relatie; soft delete op cliënten |

---

## 7. Aannames

1. Examinator test op Laravel Herd (`nexora.test`) met SQLite-database — geen externe services vereist.
2. Eén organisatie per installatie (single-tenant); multi-tenant is buiten scope.
3. Zorgbegeleiders hebben een werkend e-mailadres voor wachtwoord-reset.
4. BSN wordt gevalideerd op formaat (9 cijfers, 11-proef), niet bevraagd bij SUWInet.
5. Teamleiders hebben één organisatie onder zich — geen hiërarchie van teamleiders.

---

## 8. Bronnen & verwijzingen

- [Trello-board met sprint backlog](https://trello.com/b/ILocXzfF/nexora-platform)
- [User stories (export)](user-stories.md)
- [Definition of Done](definition-of-done.md)
- [Wireframes desktop + mobiel](wireframes/)
- [ERD](erd-files/)
- [Use-case diagram](usecase-files/)
- [Urenregistratie workflow (flowchart)](flowchart-files/)
- [Onderbouwing ethiek, privacy & security](ontwerpdocument/)

---

**Einde document.**
