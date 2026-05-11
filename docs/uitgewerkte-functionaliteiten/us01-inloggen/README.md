# Screenshots US-01 — Inloggen

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US01-inloggen.md).

## Vereiste screenshots (DoD US-01)

- [ ] `01-loginformulier.png` — `/login` leeg formulier
- [ ] `02-validatie-fout.png` — foutmelding bij fout wachtwoord (TC-03)
- [ ] `03-deactivatie-melding.png` — foutmelding bij `is_active=false` (TC-05)
- [ ] `04-zorgbegeleider-dashboard.png` — ingelogd als zorgbegeleider (TC-01)
- [ ] `05-teamleider-dashboard.png` — ingelogd als teamleider (TC-02)
- [ ] `06-uitloggen.png` — terug op `/login` na uitloggen (TC-06)

## Testgebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider |
| `teamleider@nexora.test` | `password` | teamleider |
| `inactief@nexora.test` | `password` | zorgbegeleider (inactief) |

Setup: `php artisan migrate:fresh --seed`
URL: [http://nexora.test/login](http://nexora.test/login)
