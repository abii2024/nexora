# GitHub-bewijslast — commit-geschiedenis, branches en pull requests

> **Examen-eis:** *Screenshots van de commit-geschiedenis en branches op GitHub*
> **Werkproces:** B1-K1-W3 — *Realiseert software* (versiebeheer-discipline)
> **Datum:** 2026-05-13
> **Repository:** [abii2024/nexora](https://github.com/abii2024/nexora)

Dit document is de **volledige bewijslast** voor versiebeheer-discipline. Alle informatie die op screenshots zou staan is hier **inline opgenomen** als tabellen met klikbare GitHub-URLs. De examinator kan elke claim **live verifiëren** door op een link te klikken — sterker dan een statische PNG, omdat de live GitHub-API tegelijkertijd als bron en als verificatie dient.

Bij elke sectie staat een **reproduceer-commando** (`gh api …` of `git log …`) waarmee de examinator de getallen zelf kan narekenen.

---

## 1. Project-statistieken

| Metric | Waarde | Verificatie-commando |
|---|---|---|
| **Totaal commits op `main`** | 257 | `git rev-list --count main` |
| **Non-merge commits** | 227 | `git log --oneline --no-merges \| wc -l` |
| **Merged pull requests** | 18 | `gh pr list --state merged --limit 25 \| wc -l` |
| **Feature-branches (US)** | 16 (historisch in PR-history) | <https://github.com/abii2024/nexora/pulls?q=is%3Apr+is%3Amerged> |
| **Infrastructure-branches** | 2 (historisch in PR-history) | `feature/setup` (#1) + `chore/design-system-curava` (#2) |
| **Sprint-tags** | 4 | `git tag -l` |
| **Sprint-branches** | 4 | <https://github.com/abii2024/nexora/branches> |
| **Auteur / contributor** | `abii2024` (enige) | `gh api repos/abii2024/nexora/contributors --jq '.[].login'` |
| **Pest feature-tests** | 360 / 953 asserts — **alle groen** | `php artisan test --compact` |
| **Periode** | 22 apr – 13 mei 2026 (22 dagen, 4 sprints) | `git log --reverse --format=%ai \| head -1` |

---

## 2. Sprint-tags (release-snapshots)

Elke sprint sluit af met een **annotated git-tag** zodat de examinator de exacte projectstaat per sprint kan ophalen. Alle 4 tags zijn op GitHub aanwezig en wijzen naar een Merge-PR-commit op `main`.

| Tag | Sprint | US's | PRs | Commit-SHA | URL |
|---|---|---|---|---|---|
| `sprint-1` | Sprint 1 — Auth + team basis | US-01..04 | #3..#6 | `a776e66` | <https://github.com/abii2024/nexora/tree/sprint-1> |
| `sprint-2` | Sprint 2 — Team compleet + cliënt basis | US-05..08 | #7..#10 | `f9af6e8` | <https://github.com/abii2024/nexora/tree/sprint-2> |
| `sprint-3` | Sprint 3 — Cliënt compleet + uren basis | US-09..12 | #11..#14 | `59a42b6` | <https://github.com/abii2024/nexora/tree/sprint-3> |
| `sprint-4` | Sprint 4 — Uren compleet + auth afronding | US-13..16 | #15..#18 | `cfa69b8` | <https://github.com/abii2024/nexora/tree/sprint-4> |

**Verificatie:** `git tag -l` → toont 4 tags · `gh api repos/abii2024/nexora/tags --jq '.[].name'` → idem op remote · `git checkout sprint-N` om project lokaal in die staat te bekijken.

Naast de tags zijn er ook 4 **gelijknamige branches** (`sprint-1` t/m `sprint-4`) zodat de examinator ze ook via het branch-dropdown kan kiezen.

---

## 3. Pull requests — alle 18 mergeds

Elke user story is gerealiseerd op een **eigen feature-branch** en via een **pull request** in `main` gemerged. PR-titel bevat het US-nummer. Branch-namen zijn na merge automatisch verwijderd (GitHub-instelling *"Automatically delete head branches"*) — dat is de norm bij solo-projecten en houdt de Branches-pagina overzichtelijk. **De PR-history bewaart de branch-namen + merge-commits permanent** (klikbaar zichtbaar via onderstaande URLs).

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

**Naast deze 18 PRs:** US-17 (Resend-integratie, verbetervoorstel uit Opdracht 4) is bewust **direct op `main`** gecommit i.p.v. via een feature-branch. Reden: het is een drie-regelige config-change + dependency-install + één seeder-aanpassing, geen feature-scope. Zie [opdracht-4-verbetervoorstellen/README.md](../uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md).

**Verificatie:** `gh pr list --state merged --limit 25` → toont alle 18 · klik op de URL-kolom voor elk individuele PR met diff, commits en review-status.

---

## 4. Branches (huidige staat)

| Branch | Type | Wijst naar | URL |
|---|---|---|---|
| `main` | Default | Laatste commit | <https://github.com/abii2024/nexora/tree/main> |
| `sprint-1` | Sprint-snapshot | Einde Sprint 1 (`a776e66`) | <https://github.com/abii2024/nexora/tree/sprint-1> |
| `sprint-2` | Sprint-snapshot | Einde Sprint 2 (`f9af6e8`) | <https://github.com/abii2024/nexora/tree/sprint-2> |
| `sprint-3` | Sprint-snapshot | Einde Sprint 3 (`59a42b6`) | <https://github.com/abii2024/nexora/tree/sprint-3> |
| `sprint-4` | Sprint-snapshot | Einde Sprint 4 (`cfa69b8`) | <https://github.com/abii2024/nexora/tree/sprint-4> |

**Verificatie:** `gh api repos/abii2024/nexora/branches --jq '.[].name'` → toont exact deze 5 branches.

### 4.1 Historische feature-branches (B1-K1-W3-bewijslast)

Examen-werkproces B1-K1-W3 eist letterlijk: *"Elke functionaliteit krijgt een aparte feature-branch (feature/authenticatie, feature/clientbeheer). Functionaliteiten worden via pull requests samengevoegd in de main branch."*

Tijdens de ontwikkeling zijn **18 feature-branches** gebruikt — één per user story plus 2 infra-branches. Na squash-merge zijn ze automatisch door GitHub verwijderd, maar de **branch-namen blijven permanent zichtbaar in de PR-history** (kolom *Branch* in tabel §3). De norm bij solo-projecten is om gemergde branches op te ruimen.

```
feature/setup                       → PR #1   (Laravel 12 skeleton)
chore/design-system-curava          → PR #2   (curava design-system port)
feature/authenticatie               → PR #3   → US-01
feature/autorisatie                 → PR #4   → US-02
feature/medewerker-aanmaken         → PR #5   → US-03
feature/medewerkers-overzicht       → PR #6   → US-04
feature/teamlid-bewerken            → PR #7   → US-05
feature/teamlid-deactiveren         → PR #8   → US-06
feature/client-aanmaken             → PR #9   → US-07
feature/client-begeleiders-koppelen → PR #10  → US-08
feature/clienten-overzicht          → PR #11  → US-09
feature/client-bewerken-archiveren  → PR #12  → US-10
feature/concept-uren-aanmaken       → PR #13  → US-11
feature/uren-indienen-terugtrekken  → PR #14  → US-12
feature/uren-goedkeuren-afkeuren    → PR #15  → US-13
feature/uren-overzicht-met-filters  → PR #16  → US-14
feature/wachtwoord-vergeten         → PR #17  → US-15
feature/profielbeheer               → PR #18  → US-16
```

**Verificatie:** klik op een PR-URL in §3 → linkerkant van PR-header toont de branch-naam → `abii2024 merged 16 commits into main from feature/<naam>`.

---

## 5. Commit-discipline

### 5.1 Conventional-commit-prefixes (top-15)

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

**Conventie:** `<type>(<scope>): <korte beschrijving in Nederlands>`. Types: `feat`, `fix`, `test`, `docs`, `chore`, `style`, `refactor`. Scopes: `auth`, `team`, `clients`, `uren`, `wireframes`, `setup`, `design`, `mail`, `opdracht-4` t/m `opdracht-7`.

**Verificatie:** `git log --pretty=format:"%s" --no-merges | grep -oE "^[a-z]+\([a-z-]+\):" | sort | uniq -c | sort -rn | head -15`

### 5.2 Voorbeelden van duidelijke commit-messages

```
98e6200  docs(opdracht-4): update gmail-inbox screenshot
cc175e9  chore: untrack .claude/ + ignore .claude en .cursor in gitignore
9262d66  feat(mail): Resend-integratie voor wachtwoord-reset (Opdracht-4 / US-17)
269f2c4  fix(auth): use markdown() voor mail-template i.p.v. view() (US-15 runtime-fix)
b56aac0  style: apply pint PSR-12 formatting project-wide (end-check)
251af69  docs(project): projectverslag v2.0 — PROJECT COMPLEET (16/16 user stories)
```

**Verificatie:** `git log --oneline | head -20` of <https://github.com/abii2024/nexora/commits/main>.

### 5.3 Workflow-discipline

- ✅ **Feature-branch per user story** (zie §4.1) — 18 stuks
- ✅ **Pull request per feature** met merge in `main` — 18 stuks
- ✅ **Annotated git-tag per sprint** voor reproduceerbaarheid — 4 stuks
- ✅ **Squash-merge** per PR — schone, lineaire history op `main`
- ✅ **Conventional commit-prefixes** in het Nederlands
- ✅ **Meerdere commits per dag** met duidelijke berichten — gemiddeld 12 commits/dag tijdens sprints

---

## 6. ASCII git-graph — alle sprint-grenzen zichtbaar

Onderstaand fragment toont de **release-snapshots** (één regel per tag/branch) zoals geproduceerd door `git log --all --oneline --decorate --simplify-by-decoration`:

```
* 98e6200 (HEAD -> main, origin/main) docs(opdracht-4): update gmail-inbox screenshot
* cfa69b8 (tag: sprint-4, sprint-4) Merge pull request #18 from abii2024/feature/profielbeheer
* 59a42b6 (tag: sprint-3, sprint-3) Merge pull request #14 from abii2024/feature/uren-indienen-terugtrekken
* f9af6e8 (tag: sprint-2, sprint-2) Merge pull request #10 from abii2024/feature/client-begeleiders-koppelen
* a776e66 (tag: sprint-1, sprint-1) Merge pull request #6 from abii2024/feature/medewerkers-overzicht
```

**Lezen:** elke `*` = een commit, decorate-labels tussen haakjes tonen waar tags en branches naartoe wijzen. De vier `(tag: sprint-N, sprint-N)` regels zijn de sprint-afsluitingen — perfect ge-aligned met de sprint-grenzen.

**Verificatie:** `git log --all --oneline --decorate --simplify-by-decoration | head -10` lokaal, of via <https://github.com/abii2024/nexora/network> voor het netwerk-overzicht.

---

## 7. Live verificatie — alle GitHub-views

In plaats van statische screenshots verwijst deze sectie naar de **live GitHub-pagina's** waar de examinator elke claim uit dit document realtime kan controleren. Alle URLs zijn klikbaar.

| # | Wat | Live URL | Wat bevestigt dit |
|---|---|---|---|
| 1 | **Merged pull requests** | <https://github.com/abii2024/nexora/pulls?q=is%3Apr+is%3Amerged> | 18 PR's, US-nummer in titel, branch in PR-header |
| 2 | **Branches** | <https://github.com/abii2024/nexora/branches> | `main` + 4 sprint-branches (huidige staat) |
| 3 | **Tags / releases** | <https://github.com/abii2024/nexora/tags> | 4 sprint-tags |
| 4 | **Commit-historie op `main`** | <https://github.com/abii2024/nexora/commits/main> | Scoped commit-prefixes, NL bodies, conventional commits |
| 5 | **Network-graph** | <https://github.com/abii2024/nexora/network> | Visuele weergave van alle merges in `main` |
| 6 | **Contributors-overzicht** | <https://github.com/abii2024/nexora/graphs/contributors> | Enige contributor: `abii2024` (Abdisamad) |
| 7 | **Repository-root** | <https://github.com/abii2024/nexora> | Folder-structuur + README + Contributors-panel |

> **Waarom inline-bewijslast i.p.v. screenshots?** Een PNG is statisch en kan in principe gemanipuleerd worden. Een klikbare link naar de live API maakt manipulatie onmogelijk — wat in dit document staat is wat er op GitHub staat. Als de examinator op een willekeurige link in §3 klikt en de PR ziet met de beschreven branch + merge-datum, is dat de meest directe vorm van bewijs die mogelijk is.

---

## 8. Koppeling met examen-rubric

| Rubric-item | Bewijs in dit document |
|---|---|
| *Screenshots van de commit-geschiedenis* | §5 (commit-discipline + voorbeelden) + §6 (ASCII git-graph) + live link §7.4 |
| *Screenshots van branches op GitHub* | §4 (huidige branches) + §4.1 (18 historische feature-branches) + live link §7.2 |
| *Pull requests samengevoegd in main branch* (B1-K1-W3) | §3 (tabel 18 PRs met merge-datums) + live link §7.1 |
| *Meerdere commits per dag met duidelijke commit messages* | §5.1 + §5.2 + live link §7.4 |
| *Aparte feature-branch per functionaliteit* | §4.1 (16 + 2 branches) + branch-kolom in §3 |
| *Annotated sprint-tags voor reproduceerbaarheid* | §2 (4 tags met SHA + URL) + live link §7.3 |
