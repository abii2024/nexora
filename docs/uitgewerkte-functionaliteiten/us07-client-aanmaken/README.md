# Screenshots US-07 — Cliënt aanmaken met persoonsgegevens

Screenshots + handmatige tests worden **aan het einde van alle 16 US's** in één batch uitgevoerd (afspraak met gebruiker).

## Vereiste screenshots (DoD US-07)

- [ ] `01-create-form-secties.png` — `/clients/create` met 3 secties zichtbaar (Persoonlijk/Contact/Zorg)
- [ ] `02-validatie-voornaam.png` — fout "Voornaam is verplicht" + andere velden behouden
- [ ] `03-bsn-duplicate.png` — fout "Dit BSN is al gekoppeld aan een andere cliënt"
- [ ] `04-bsn-format.png` — fout bij <9 cijfers of letters
- [ ] `05-success-show.png` — redirect `/clients/{id}` met flash "Cliënt aangemaakt."
- [ ] `06-index-lijst.png` — cliënt zichtbaar in `/clients` (zonder BSN — AVG)
- [ ] `07-zorgbeg-403.png` — zorgbegeleider probeert `/clients/create` → 403
- [ ] `08-pest-output.png` — terminal: `./vendor/bin/pest tests/Feature/US-07.php` 21/21 groen

## Test-gebruikers

| E-mail | Wachtwoord | Rol |
|---|---|---|
| `teamleider@nexora.test` | `password` | teamleider |
| `zorgbegeleider@nexora.test` | `password` | zorgbegeleider |

## Test-data voor TC-05

| Veld | Waarde |
|---|---|
| Voornaam | Sanne |
| Achternaam | de Wit |
| Geboortedatum | 17-05-1980 |
| BSN | 123456789 (of leeg voor AVG) |
| E-mail | `sanne@client.test` |
| Telefoon | 0612345678 |
| Status | Actief |
| Zorgtype | WMO |
