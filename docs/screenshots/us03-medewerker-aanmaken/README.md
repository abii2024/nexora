# Screenshots US-03 — Nieuwe zorgbegeleider aanmaken

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US03-medewerker-aanmaken.md).

## Vereiste screenshots (DoD US-03)

- [ ] `01-create-form.png` — `/team/create` formulier leeg (teamleider ingelogd)
- [ ] `02-validatie-errors.png` — leeg submit → 6 validatiefouten in alert (TC-03)
- [ ] `03-duplicate-email.png` — error bij duplicaat e-mail
- [ ] `04-privilege-escalation.png` — pogint `admin` rol via DevTools → "Ongeldige rol." (TC-04)
- [ ] `05-success-flash.png` — redirect naar `/team` met groene flash "Medewerker aangemaakt." + nieuwe rij in tabel
- [ ] `06-new-member-login.png` — nieuwe medewerker kan inloggen (TC-02 stap 4)
- [ ] `07-zorgbeg-403.png` — zorgbegeleider probeert `/team/create` → 403 (TC-05)
- [ ] `08-pest-output.png` — terminal: `./vendor/bin/pest tests/Feature/Team` 15/15 groen

## Testgebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider (Rotterdam-Noord) |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider (voor 403 test) |

Setup: `php artisan migrate:fresh --seed`
URL: [http://nexora.test/team/create](http://nexora.test/team/create) (na login als teamleider)

## Test-scenario voor nieuwe medewerker

Gebruik deze test-data om eentje aan te maken:

| Veld | Waarde |
|---|---|
| Voornaam | Lisa |
| Achternaam | Van Dijk |
| E-mail | `lisa@nexora.test` |
| Rol | Zorgbegeleider |
| Dienstverband | Intern |
| Wachtwoord | `Geheim123` |
| Bevestigen | `Geheim123` |

Na create: uitloggen, opnieuw inloggen als `lisa@nexora.test` / `Geheim123` → moet direct naar `/dashboard` gaan.
