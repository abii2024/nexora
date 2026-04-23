# Screenshots US-02 — Rolgebaseerde toegang

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US02-rolgebaseerde-toegang.md).

## Vereiste screenshots (DoD US-02)

- [ ] `01-pest-authorization.png` — Pest-output van `./vendor/bin/pest --filter=Authorization` (alle 8 groen)
- [ ] `02-pest-clientpolicy.png` — Pest-output van `./vendor/bin/pest tests/Feature/US-02.php` (alle 12 groen)
- [ ] `03-pest-clientscope.png` — Pest-output van `./vendor/bin/pest tests/Feature/US-02.php` (alle 6 groen)
- [ ] `04-403-no-access.png` — 403-pagina als zorgbegeleider handmatig `/teamleider/dashboard` opent (TC-03)
- [ ] `05-guest-redirect.png` — Netwerk-tab Chrome DevTools: GET `/dashboard` → 302 → `/login` als guest (TC-01)
- [ ] `06-tinker-scope.png` — `php artisan tinker` met `app(ClientService::class)->scopedForUser(...)` resultaten voor Jeroen/Mo/Noa (TC-05)

## Testgebruikers

| E-mail | Wachtwoord | Rol | Team |
|---|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider | Rotterdam-Noord |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord |
| `mo@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord |
| `noa@nexora.test` | `password` | zorgbegeleider | Amsterdam-Zuid |
| `inactief@nexora.test` | `password` | zorgbegeleider | Rotterdam-Noord (is_active=false) |

Setup: `php artisan migrate:fresh --seed`
URL: [http://nexora.test](http://nexora.test)
