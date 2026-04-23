# Screenshots US-04 — Medewerkersoverzicht met zoek en filter

Plaats hier de screenshots van de handmatige uitvoering van het [testplan](../../testplan/US04-medewerkers-overzicht.md).

## Vereiste screenshots (DoD US-04)

- [ ] `01-overzicht-alle.png` — `/team` toont tabel met 4 medewerkers + header-teller "3 actief · 1 inactief"
- [ ] `02-zoek-naam.png` — filter "Jeroen" → alleen Jeroen Bakker zichtbaar
- [ ] `03-filter-rol.png` — rol=Teamleider → alleen Fatima zichtbaar
- [ ] `04-filter-status-inactief.png` — status=Inactief → Ilse Voskuil grijs weergegeven
- [ ] `05-filters-combineren.png` — zoek "Jeroen" + rol + status tegelijk
- [ ] `06-paginatie.png` — `/team?page=2` na 30 extra seed-users (pagination links onderaan)
- [ ] `07-xss-escape.png` — zoekterm `<script>alert('xss')</script>` → Blade escape in input-veld, geen alert
- [ ] `08-zorgbeg-403.png` — zorgbegeleider probeert `/team` → 403
- [ ] `09-pest-output.png` — terminal: `./vendor/bin/pest tests/Feature/US-04.php` 19/19 groen

## Testgebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider (voor 403 test) |

Setup: `php artisan migrate:fresh --seed`
URL: [http://nexora.test/team](http://nexora.test/team) (na login als teamleider)

## Paginatie-test data genereren

Om paginatie te kunnen testen (>25 medewerkers nodig):

```bash
php artisan tinker
>>> App\Models\User::factory()->count(30)->zorgbegeleider()->create(['team_id' => 1, 'dienstverband' => 'intern']);
```
