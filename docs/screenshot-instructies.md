# Screenshot-checklist voor inlevering (B1-K1-W4)

> **Doel:** alle examen-eis-screenshots in **één werksessie** maken — efficient, geen heen-en-weer geklik.
> **Geschatte tijdsbesteding:** 60-90 minuten voor alle 16 US's + GitHub-views.
> **Datum:** 2026-05-12 (inleverdag)

Dit document is je **werkscript** voor het maken van de laatste visuele bewijslast vóór inlevering. Per US staat exact welke screenshots nog ontbreken, welk pad ze moeten krijgen, welke URL je opent en welke handeling je uitvoert. Werk **van boven naar beneden** in deze volgorde.

---

## 0. Voorbereiding (5 min)

```bash
# Terminal 1: server starten
cd /Users/abdisamadabdulle/Herd/nexora
php artisan migrate:fresh --seed
php artisan serve   # of Herd: http://nexora.test

# Terminal 2: tinker (voor seed-checks tijdens screenshotten)
php artisan tinker
```

**Testaccounts (wachtwoord overal: `password`):**

| Rol | E-mail | Naam | Gebruik voor |
|---|---|---|---|
| Teamleider | `abdisamadvanabdulle@gmail.com` | Fatima El Amrani | Team beheer + cliënt aanmaken/koppelen + uren beoordelen |
| Zorgbegeleider (actief) | `zorgbegeleider@nexora.test` | Jeroen Bakker | Eigen caseload + uren registreren |
| Zorgbegeleider (extra) | `mo@nexora.test` | Mo Yilmaz | Cross-caregiver tests US-08 |
| Zorgbegeleider (ander team) | `noa@nexora.test` | Noa De Vries | Cross-team autorisatie US-02 |
| Inactief | `inactief@nexora.test` | Ilse Voskuil | US-01 deactivated-flow + US-06 |

**Browser:** open een private/incognito window (Cmd+Shift+N Chrome / Cmd+Shift+P Firefox) zodat sessies elkaar niet kruisen. **Maak desktop-window 1280×800** (consistente screenshot-grootte).

**Screenshot-tool:**
- macOS: `Cmd+Shift+4` → spatiebalk → klik venster (vangt 1 venster).
- Voor full-page: gebruik browser-extensie *GoFullPage*.
- Bestandsnaam-conventie: `NN-korte-beschrijving.png` (lowercase, kebab-case).

---

## 1. Per-US screenshots — `docs/uitgewerkte-functionaliteiten/usNN-*/`

> **Status nu:** elke US heeft 1-2 screenshots geplaatst, maar de README's specificeren 6-16 vereiste shots per US. Hieronder de **resterende** shots per US, met de exacte path waar ze opgeslagen moeten worden.

### US-01 — Inloggen op Nexora (`us01-inloggen/`)

| # | Bestand | URL / handeling | Wat moet zichtbaar zijn |
|---|---|---|---|
| 2 | `02-login-fout.png` | `/login` → submit met `zorgbegeleider@nexora.test` + fout wachtwoord | NL foutmelding "De ingevoerde gegevens zijn onjuist." (NIET "user not found" — enumeration-protection) |
| 3 | `03-login-inactief.png` | `/login` → submit met `inactief@nexora.test` + `password` | NL melding "Dit account is gedeactiveerd. Neem contact op met je teamleider." |
| 4 | `04-dashboard-teamleider.png` | Login als teamleider → `/teamleider/dashboard` | Teamleider-dashboard met stats-cards |
| 5 | `05-dashboard-zorgbegeleider.png` | Login als zorgbegeleider → `/dashboard` | Zorgbegeleider-dashboard met eigen caseload-banner |
| 6 | `06-pest-tests-us01.png` | Terminal: `php artisan test --filter US-01 --compact` | 10 tests groen, 37 asserts |

