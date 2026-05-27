# Examen-checklist — leesgids voor de examinator

> **Project:** Nexora — zorgbegeleidingssysteem voor beschermd wonen
> **Kandidaat:** Abdisamad (`abii2024`)
> **PvB:** Software Developer Niveau 4
> **Inleverdatum:** 2026-05-26
> **Repository:** <https://github.com/abii2024/nexora>

Dit document is een **leesgids**: elk verplicht onderdeel uit het examenverslag (zoals genoemd in de PvB-instructies) is hieronder gekoppeld aan de exacte locatie van het bewijs in de repository. Klik op de link om direct naar de bron te navigeren.

---

## A. Eisen, ontwerp en planning

| # | Examen-eis | Bewijs (locatie in repo) |
|---|---|---|
| 1 | Eisen, wensen en technische uitgangspunten | [`docs/eisen-wensen-uitgangspunten.md`](eisen-wensen-uitgangspunten.md) |
| 2 | User stories met acceptatiecriteria | [`docs/user-stories.md`](user-stories.md) — 16 US's |
| 3 | Definition of Done | [`docs/definition-of-done.md`](definition-of-done.md) |
| 4 | Screenshot **begin** sprint backlog (prio: hoogste boven, laagste onder) | [`docs/sprint-backlog-screenshots/begin-sprint/`](sprint-backlog-screenshots/begin-sprint/) — 5 PNG's |
| 5 | Screenshots scrumboard na elke update | [`docs/sprint-backlog-screenshots/`](sprint-backlog-screenshots/) (per sprint — `begin-sprint/`, `sprint1/`, `sprint 2/`, `sprint 3/`) + [`docs/overleggen/02-trello-activiteitenlog-po-review-batch2.png`](overleggen/02-trello-activiteitenlog-po-review-batch2.png) (PO verplaatst Sprint 4-kaarten US-13..16 naar *done* — dit dekt de eindstaat na Sprint 4) |
| 6 | Wireframes van alle pagina's (desktop + mobiel) | [`docs/wireframes/desktop/`](wireframes/desktop/) + [`docs/wireframes/mobile/`](wireframes/mobile/) — 32 PNG's totaal |
| 7 | ERD databasestructuur | [`docs/erd-files/erd.png`](erd-files/erd.png) + [`erd.mmd`](erd-files/erd.mmd) (source) |
| 8 | Use-case diagram zorgbegeleider + teamleider | [`docs/usecase-files/usecase.png`](usecase-files/usecase.png) + [`usecase.puml`](usecase-files/usecase.puml) |
| 9 | Flowchart urenregistratie | [`docs/flowchart-files/urenregistratie-workflow.png`](flowchart-files/urenregistratie-workflow.png) + `.mmd` |
| 10 | Onderbouwing keuzes user stories | Inline in elke [`docs/uitgewerkte-functionaliteiten/usNN-*/README.md`](uitgewerkte-functionaliteiten/) + [`docs/code-bewijslast/README.md`](code-bewijslast/) (per US "Waarom deze code-keuze") |
| 11 | Onderbouwing ethiek / privacy / security | [`docs/ontwerpdocument.md`](ontwerpdocument.md) + drie deeldocumenten: [ethiek](ontwerpdocument/verantwoorde-verwerking.md) · [privacy](ontwerpdocument/gegevensbescherming.md) · [security](ontwerpdocument/beveiliging.md) |
| 12 | Lijst van user stories | [`docs/user-stories.md`](user-stories.md) |

---

## B. Uitgewerkte functionaliteiten + bewijslast

