# Testplan — US-01 Inloggen op Nexora

> **User story:** Als zorgbegeleider of teamleider wil ik met mijn e-mailadres en wachtwoord kunnen inloggen op Nexora zodat ik veilig toegang krijg tot de cliëntgegevens en functionaliteiten die bij mijn rol horen.
>
> **Branch:** `feature/authenticatie`
> **Feature test:** [`tests/Feature/Auth/LoginTest.php`](../../tests/Feature/Auth/LoginTest.php) (10 tests, 37 asserts — allemaal groen)

## Test-gebruikers (uit `DatabaseSeeder`)

| Naam | E-mail | Wachtwoord | Rol | Actief |
|---|---|---|---|---|
| Fatima El Amrani | `teamleider@nexora.test` | `password` | teamleider | ✓ |
| Jeroen Bakker | `zorgbegeleider@nexora.test` | `password` | zorgbegeleider | ✓ |
| Ilse Voskuil | `inactief@nexora.test` | `password` | zorgbegeleider | ✗ |

Setup: `php artisan migrate:fresh --seed`

## Handmatige testscenario's

### TC-01 — Zorgbegeleider succesvol inloggen (AC-1)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Ga naar [http://nexora.test/login](http://nexora.test/login) | Loginformulier zichtbaar met e-mail, wachtwoord, "Onthoud mij" en "Wachtwoord vergeten"-link |
| 2 | Vul in: `zorgbegeleider@nexora.test` / `password` | Velden gevuld |
| 3 | Klik **Inloggen** | Redirect naar `/dashboard`, navbar toont naam + uitloggen-knop, welkomsttekst "Welkom, Jeroen Bakker" |
| 4 | Inspecteer cookies | Sessiecookie aanwezig, CSRF-token aanwezig |

**Pest-dekking:** `it('logs in an active zorgbegeleider and redirects to /dashboard')`

### TC-02 — Teamleider succesvol inloggen (AC-2)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Ga naar `/login` | Formulier zichtbaar |
| 2 | Vul in: `teamleider@nexora.test` / `password` | Velden gevuld |
| 3 | Klik **Inloggen** | Redirect naar `/teamleider/dashboard`, titel "Teamleider dashboard", team-naam zichtbaar |

**Pest-dekking:** `it('logs in an active teamleider and redirects to /teamleider/dashboard')`

### TC-03 — Fout wachtwoord (AC-3)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Ga naar `/login` | Formulier zichtbaar |
| 2 | Vul in: `zorgbegeleider@nexora.test` / `VERKEERD` | Velden gevuld |
| 3 | Klik **Inloggen** | Blijft op `/login` (URL ongewijzigd), foutmelding "De ingevoerde gegevens zijn onjuist." zichtbaar, wachtwoordveld is leeg, e-mailveld behoudt waarde |
| 4 | Sessie-status | Niet ingelogd (navbar toont geen naam) |

**Pest-dekking:** `it('rejects login with wrong password and keeps user unauthenticated')`

### TC-04 — Onbekend e-mailadres — identieke foutmelding (Privacy bullet 4)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Ga naar `/login` | Formulier zichtbaar |
| 2 | Vul in: `bestaat-niet@example.com` / `wat-dan-ook` | Velden gevuld |
| 3 | Klik **Inloggen** | Foutmelding **exact gelijk** aan TC-03: "De ingevoerde gegevens zijn onjuist." (geen info-leak: geen "dit e-mailadres bestaat niet"-melding) |

**Pest-dekking:** `it('returns the same error message for unknown email and wrong password')`

### TC-05 — Gedeactiveerd account (AC-4)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Ga naar `/login` | Formulier zichtbaar |
| 2 | Vul in: `inactief@nexora.test` / `password` | Velden gevuld |
| 3 | Klik **Inloggen** | Blijft op `/login`, foutmelding bevat "gedeactiveerd" met verwijzing naar teamleider, geen redirect naar dashboard |
| 4 | Sessie-status | Niet ingelogd |

**Pest-dekking:** `it('rejects login for a deactivated account with specific message')`

### TC-06 — Uitloggen (AC-5)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Log in (TC-01 of TC-02) | Op dashboard |
| 2 | Inspecteer cookie-waarde | Sessie-ID = X |
| 3 | Klik **Uitloggen** in de navbar | Redirect naar `/login`, navbar zonder naam |
| 4 | Inspecteer cookie-waarde | Sessie-ID ≠ X (sessie geïnvalideerd), CSRF-token opnieuw gegenereerd |
| 5 | Ga handmatig terug naar `/dashboard` | Redirect naar `/login` (niet ingelogd) |

**Pest-dekking:** `it('logs out an authenticated user and invalidates the session')`

### TC-07 — Session fixation protection (Privacy bullet 3)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Open `/login` in nieuwe incognito | Sessie-cookie gezet met ID = A |
| 2 | Log in (TC-01) | Sessie-cookie ID ≠ A na succesvolle login |

**Pest-dekking:** `it('regenerates the session id after successful login')`

### TC-08 — CSRF protection (Privacy bullet 2)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | Inspecteer DOM op `/login` | `<input type="hidden" name="_token" value="...">` aanwezig |
| 2 | Verwijder het token via DevTools en submit | 419 Page Expired response (CSRF check werkt) |

### TC-09 — Brute-force protection (extra)

| Stap | Actie | Verwacht resultaat |
|---|---|---|
| 1 | 6× fout wachtwoord invoeren voor hetzelfde e-mailadres | Na poging 6: foutmelding "Te veel inlogpogingen. Probeer het over X seconden opnieuw." |

## Automatisch getest

```bash
./vendor/bin/pest --filter=LoginTest
```

Verwachte output: **10 passed (37 assertions)**.

## Screenshots

Screenshots van handmatige testuitvoering in `docs/screenshots/us01-inloggen/`:

- `01-loginformulier.png` — leeg formulier op `/login`
- `02-validatie-fout.png` — formulier met foutmelding bij fout wachtwoord
- `03-deactivatie-melding.png` — foutmelding bij gedeactiveerd account
- `04-zorgbegeleider-dashboard.png` — succesvol ingelogd als zorgbegeleider
- `05-teamleider-dashboard.png` — succesvol ingelogd als teamleider
- `06-uitloggen.png` — terug op `/login` na uitloggen

## Verificatie samenvatting

| Acceptatiecriterium | Pest | Handmatig |
|---|---|---|
| AC-1: zorgbegeleider → /dashboard | ✓ | TC-01 |
| AC-2: teamleider → /teamleider/dashboard | ✓ | TC-02 |
| AC-3: fout wachtwoord → /login + foutmelding | ✓ | TC-03 |
| AC-4: is_active=false → geweigerd met eigen melding | ✓ | TC-05 |
| AC-5: uitloggen → invalidate + CSRF regen + /login | ✓ | TC-06 |
| Privacy: wachtwoord bcrypt | n.v.t. (Hash::make via factory) | database-check |
| Privacy: CSRF op form | ✓ | TC-08 |
| Privacy: session regenerate | ✓ | TC-07 |
| Privacy: user enumeration | ✓ | TC-04 |