### US-02 — Rolgebaseerde toegang (`us02-rolgebaseerde-toegang/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 3 | `03-403-cross-team.png` | Login als `noa@nexora.test` → `/clients/{id-van-rotterdam-client}` | 403-pagina (cross-team toegang geblokkeerd) |
| 4 | `04-403-zorgbeg-team.png` | Login als zorgbegeleider → ga naar `/team` | 403-pagina (geen teamleider-rol) |
| 5 | `05-eigen-caseload.png` | Login als Jeroen → `/clients` | Alleen 2 cliënten (waar Jeroen primair of secundair is) — niet de 3e |
| 6 | `06-pest-tests-us02.png` | `php artisan test --filter US-02 --compact` | 26 tests groen, 54 asserts |

### US-03 — Nieuwe zorgbegeleider aanmaken (`us03-medewerker-aanmaken/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-form-leeg.png` | `/team/create` | Leeg formulier zichtbaar |
| 3 | `03-form-validatie.png` | Submit met leeg e-mail | Validatie-meldingen rood |
| 4 | `04-form-ingevuld.png` | Voer gegevens in (bv. Test Tester, test@nexora.test, zorgbegeleider, intern) | Formulier ingevuld |
| 5 | `05-success-flash.png` | Submit | Redirect naar `/team` met groene flash "is toegevoegd" |
| 6 | `06-medewerker-in-overzicht.png` | `/team` na aanmaak | Nieuwe medewerker zichtbaar in tabel |
| 7 | `07-pest-tests-us03.png` | `php artisan test --filter US-03 --compact` | 15 tests, 53 asserts |

### US-04 — Medewerkersoverzicht (`us04-medewerkers-overzicht/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-overzicht-alle.png` | `/team` als teamleider | Tabel met alle 6 medewerkers |
| 3 | `03-zoek-jeroen.png` | `/team?search=Jeroen` | Alleen Jeroen Bakker zichtbaar |
| 4 | `04-filter-inactief.png` | `/team?status=inactief` | Alleen Ilse Voskuil zichtbaar |
| 5 | `05-paginatie.png` | (Optioneel — als seeder >15 users heeft) `/team?page=2` | Tweede pagina + paginatie-links onderaan |
| 6 | `06-pest-tests-us04.png` | `php artisan test --filter US-04 --compact` | 19 tests, 61 asserts |

### US-05 — Teamlid bewerken (`us05-teamlid-bewerken/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-edit-form.png` | `/team/{jeroen-id}/edit` | Edit-formulier met huidige waarden voorgevuld |
| 3 | `03-rol-gewijzigd.png` | Submit met rol naar 'teamleider' | Redirect met success-flash |
| 4 | `04-audit-log-tinker.png` | `php artisan tinker` → `UserAuditLog::latest()->take(3)->get(['field','old_value','new_value'])` | Drie audit-log entries voor rolwijziging |
| 5 | `05-self-demotion-guard.png` | Probeer als enige teamleider jezelf naar zorgbegeleider te zetten | Validatie-fout "Het team moet minimaal één teamleider houden" |
| 6 | `06-pest-tests-us05.png` | `php artisan test --filter US-05 --compact` | 16 tests, 54 asserts |

### US-06 — Teamlid deactiveren (`us06-teamlid-deactiveren/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-deactivate-button.png` | `/team/{jeroen-id}/edit` | "Deactiveren"-knop zichtbaar |
| 3 | `03-after-deactivate.png` | Klik deactiveer | Redirect met "is gedeactiveerd" flash |
| 4 | `04-runtime-logout.png` | Open tweede browser-window als Jeroen (al ingelogd) → refresh een willekeurige pagina | Automatisch uitgelogd, redirect naar `/login` |
| 5 | `05-heractiveer.png` | Login als teamleider → `/team/{jeroen-id}/edit` → "Heractiveren" | Jeroen weer actief |
| 6 | `06-pest-tests-us06.png` | `php artisan test --filter US-06 --compact` | 19 tests, 62 asserts |

