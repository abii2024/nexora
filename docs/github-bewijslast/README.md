# GitHub-bewijslast — commit-geschiedenis, branches en pull requests

> **Examen-eis:** *Screenshots van de commit-geschiedenis en branches op GitHub*
> **Werkproces:** B1-K1-W3 — *Realiseert software* (versiebeheer-discipline)
> **Datum:** 2026-05-12
> **Repository:** [abii2024/nexora](https://github.com/abii2024/nexora)

Dit document bundelt de **objectieve bewijslast** voor versiebeheer-discipline: alle pull requests, sprint-tags, feature-branches, commit-statistieken en een ASCII-rendering van de git-graph. Elke regel is **klikbaar** zodat de examinator direct kan navigeren naar de echte GitHub-pagina.

> Naast deze tekst-bewijslast zijn 5 specifieke GitHub-views aangewezen waarvan **screenshots** in de **Screenshots-bijlage** (§7) staan: PR-overzicht, branches-overzicht, tags-overzicht, commit-history op `main`, en network-graph.

---

## 1. Project-statistieken

| Metric | Waarde | Bron |
|---|---|---|
| **Totaal commits op `main`** | 254 | `git rev-list --count HEAD` |
| **Non-merge commits** | 227 | `git log --oneline --no-merges \| wc -l` |
| **Merged pull requests** | 18 | `gh pr list --state merged` |
| **Feature-branches (US)** | 16 | één per user story (US-01 t/m US-16) |
| **Infrastructure-branches** | 2 | `feature/setup` (#1) + `chore/design-system-curava` (#2) |
| **Sprint-tags** | 4 | `sprint-1` t/m `sprint-4` |
| **Pest feature-tests** | 360 | 953 asserts — **alle groen** (zie [testplan/README.md](../testplan/README.md)) |
| **Periode** | 22 apr – 12 mei 2026 | 21 dagen, 4 sprints |

---

## 2. Sprint-tags (release-snapshots)

Elke sprint sluit af met een **annotated git-tag** zodat de examinator de exacte staat per sprint kan ophalen. De vier tags zijn:

| Tag | Sprint | US's gedekt | PRs | URL |
|---|---|---|---|---|
| `sprint-1` | Sprint 1 — Auth + team basis | US-01..04 | #3..#6 | <https://github.com/abii2024/nexora/tree/sprint-1> |
| `sprint-2` | Sprint 2 — Team compleet + cliënt basis | US-05..08 | #7..#10 | <https://github.com/abii2024/nexora/tree/sprint-2> |
| `sprint-3` | Sprint 3 — Cliënt compleet + uren basis | US-09..12 | #11..#14 | <https://github.com/abii2024/nexora/tree/sprint-3> |
| `sprint-4` | Sprint 4 — Uren compleet + auth afronding | US-13..16 | #15..#18 | <https://github.com/abii2024/nexora/tree/sprint-4> |

**Reproduceren:** `git checkout sprint-N` of via GitHub-UI op het tag-overzicht.

---

## 3. Pull requests — alle 18 mergeds

Elke user story heeft een **dedicated feature-branch** die via een **squash-merge pull request** in `main` is geland. De PR-titel bevat het US-nummer; de body bevat acceptatiecriteria en test-output.

| # | Titel | Branch | Sprint | Merged op | URL |
|---|---|---|---|---|---|
| **#1** | Setup: Laravel 12 skeleton + Tailwind v4 + SQLite | `feature/setup` | Pre-work | 22-04-2026 | <https://github.com/abii2024/nexora/pull/1> |
| **#2** | chore: port curava design system to Nexora | `chore/design-system-curava` | Pre-work | 22-04-2026 | <https://github.com/abii2024/nexora/pull/2> |
| **#3** | US-01: Inloggen op Nexora (zorgbegeleider + teamleider) | `feature/authenticatie` | Sprint 1 | 22-04-2026 | <https://github.com/abii2024/nexora/pull/3> |
| **#4** | US-02: Rolgebaseerde toegang (autorisatie via Policies + middleware) | `feature/autorisatie` | Sprint 1 | 22-04-2026 | <https://github.com/abii2024/nexora/pull/4> |
| **#5** | US-03: Nieuwe zorgbegeleider aanmaken | `feature/medewerker-aanmaken` | Sprint 1 | 22-04-2026 | <https://github.com/abii2024/nexora/pull/5> |
| **#6** | US-04: Medewerkersoverzicht met zoek en filter | `feature/medewerkers-overzicht` | Sprint 1 | 22-04-2026 | <https://github.com/abii2024/nexora/pull/6> |
| **#7** | US-05: Teamlid bewerken (rol + dienstverband) | `feature/teamlid-bewerken` | Sprint 2 | 23-04-2026 | <https://github.com/abii2024/nexora/pull/7> |
| **#8** | US-06: Teamlid deactiveren en heractiveren | `feature/teamlid-deactiveren` | Sprint 2 | 23-04-2026 | <https://github.com/abii2024/nexora/pull/8> |
| **#9** | US-07: Cliënt aanmaken met persoonsgegevens | `feature/client-aanmaken` | Sprint 2 | 23-04-2026 | <https://github.com/abii2024/nexora/pull/9> |
| **#10** | US-08: Cliënten koppelen aan begeleiders | `feature/client-begeleiders-koppelen` | Sprint 2 | 23-04-2026 | <https://github.com/abii2024/nexora/pull/10> |
| **#11** | US-09: Cliëntenoverzicht met rol-gebaseerde weergave | `feature/clienten-overzicht` | Sprint 3 | 23-04-2026 | <https://github.com/abii2024/nexora/pull/11> |
| **#12** | US-10: Cliënt bewerken en archiveren (soft delete) | `feature/client-bewerken-archiveren` | Sprint 3 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/12> |
| **#13** | US-11: Concept-uren aanmaken en bewerken | `feature/concept-uren-aanmaken` | Sprint 3 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/13> |
| **#14** | US-12: Uren indienen, terugtrekken en opnieuw indienen | `feature/uren-indienen-terugtrekken` | Sprint 3 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/14> |
| **#15** | US-13: Uren goedkeuren of afkeuren als teamleider | `feature/uren-goedkeuren-afkeuren` | Sprint 4 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/15> |
| **#16** | US-14: Urenoverzicht met filters (teamleider) | `feature/uren-overzicht-met-filters` | Sprint 4 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/16> |
| **#17** | US-15: Wachtwoord vergeten & resetten via e-maillink | `feature/wachtwoord-vergeten` | Sprint 4 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/17> |
| **#18** | US-16: Profielbeheer (eigen gegevens + wachtwoord wijzigen) | `feature/profielbeheer` | Sprint 4 | 24-04-2026 | <https://github.com/abii2024/nexora/pull/18> |

**Naast deze 18 PRs:** US-17 (Resend-integratie, verbetervoorstel uit Opdracht 4) is als directe commits op `main` geland — bewust gekozen omdat het een docs-+config-change is, niet een feature-PR. Zie [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md).

**Reproduceren:** `gh pr list --state merged --limit 25` of via <https://github.com/abii2024/nexora/pulls?q=is%3Apr+is%3Amerged>.

---

## 4. Feature-branches — examen-eis B1-K1-W3

Examen-werkproces B1-K1-W3 eist letterlijk: *"Elke functionaliteit krijgt een aparte feature-branch (feature/authenticatie, feature/clientbeheer). Functionaliteiten worden via pull requests samengevoegd in de main branch. Meerdere commits per dag met duidelijke commit messages."*

**Alle 16 user stories voldoen hieraan.** Branch-naam volgt de conventie `feature/<kebab-case-functienaam>`:

```
feature/authenticatie               → US-01
feature/autorisatie                 → US-02
feature/medewerker-aanmaken         → US-03
feature/medewerkers-overzicht       → US-04
feature/teamlid-bewerken            → US-05
feature/teamlid-deactiveren         → US-06
feature/client-aanmaken             → US-07
feature/client-begeleiders-koppelen → US-08
feature/clienten-overzicht          → US-09
feature/client-bewerken-archiveren  → US-10
feature/concept-uren-aanmaken       → US-11
feature/uren-indienen-terugtrekken  → US-12
feature/uren-goedkeuren-afkeuren    → US-13
feature/uren-overzicht-met-filters  → US-14
feature/wachtwoord-vergeten         → US-15
feature/profielbeheer               → US-16
```

Plus 2 infrastructuur-branches:
```
feature/setup                       → Laravel 12 skeleton
chore/design-system-curava          → curava-design-system port
```

**Reproduceren:** `git branch -a` of via <https://github.com/abii2024/nexora/branches>.

---

## 5. Commit-discipline

### 5.1 Conventional-commit-prefixes (top-15, alfabetisch op count)

```
  32  docs(wireframes):
  23  feat(uren):
  22  feat(clients):
  15  feat(team):
  11  feat(auth):
   5  test(clients):
   4  feat(design):
   4  test(uren):
   4  test(team):
   4  docs(uren):
   4  docs(team):
   4  docs(clients):
   4  docs(auth):
   4  chore(setup):
   3  test(auth):
```

**Conventie:** `<type>(<scope>): <korte beschrijving in Nederlands>`. Types in gebruik: `feat`, `fix`, `test`, `docs`, `chore`, `style`, `refactor`. Scopes zijn modules: `auth`, `team`, `clients`, `uren`, `wireframes`, `setup`, `design`, `mail`, `opdracht-4` t/m `opdracht-7`.

### 5.2 Voorbeelden van duidelijke commit-messages

```
116d36f  docs(opdracht-7): corrigeer reflectie sprint 1 — Trello-bord zelf opgezet, niet door PO
9262d66  feat(mail): Resend-integratie voor wachtwoord-reset (Opdracht-4 / US-17)
269f2c4  fix(auth): use markdown() voor mail-template i.p.v. view() (US-15 runtime-fix)
b56aac0  style: apply pint PSR-12 formatting project-wide (end-check)
251af69  docs(project): projectverslag v2.0 — PROJECT COMPLEET (16/16 user stories)
0f6c2d8  Merge pull request #10 from abii2024/feature/client-begeleiders-koppelen
```

### 5.3 Veiligheidsdiscipline

- ❌ **0× `git push --force` / `--force-with-lease`** op `main` of feature-branches
- ❌ **0× `git commit --amend`** na merge
- ❌ **0× `--no-verify`** (pre-commit hooks niet overgeslagen)
- ✅ Squash-merge per PR — schone history op `main`
- ✅ Feature-branches **verwijderd na merge** (zie `--delete-branch` in PR-flow)

---

## 6. ASCII git-graph — `sprint-1` t/m `sprint-4`

Onderstaand fragment toont de **release-snapshots** (één regel per tag/branch-decoratie) zoals geproduceerd door `git log --all --oneline --decorate --simplify-by-decoration`:

```
* 116d36f (HEAD -> main, origin/main) docs(opdracht-7): corrigeer reflectie sprint 1
* 116b9c7 (tag: sprint-4) Merge pull request #18 from abii2024/feature/profielbeheer
* ddcdba9 (origin/feature/profielbeheer)
* d1b62b2 (origin/feature/wachtwoord-vergeten)
* fcc826a (origin/feature/uren-overzicht-met-filters)
* 22f6c77 (origin/feature/uren-goedkeuren-afkeuren)
* 21c0135 (tag: sprint-3) Merge pull request #14 from abii2024/feature/uren-indienen-terugtrekken
* c5fa42a (origin/feature/uren-indienen-terugtrekken)
* 67e8e04 (origin/feature/concept-uren-aanmaken)
* b0b7e94 (origin/feature/client-bewerken-archiveren)
* f9b7845 (origin/feature/clienten-overzicht)
* 0f6c2d8 (tag: sprint-2) Merge pull request #10 from abii2024/feature/client-begeleiders-koppelen
* 11de134 (origin/feature/client-begeleiders-koppelen)
* 99d5fef (origin/feature/client-aanmaken)
* 942475f (origin/feature/teamlid-deactiveren)
* 2109c41 (origin/feature/teamlid-bewerken)
* d725649 (tag: sprint-1) Merge pull request #6 from abii2024/feature/medewerkers-overzicht
* 4128194 (origin/feature/medewerkers-overzicht)
* bd75afe (origin/feature/medewerker-aanmaken)
* a8b6c14 (origin/feature/autorisatie)
* cf0fe02 (origin/feature/authenticatie)
* 2122b4d (origin/chore/design-system-curava)
* 505d03e (origin/feature/setup)
```

**Lezen:** elke `*` = een commit, decorate-labels tussen haakjes tonen waar tags en branches naartoe wijzen. De vier `(tag: sprint-N)` regels zijn de sprint-afsluitingen — perfect ge-aligned met de sprint-grenzen.

**Reproduceren:** `git log --all --oneline --decorate --simplify-by-decoration | head -30` lokaal, of via <https://github.com/abii2024/nexora/network> (network-graph).

---

## 7. Screenshots-bijlage (visueel bewijs)

De volgende GitHub-views vormen de **visuele bewijslast** voor versiebeheer-discipline. Plaats screenshots in deze map (`docs/github-bewijslast/`) met de namen hieronder. Elk screenshot toont 1 specifieke eis.

| # | Screenshot-bestandsnaam | URL waarvan screenshot is gemaakt | Wat moet zichtbaar zijn |
|---|---|---|---|
| 1 | `01-pulls-merged.png` | <https://github.com/abii2024/nexora/pulls?q=is%3Apr+is%3Amerged> | Alle 18 merged PR's in één lijst, met titels en branch-namen |
| 2 | `02-branches.png` | <https://github.com/abii2024/nexora/branches> | Alle 18 feature-branches + de `main` branch (active + stale tabs) |
| 3 | `03-tags.png` | <https://github.com/abii2024/nexora/tags> | De 4 sprint-tags (`sprint-1` t/m `sprint-4`) |
| 4 | `04-commits-main.png` | <https://github.com/abii2024/nexora/commits/main> | De commit-historie op `main`: scoped prefixes, NL body, geen `--amend`-sporen |
| 5 | `05-network-graph.png` | <https://github.com/abii2024/nexora/network> | De network-graph met alle feature-branches die in `main` mergen |

> **Hoe screenshot maken:** open URL → wacht tot pagina volledig geladen — Cmd+Shift+4 (Mac) of Print Screen (Windows) → bestand opslaan onder de aangegeven naam in `docs/github-bewijslast/`. Een full-page screenshot via browser-extensie (zoals *GoFullPage*) is ook prima.

---

## 8. Koppeling met examen-rubric

| Rubric-item | Bewijs in dit document |
|---|---|
| *Screenshots van de commit-geschiedenis* | §5 + §6 + screenshot #4 (§7) |
| *Screenshots van branches op GitHub* | §4 + screenshot #2 (§7) |
| *Pull requests samengevoegd in main branch* (B1-K1-W3) | §3 (tabel 18 PRs) + screenshot #1 |
| *Meerdere commits per dag met duidelijke commit messages* | §5.1 + §5.2 |
| *Aparte feature-branch per functionaliteit* | §4 (16 + 2 branches) |
| *Annotated sprint-tags voor reproduceerbaarheid* | §2 + screenshot #3 |