| # | Examen-eis | Bewijs |
|---|---|---|
| 13 | Screenshots van uitgewerkte functionaliteiten met beschrijving | [`docs/uitgewerkte-functionaliteiten/usNN-*/`](uitgewerkte-functionaliteiten/) — 16 mappen + [`opdracht-4-verbetervoorstellen/`](uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/) (US-17). Per US een README met screenshots + checklist. Werkscript: [`docs/screenshot-instructies.md`](screenshot-instructies.md). |
| 14 | Screenshots van code van uitgewerkte functionaliteiten | [`docs/code-bewijslast/README.md`](code-bewijslast/README.md) — per US code-fragmenten met **GitHub-permalinks** naar specifieke regelnummers in de live repo. Geen PNG-screenshots vereist: de live links **zijn** het bewijs (klikbaar en niet-manipuleerbaar). |
| 15 | Screenshots commit-geschiedenis en branches op GitHub | [`docs/github-bewijslast/README.md`](github-bewijslast/README.md) — alle 18 PRs, 4 sprint-tags, 4 sprint-branches + 18 historische feature-branches, commit-statistieken, ASCII git-graph. **Geen PNG-screenshots vereist**: alle data inline + 7 live GitHub-URLs voor realtime verificatie (PRs · branches · tags · commits · network · contributors · root). Elke claim is reproduceerbaar via een `gh api` / `git log` commando. |

---

## C. Testen

| # | Examen-eis | Bewijs |
|---|---|---|
| 16 | Testplan (welke testsoorten + hoe ermee omgegaan) | [`docs/testplan/README.md`](testplan/README.md) — Pest feature-tests, unit-tests, handmatige browser-tests, regressie-tests |
| 17 | Testscenario's per user story (verwacht + werkelijk) | [`docs/testplan/US-NN.md`](testplan/) — 16 bestanden met TC-XX-tabellen |
| 18 | Resultaten van de testen | [`docs/testplan/README.md`](testplan/README.md) §6 — **360 Pest-tests / 953 asserts — allemaal groen**. Per sprint: 70/85/117/86. Per US: zie [`projectverslag.md`](projectverslag.md) §5 PR-tabellen |
| 19 | Getrokken conclusies uit de testen | Per US §5 in [`docs/testplan/US-NN.md`](testplan/) (Functioneel / Privacy / Code quality / Eindoordeel PASS) |

---

## D. Verbetervoorstellen (B1-K1-W5)

| # | Examen-eis | Bewijs |
|---|---|---|
| 20 | Analyse gebruikte informatiebronnen (testresultaten, PO-feedback, retrospective) | [`docs/uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md`](uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md) §1 |
| 21 | Interpretatie bevindingen | opdracht-4-README §2 |
| 22 | Beschreven verbetervoorstellen | opdracht-4-README §3 (3 voorstellen: VV-1 t/m VV-3) |
| 23 | Nieuwe/aangepaste US in product backlog met tijdsinschatting + prioriteit | opdracht-4-README §4 — **US-17 (Resend, 4 uur, Should have)** |
| 24 | Screenshot product backlog met nieuwe US zichtbaar | opdracht-4-README §6 (schermafbeelding 2: US-17 op Trello-backlog) |

**Status implementatie:** US-17 is **doorgevoerd** in dezelfde sessie — `resend/resend-laravel` geïnstalleerd, `MAIL_MAILER=resend`, mail aantoonbaar aangekomen in Gmail-inbox (opdracht-4-README §6 schermafbeelding 3).

---

## E. Overleg (B1-K2-W1)

| # | Examen-eis | Bewijs |
|---|---|---|
| 25 | Screenshots scrumboard na elke update (afspraken zichtbaar) | [`docs/overleggen/README.md`](overleggen/README.md) — 3 screenshots Trello-activiteitenlog + [`docs/sprint-backlog-screenshots/`](sprint-backlog-screenshots/) per sprint |
| 26 | Andere afspraken (niet op scrumboard) | [`overleggen/README.md`](overleggen/README.md) §2 + [`projectverslag.md`](projectverslag.md) §11 + [`definition-of-done.md`](definition-of-done.md) |
| 27 | Overzicht uitgevoerde activiteiten obv afspraken | [`overleggen/README.md`](overleggen/README.md) §4 (tabel afspraak → actie → bewijs) |

---

## F. Reflectie (B1-K2-W3)

