# Nexora — zorgbegeleidingssysteem voor beschermd wonen

> **Kandidaat:** Abdisamad (`abii2024`)
> **PvB:** Software Developer Niveau 4
> **Project-type:** Laravel 12 web-applicatie (zorg-SaaS)
> **Status:** Compleet — 16 user stories afgerond + verbetervoorstel doorgevoerd, 4 sprint-tags op GitHub

---

## Voor de examinator

> **Hier is de complete leesgids met alle bewijslocaties:** [`docs/examen-checklist.md`](docs/examen-checklist.md)

Elke verplichte examen-eis (eisen-doc, user stories, wireframes, ERD, use-case, flowchart, testplan, verbetervoorstellen, reflectie, overleggen, screenshots, github-bewijslast) is daar gekoppeld aan het exacte bestand in deze repository.

---

## Wat is Nexora?

Een webapplicatie voor **beschermd-wonen-zorgorganisaties** die zorgbegeleiders en teamleiders ondersteunt:

- **Cliëntdossiers** beheren (persoonsgegevens, zorgtype, status)
- **Begeleiders koppelen** aan cliënten (primair / secundair / tertiair)
- **Urenregistratie** per cliënt — van concept tot goedkeuring
- **Teambeheer** (medewerkers toevoegen, rol wijzigen, deactiveren)
- **Rolgebaseerde toegang** — zorgbegeleiders zien alleen eigen caseload

Wettelijke kaders: **AVG** (art. 5, 9, 30, 32) · **Wgbo** (20-jaar bewaartermijn) · **NEN 7510** (informatiebeveiliging in de zorg).

---

## Tech-stack

| Laag | Technologie |
|---|---|
| Backend | PHP 8.4 · Laravel 12 |
| Database | SQLite (dev + examen) |
| ORM | Eloquent (mass-assignment protection) |
| View | Blade + Tailwind CSS v4 |
| Tests | Pest v4 + `pest-plugin-laravel` |
| Code-style | Laravel Pint (PSR-12) |
| Autorisatie | Policies + Middleware (defense in depth) |
| Mail (US-17) | Resend (`resend/resend-laravel`) |

---

## Lokaal draaien

```bash
git clone https://github.com/abii2024/nexora.git
cd nexora
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
# Open http://127.0.0.1:8000
```

### Testaccounts

Wachtwoord overal: `password`

| Rol | E-mail |
|---|---|
| Teamleider Fatima | `abdisamadvanabdulle@gmail.com` |
| Zorgbegeleider Jeroen | `zorgbegeleider@nexora.test` |
| Inactieve Ilse | `inactief@nexora.test` |

Volledige seed-context: [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php).

### Tests draaien

```bash
php artisan test                  # 360 tests, 953 asserts — alle groen
php artisan test --filter US-08   # per US filteren
```

---

## Sprint-snapshots

| Tag | URL | Snapshot van |
|---|---|---|
| `sprint-1` | <https://github.com/abii2024/nexora/tree/sprint-1> | Einde Sprint 1 (US-01..04) |
| `sprint-2` | <https://github.com/abii2024/nexora/tree/sprint-2> | Einde Sprint 2 (US-05..08) |
| `sprint-3` | <https://github.com/abii2024/nexora/tree/sprint-3> | Einde Sprint 3 (US-09..12) |
| `sprint-4` | <https://github.com/abii2024/nexora/tree/sprint-4> | Einde Sprint 4 (US-13..16) |

---

## Documentatie-overzicht

| Onderwerp | Locatie |
|---|---|
| **Examen-leesgids (alle eisen → bewijs)** | [`docs/examen-checklist.md`](docs/examen-checklist.md) |
| Procesverslag (sprints, keuzes, testresultaten) | [`docs/projectverslag.md`](docs/projectverslag.md) |
| Eisen, wensen, technische uitgangspunten | [`docs/eisen-wensen-uitgangspunten.md`](docs/eisen-wensen-uitgangspunten.md) |
| User stories (16 US's) | [`docs/user-stories.md`](docs/user-stories.md) |
| Definition of Done | [`docs/definition-of-done.md`](docs/definition-of-done.md) |
| Ontwerp (ethiek · privacy · security) | [`docs/ontwerpdocument.md`](docs/ontwerpdocument.md) + [`docs/ontwerpdocument/`](docs/ontwerpdocument/) |
| Testplan + testscenario's per US | [`docs/testplan/`](docs/testplan/) |
| Uitgewerkte functionaliteiten (per US) | [`docs/uitgewerkte-functionaliteiten/`](docs/uitgewerkte-functionaliteiten/) |
| Code-bewijslast (GitHub-permalinks per US) | [`docs/code-bewijslast/`](docs/code-bewijslast/) |
| GitHub-bewijslast (PRs, branches, tags) | [`docs/github-bewijslast/`](docs/github-bewijslast/) |
| Reflectie per sprint | [`docs/reflectie/`](docs/reflectie/) |
| Overleggen met PO | [`docs/overleggen/`](docs/overleggen/) |
| Wireframes (desktop + mobiel) | [`docs/wireframes/`](docs/wireframes/) |
| ERD · Use-case · Flowchart | [`docs/erd-files/`](docs/erd-files/) · [`docs/usecase-files/`](docs/usecase-files/) · [`docs/flowchart-files/`](docs/flowchart-files/) |
| Examen-logboek (getekend) | [`docs/logboek/getekend-logboek.pdf`](docs/logboek/getekend-logboek.pdf) |
| Examen-presentatie | [`docs/presentatie/nexora-examen-presentatie.pptx`](docs/presentatie/nexora-examen-presentatie.pptx) |
