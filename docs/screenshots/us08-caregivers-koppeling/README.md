# Screenshots US-08 — Cliënten koppelen aan begeleiders

Screenshots + handmatige tests worden **aan het einde van alle 16 US's** in één batch uitgevoerd.

## Vereiste screenshots (DoD US-08)

- [ ] `01-create-begeleiders-sectie.png` — `/clients/create` 4e sectie "Begeleiders" met checkboxes + primair-radio
- [ ] `02-show-primair-secundair-tertiair.png` — show-page met 3 begeleiders met rol-badges in 3 kleuren
- [ ] `03-caregivers-edit-page.png` — `/clients/{id}/caregivers` met bestaande state + radio "Maak primair"
- [ ] `04-swap-primair-flash.png` — na swap redirect met flash "Begeleiders bijgewerkt."
- [ ] `05-notifications-table.png` — DB-inspectie van `notifications` tabel met `type='client_toegewezen'` rijen
- [ ] `06-partial-unique-exception.png` — tinker poging 2x primair → UniqueConstraintViolationException
- [ ] `07-cross-team-validation.png` — fout "Alleen actieve zorgbegeleiders uit je eigen team"
- [ ] `08-zorgbeg-403-caregivers.png` — zorgbegeleider probeert `/clients/{id}/caregivers` → 403
- [ ] `09-empty-state-begeleiders.png` — show-page zonder koppeling toont "Geen begeleiders gekoppeld" + CTA
- [ ] `10-pest-output-us08.png` — terminal: `./vendor/bin/pest tests/Feature/US-08.php` 29/29 groen

## Test-gebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider (Rotterdam) |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider (caregiver-target) |
| `mo@nexora.test` | `password` | zorgbegeleider (caregiver-target) |
| `noa@nexora.test` | `password` | zorgbegeleider Amsterdam (cross-team) |

## Notifications via tinker

```bash
php artisan tinker
>>> $jeroen = App\Models\User::where('email', 'zorgbegeleider@nexora.test')->first();
>>> $jeroen->notifications()->get(['type', 'data', 'created_at']);
>>> $jeroen->unreadNotifications()->count();
```

## Partial unique test via tinker

```bash
>>> use Illuminate\Support\Facades\DB;
>>> $client = App\Models\Client::first();
>>> DB::table('client_caregivers')->insert(['client_id'=>$client->id,'user_id'=>2,'role'=>'primair','created_at'=>now(),'updated_at'=>now()]);
>>> DB::table('client_caregivers')->insert(['client_id'=>$client->id,'user_id'=>3,'role'=>'primair','created_at'=>now(),'updated_at'=>now()]);
// Verwacht: UniqueConstraintViolationException
```