### US-07 — Cliënt aanmaken (`us07-client-aanmaken/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-form-leeg.png` | `/clients/create` | Leeg formulier |
| 3 | `03-validatie-bsn.png` | Submit met BSN "123" (te kort) | Validatie-fout BSN moet 9 cijfers zijn |
| 4 | `04-validatie-toekomst-datum.png` | Submit met geboortedatum 2030 | Validatie-fout datum mag niet in toekomst |
| 5 | `05-form-ingevuld.png` | Voer geldige cliënt in | Formulier groen |
| 6 | `06-cliënt-in-overzicht.png` | `/clients` | Nieuwe cliënt zichtbaar |
| 7 | `07-pest-tests-us07.png` | `php artisan test --filter US-07 --compact` | 21 tests, 75 asserts |

### US-08 — Cliënten koppelen aan begeleiders (`us08-caregivers-koppeling/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-caregivers-form.png` | `/clients/{c1-id}/caregivers` | Checklist met begeleiders + radio voor primair |
| 3 | `03-after-koppeling.png` | Submit met Jeroen primair + Mo secundair | Redirect met success-flash |
| 4 | `04-partial-unique-test.png` | `tinker`: probeer 2× primair direct via `ClientCaregiver::create()` | SQLite-error UNIQUE constraint |
| 5 | `05-cliënt-tabel-rollen.png` | `/clients` | Tabel toont rol-badges (primair / secundair / tertiair) |
| 6 | `06-notification-database.png` | `tinker`: `User::find($mo->id)->notifications` | Database-channel notification voor Mo |
| 7 | `07-pest-tests-us08.png` | `php artisan test --filter US-08 --compact` | 29 tests, 70 asserts |

### US-09 — Cliëntenoverzicht (`us09-clienten-overzicht/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-tl-tabel-view.png` | Teamleider → `/clients` | Tabel-view + totaal-banner ("X cliënten in jouw team") |
| 3 | `03-zb-kaart-view.png` | Zorgbegeleider → `/clients` | Kaart-grid + eigen-caseload-banner |
| 4 | `04-filter-status.png` | `/clients?status=actief` | Filter werkt + query-string in URL |
| 5 | `05-zoek-naam.png` | `/clients?search=sanne` | Resultaten filteren op voornaam |
| 6 | `06-sort-achternaam.png` | `/clients?sort=achternaam` | Gesorteerd op achternaam |
| 7 | `07-empty-state.png` | Filter zonder resultaten | Empty-state-component zichtbaar |
| 8 | `08-pest-tests-us09.png` | `php artisan test --filter US-09 --compact` | 27 tests, 67 asserts |

### US-10 — Cliënt bewerken + archiveren (`us10-client-bewerken-archiveren/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-edit-form.png` | `/clients/{c1-id}/edit` | Edit-formulier met voorgevulde waarden |
| 3 | `03-after-edit.png` | Submit wijziging | Success-flash |
| 4 | `04-archive-confirm.png` | Klik "Archiveren" | Bevestigings-modal |
| 5 | `05-archive-overzicht.png` | `/clients/archive` (alleen teamleider) | Gearchiveerde cliënten zichtbaar |
| 6 | `06-soft-delete-tinker.png` | `tinker`: `Client::withTrashed()->find($id)->deleted_at` | Timestamp aanwezig, niet null |
| 7 | `07-pest-tests-us10.png` | `php artisan test --filter US-10 --compact` | 31 tests, 74 asserts |

### US-11 — Concept-uren (`us11-concept-uren-aanmaken/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-form-uren-create.png` | `/uren/create` | Formulier: cliënt-dropdown, datum, start, eind, notities |
| 3 | `03-duur-server-side.png` | Vul 09:00 + 12:30 in → submit → tinker: `Urenregistratie::latest()->first()->uren` | Decimaal 3.50 (server-side berekend) |
| 4 | `04-status-concept.png` | `/uren` | Tab "Concept" actief, nieuwe entry zichtbaar |
| 5 | `05-bewerken-toegestaan.png` | `/uren/{id}/edit` als status=Concept | Bewerk-formulier toegankelijk |
| 6 | `06-pest-tests-us11.png` | `php artisan test --filter US-11 --compact` | 28 tests, 77 asserts |