| # | Examen-eis | Bewijs |
|---|---|---|
| 28 | Positieve punten + verbeterpunten voor het **proces** | [`docs/reflectie/README.md`](reflectie/README.md) §2-5 per sprint (Proces +/−/→) + §6 overkoepelend |
| 29 | Positieve punten + verbeterpunten voor de **samenwerking** met PO | reflectie-README §2-5 (Samenwerking met PO +/−/→) + §6 |
| 30 | Positieve punten + verbeterpunten voor de **eigen prestaties** | reflectie-README §2-5 (Eigen prestaties +/−/→) + §6 |

---

## G. Procesoverzicht — projectverslag

Het centrale procesverslag is [`docs/projectverslag.md`](projectverslag.md). Inhoud:

- **§1** Projectcontext + stakeholders (zorgorganisatie, AVG, Wgbo, NEN 7510)
- **§2** Architectuurkeuzes (Laravel 12, SQLite → PostgreSQL, Pest, Tailwind v4)
- **§3** Git-workflow (feature-branches, PRs, sprint-tags)
- **§4** Sprintindeling
- **§5** Procesverloop — **alle 4 sprints + pre-work + US-17**, per sprint US-tabel met PR-nummers, test-aantallen, kerntechnologieën
- **§6** Testen — huidige stand (360/953 alle groen) + per-sprint-subtotalen
- **§7** ERD & datamodel-overzicht
- **§8** Rol-matrix
- **§9** Documenten-index
- **§10** Verbetervoorstellen — incl. §10.1 (opdracht 4), §10.2 (opdracht 5), §10.3 (opdracht 7)
- **§11** Reflectie (pointer naar `docs/reflectie/`)

---

## H. Aanvullende documentatie

| Onderwerp | Document |
|---|---|
| README van het project | [`README.md`](../README.md) |
| Drie deeldocumenten ontwerp | [`docs/ontwerpdocument/`](ontwerpdocument/) — verantwoorde verwerking (ethiek) · gegevensbescherming (privacy/AVG) · beveiliging (security/OWASP/NEN 7510) |
| Documenten-index | [`docs/projectverslag.md`](projectverslag.md) §9 |
| Logboek (handgeschreven, getekend) | [`docs/logboek/getekend logbook.pdf`](logboek/getekend%20logbook.pdf) |
| Examen-presentatie | [`docs/presentatie/nexora-examen-presentatie.pptx`](presentatie/nexora-examen-presentatie.pptx) |
| Observatieformulier (getekend) | [`docs/observatieformulier/getekend.docx`](observatieformulier/getekend.docx) |

---

## I. Reproduceerbaarheid

Wil de examinator de software lokaal draaien?

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

**Testaccounts** (wachtwoord overal: `password`):

| Rol | E-mail |
|---|---|
| Teamleider Fatima | `abdisamadvanabdulle@gmail.com` |
| Zorgbegeleider Jeroen | `zorgbegeleider@nexora.test` |
| Inactieve Ilse | `inactief@nexora.test` |

Volledige seed-context staat in [`database/seeders/DatabaseSeeder.php`](https://github.com/abii2024/nexora/blob/main/database/seeders/DatabaseSeeder.php).

Tests draaien:
```bash
php artisan test         # 360 tests, 953 asserts
php artisan test --filter US-08   # per US filteren
```

---

## J. Sprint-snapshots (reproduceerbaarheid per sprint)

| Tag | URL | Snapshot van |
|---|---|---|
| `sprint-1` | <https://github.com/abii2024/nexora/tree/sprint-1> | Einde Sprint 1 (US-01..04) |
| `sprint-2` | <https://github.com/abii2024/nexora/tree/sprint-2> | Einde Sprint 2 (US-05..08) |
| `sprint-3` | <https://github.com/abii2024/nexora/tree/sprint-3> | Einde Sprint 3 (US-09..12) |
| `sprint-4` | <https://github.com/abii2024/nexora/tree/sprint-4> | Einde Sprint 4 (US-13..16) — final feature-staat |

`git checkout sprint-N` of via GitHub-UI op het tag-overzicht.
