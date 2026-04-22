---
name: nexora-us
description: Bouw een Nexora user story (US-01 t/m US-16) volledig automatisch — feature-branch, code, commits, PR, merge. Gebruik bij `/nexora-us <nummer>` of als gebruiker zegt "bouw US-NN".
---

# Nexora US builder

Bouwt één user story uit [docs/user-stories.md](../../../docs/user-stories.md) volledig geautomatiseerd volgens examen-conventies.

## Examen-conventies (verplicht)

**Branch naming** (strikt — 1 branch per US):

| US | Branch |
|---|---|
| 01 | `feature/authenticatie` |
| 02 | `feature/autorisatie` |
| 03 | `feature/medewerker-aanmaken` |
| 04 | `feature/medewerkers-overzicht` |
| 05 | `feature/teamlid-bewerken` |
| 06 | `feature/teamlid-deactiveren` |
| 07 | `feature/client-aanmaken` |
| 08 | `feature/client-begeleiders-koppelen` |
| 09 | `feature/clienten-overzicht` |
| 10 | `feature/client-bewerken-archiveren` |
| 11 | `feature/uren-concept` |
| 12 | `feature/uren-indienen` |
| 13 | `feature/uren-goedkeuren` |
| 14 | `feature/uren-overzicht-teamleider` |
| 15 | `feature/wachtwoord-reset` |
| 16 | `feature/profiel` |

**Code conventions** (uit examenopdracht):
- PHP 8.4, Laravel 12, afgedwongen met Laravel Pint (PSR-12)
- Blade templates + curava design system (zie `resources/css/tokens.css` + `resources/views/components/ui/*` en `layout/*`)
- SQLite
- Eloquent ORM, Form Requests voor validatie, Policies voor autorisatie
- PascalCase voor Models/Controllers, camelCase variabelen/methoden, snake_case DB-kolommen
- Controllers in `app/Http/Controllers/`, Models in `app/Models/`
- Blade per module: `resources/views/{module}/`
- **Alle UI via bestaande components** — `<x-ui.button>`, `<x-ui.input>`, `<x-ui.card>`, `<x-ui.stats-card>`, `<x-ui.alert>`, `<x-ui.badge>`, `<x-ui.empty-state>`, `<x-layout.icon>`. Geen eigen CSS-classes tenzij echt nodig.
- Page headers via `.page-header` / `.page-title` / `.page-subtitle` / `.page-actions`
- Nederlandse user-facing copy (labels, foutmeldingen, flash messages)

**Commit stijl** (meerdere commits per dag, duidelijke messages):
- `feat(auth): ...`, `feat(team): ...`, `feat(clients): ...`, `feat(uren): ...`
- `fix(...): ...`, `refactor(...): ...`, `test(...): ...`, `chore(...): ...`
- Meerdere kleine commits per US (migration → model → controller → views → policy → tests → pint)

**PR stijl**:
- Titel: `US-NN: <korte titel>`
- Body: linkt naar docs/user-stories.md#us-nn, bullets met wat gedaan is
- Merge met `gh pr merge --squash --delete-branch` NIET — gewoon `--merge` zodat commit-historie zichtbaar blijft

## Workflow per US

1. **Lees user story**
   - `docs/user-stories.md` → zoek naar `## US-NN`
   - Lees alle 5 bullets (Omschrijving) + Privacy/Security/Ethiek sectie

2. **Checkout main + pull + nieuwe branch**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/<naam>
   ```

3. **Bouw** in deze volgorde (elke stap = eigen commit):
   - Migration(s) → `feat(<module>): add <table> migration`
   - Model(s) + relaties + casts → `feat(<module>): add <Model> model`
   - Form Request(s) → `feat(<module>): add <Name>Request validation`
   - Policy/Policies → `feat(<module>): add <Name>Policy authorization`
   - Service class (indien nodig) → `feat(<module>): add <Name>Service`
   - Controller(s) + routes → `feat(<module>): wire <name> routes and controller`
   - Blade views (gebruikt `<x-ui.*>` + `<x-layout.icon>`) → `feat(<module>): add <name> views`
   - Middleware (indien nodig) → `feat(<module>): add <name> middleware`
   - Notification (indien nodig) → `feat(<module>): add <Name>Notification`
   - Feature test (Pest) → `test(<module>): cover <name> ACs + alternatieve scenario's`
   - **Testplan + screenshots-map** → `docs(<module>): add US-NN testplan + screenshots README`
   - Pint run → `style: apply pint PSR-12 formatting`

### 3a. Verplichte test-dekking (examen-eis)

**Per US minimaal:**
- 1 Pest test per acceptatiecriterium (AC-1, AC-2, ...)
- 1 Pest test per Privacy/Security bullet die testbaar is
- Alternatieve scenario's (verplicht indien van toepassing):
  - Lege verplichte velden → validatie-fout
  - Ongeldige invoer (verkeerd e-mailformaat, te lange string, niet-bestaande ID)
  - Ongeautoriseerde gebruiker (guest probeert auth-route)
  - Verkeerde rol (zorgbegeleider op teamleider-route → 403)
  - Onbestaande resource → 404
  - State-transitie ongeldig (waar relevant)
  - Dubbele waarde (unique constraint)

### 3b. Verplicht testplan-document

`docs/testplan/US<NN>-<naam>.md` moet deze 5 secties hebben (volg [US-01 template](../../docs/testplan/US01-inloggen.md)):

1. **Soorten testen uitgevoerd** — tabel met Pest + handmatig + aantal
2. **Test-gebruikers / test-data** — tabel
3. **Handmatige testscenario's** — TC-XX tabellen met 4 kolommen:
   `Stap | Actie | Verwacht resultaat | Werkelijk resultaat` + Pest-dekking referentie
4. **Resultaten van de testen** — integrale Pest-output als code-block + TC-samenvatting + dekkingsmatrix
5. **Conclusies** — Functioneel / Privacy & security / Code kwaliteit / Openstaand / Eindoordeel

### 3c. Screenshots-map

`docs/screenshots/us<nn>-<naam>/README.md` met checklist van vereiste screenshots (werkende functionaliteit + Pest terminal-output).

4. **Run checks**
   ```bash
   php artisan migrate --force
   vendor/bin/pint
   php artisan route:list --except-vendor | grep <module>
   php artisan test --filter <Test>
   ```

5. **Push + PR + merge**
   ```bash
   git push -u origin feature/<naam>
   gh pr create --base main --title "US-NN: <titel>" --body "<body>"
   gh pr merge --merge --delete-branch
   git checkout main && git pull
   ```

6. **Meld terug**: "US-NN klaar, PR #X merged. Branch verwijderd."
   Na US-06/US-10/US-14 extra: "Functionaliteit <naam> compleet — screenshots tijd."

## Regels

- **Niet vragen**, gewoon bouwen. Requirements staan in user-stories.md.
- **Niet over-engineeren**: wat de US zegt, niks meer. Geen "nice to haves".
- **Altijd Policies + Form Requests gebruiken** — examen eist dit expliciet.
- **Elke US bouwt voort op vorige** — volgorde matters (setup → 01 → 02 → ...).
- **Bij conflicten met main**: rebase op main, fix, force-push branch.
- **Pint is laatste commit** — code altijd formatted voor merge.
- **Flash messages in Nederlands** ("Cliënt aangemaakt.", "Teamlid bijgewerkt.", etc.)
- **403-pagina in Nederlands**, geen stacktrace.
- **Nooit `--amend` op al gepushte commits**.
- **Geen `--no-verify`**.