### US-12 — Indienen/terugtrekken/resubmit (`us12-uren-indienen/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-indien-knop.png` | `/uren` → klik "Indienen" op concept-entry | Status wisselt naar Ingediend |
| 3 | `03-terugtrekken.png` | Klik "Terugtrekken" op Ingediend | Status terug naar Concept |
| 4 | `04-state-machine-error.png` | tinker: probeer ongeldige transitie (Goedgekeurd→Concept) | `InvalidStateTransitionException` 422 |
| 5 | `05-notification-tl.png` | Indien → tinker: `User::where('role','teamleider')->first()->notifications` | Notification voor teamleider zichtbaar |
| 6 | `06-pest-tests-us12.png` | `php artisan test --filter US-12 --compact` | 31 tests, 62 asserts |

### US-13 — Goedkeuren/afkeuren (`us13-uren-beoordelen/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-teamleider-uren-overzicht.png` | Teamleider → `/teamleider/uren` | Ingediende uren zichtbaar |
| 3 | `03-goedkeur-knop.png` | Klik "Goedkeuren" | Status naar Goedgekeurd, success-flash |
| 4 | `04-afkeur-modal.png` | Klik "Afkeuren" op andere entry | Modal met reden-textarea |
| 5 | `05-afkeur-validatie-min10.png` | Submit met reden < 10 chars | Validatie-fout |
| 6 | `06-afkeur-saved.png` | Valide reden invoeren → submit | Status Afgekeurd + reden zichtbaar |
| 7 | `07-zorgbeg-ziet-reden.png` | Login als zorgbegeleider → afgekeurde entry | Banner met afkeur-reden |
| 8 | `08-pest-tests-us13.png` | `php artisan test --filter US-13 --compact` | 27 tests, 63 asserts |

### US-14 — Urenoverzicht met filters (`us14-uren-overzicht/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-overzicht-zonder-filters.png` | Teamleider → `/teamleider/uren-overzicht` | Tabel met ingediende uren (default-filter) |
| 3 | `03-filter-week.png` | `?week=2026-W17` (de werkende week) | Alleen entries van die week |
| 4 | `04-filter-medewerker.png` | `?medewerker={jeroen-id}` | Alleen Jeroens uren |
| 5 | `05-sort-duur.png` | Klik kolomkop "Duur" | Gesorteerd, pijl-indicator zichtbaar |
| 6 | `06-week-summary.png` | Default view | Subtotalen per medewerker + weektotaal onderaan |
| 7 | `07-pest-tests-us14.png` | `php artisan test --filter US-14 --compact` | 22 tests, 44 asserts |

### US-15 — Wachtwoord reset (`us15-wachtwoord-reset/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 3 | `03-form-wachtwoord-vergeten.png` | `/wachtwoord-vergeten` | Formulier met e-mail-veld |
| 4 | `04-flash-mail-verzonden.png` | Submit met `abdisamadvanabdulle@gmail.com` | Flash "Als dit account bestaat, is er een reset-link verstuurd" |
| 5 | `05-mail-in-laravel-log.png` | (Alleen als `MAIL_MAILER=log`) `tail -100 storage/logs/laravel.log` | Mail-body met reset-link in log |
| 6 | `06-reset-form.png` | Klik link uit mail (of `tinker`-gegenereerd) | Reset-formulier met nieuwe wachtwoord-velden |
| 7 | `07-nieuwe-wachtwoord-werkt.png` | Nieuwe wachtwoord instellen → login | Dashboard zichtbaar |
| 8 | `08-pest-tests-us15.png` | `php artisan test --filter US-15 --compact` | 16 tests, 46 asserts |

### US-16 — Profielbeheer (`us16-profielbeheer/`)

| # | Bestand | URL / handeling | Wat |
|---|---|---|---|
| 2 | `02-profiel-view.png` | `/profiel` (ingelogd) | Eigen gegevens zichtbaar |
| 3 | `03-edit-naam-email.png` | Wijzig naam → submit met huidig wachtwoord | Success-flash |
| 4 | `04-wachtwoord-mismatch.png` | Submit nieuw wachtwoord met fout current_password | Validatie-fout |
| 5 | `05-mass-assignment-blocked.png` | tinker: `User::find($u->id)->forceFill(['role'=>'teamleider'])` zou werken — toon dat **via HTTP** (POST met `role=teamleider` field) ‟role" NIET muteert | Database toont role nog steeds 'zorgbegeleider' |
| 6 | `06-logout-other-devices.png` | Open 2e browser → login → in 1e browser wachtwoord wijzigen → refresh 2e browser | 2e browser uitgelogd |
| 7 | `07-pest-tests-us16.png` | `php artisan test --filter US-16 --compact` | 21 tests, 52 asserts |

---

## 2. GitHub-screenshots — `docs/github-bewijslast/`

5 specifieke GitHub-views. Open elke URL, scroll naar boven, full-page screenshot (extensie *GoFullPage*).

| # | Bestand | URL | Wat zichtbaar |
|---|---|---|---|
| 1 | `01-pulls-merged.png` | <https://github.com/abii2024/nexora/pulls?q=is%3Apr+is%3Amerged> | 18 merged PR's, allemaal met US-N nummer in titel |
| 2 | `02-branches.png` | <https://github.com/abii2024/nexora/branches> | "Active" branches lege of weinig, "Stale" toont alle 18 feature-branches |
| 3 | `03-tags.png` | <https://github.com/abii2024/nexora/tags> | 4 tags: `sprint-1`, `sprint-2`, `sprint-3`, `sprint-4` |
| 4 | `04-commits-main.png` | <https://github.com/abii2024/nexora/commits/main> | Scoped commit-prefixes, NL bodies, geen `--amend`-markers |
| 5 | `05-network-graph.png` | <https://github.com/abii2024/nexora/network> | Network-graph waarin alle feature-branches in `main` mergen |

---

## 3. Code-screenshots (alternatief — niet verplicht)

Code-bewijslast is al **inline** opgenomen in [docs/code-bewijslast/README.md](code-bewijslast/README.md) met GitHub-permalinks naar elke regel. Als de examinator toch IDE-screenshots wenst, screenshot de volgende files in PHPStorm/VSCode (1 screenshot per US, file open):

1. `app/Http/Controllers/Auth/LoginController.php` → US-01
2. `app/Http/Middleware/EnsureTeamleider.php` → US-02
3. `app/Services/UserService.php` (updateWithAudit-methode) → US-05
4. `database/migrations/2026_04_23_072143_add_primary_secondary_partial_unique_to_client_caregivers.php` → US-08
5. `app/Enums/UrenStatus.php` → US-11
6. `app/Services/UrenregistratieService.php` (transition-methode) → US-12
7. `app/Notifications/WachtwoordResetNotification.php` → US-15
8. `app/Http/Controllers/ProfielController.php` (update-methode) → US-16

Opslagpath: `docs/code-bewijslast/screenshots/NN-bestand.png`.

---

## 4. Eind-checklist vóór inlevering

- [ ] Alle 16 US-mappen hebben de hierboven gespecificeerde screenshots
- [ ] `docs/github-bewijslast/` heeft 5 GitHub-views
- [ ] `git status` is schoon (alle screenshots gecommit + gepusht)
- [ ] `php artisan test` lokaal groen (360 tests)
- [ ] Repository public toegankelijk: <https://github.com/abii2024/nexora>
- [ ] Examenverslag-document up-to-date: [`docs/projectverslag.md`](projectverslag.md) + [`docs/examen-checklist.md`](examen-checklist.md)
- [ ] Eind-commit gepusht naar `main`

**Pro-tip:** maak alle screenshots **op één avond** in de volgorde hierboven. Het is sneller om 1× door alle US's te lopen dan elk later individueel terug te zoeken. Reken op ~5 min per US × 16 = 80 min, plus 10 min voor GitHub-views.
