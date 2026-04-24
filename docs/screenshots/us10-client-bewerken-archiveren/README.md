# Screenshots — US-10 Cliënt bewerken en archiveren

Checklist voor de handmatige verificatie aan het einde van het project (batch-oplevering).

## Checklist

- [ ] `01-edit-form-teamleider.png` — `/clients/{id}/edit` als teamleider met pre-filled velden + 4 secties + rode "Archiveren"-knop onderaan
- [ ] `02-bewerken-opgeslagen.png` — na PUT → redirect naar `/clients/{id}` met groene "Cliënt bijgewerkt"-alert
- [ ] `03-statuswissel-log.png` — show-pagina met "Recente statuswijzigingen"-card: rij `Actief → Wacht` door Fatima El Amrani
- [ ] `04-bsn-duplicate-error.png` — validatie-error "Dit BSN is al gekoppeld aan een andere cliënt" bij duplicate-BSN
- [ ] `05-archiveren-confirm.png` — JS-confirm-dialog "Cliënt Sanne de Wit archiveren? Historische data blijft bewaard."
- [ ] `06-archiveren-alert.png` — `/clients` met groene "Cliënt gearchiveerd"-alert + client weg uit tabel
- [ ] `07-archief-pagina.png` — `/clients/archive` tabel met gearchiveerde cliënten + Herstellen-knop + deleted_at-kolom
- [ ] `08-herstellen-alert.png` — na restore → show-pagina met groene "Cliënt hersteld"-alert
- [ ] `09-zorgbegeleider-geen-bewerken.png` — show als zorgbegeleider: géén Bewerken-knop zichtbaar
- [ ] `10-zorgbegeleider-archief-403.png` — zorgbegeleider direct naar `/clients/archive` → 403-pagina
- [ ] `11-pest-output-us10.png` — Terminal `./vendor/bin/pest tests/Feature/US-10.php` → 31 groene vinkjes, 74 asserts
- [ ] `12-pest-full-suite.png` — Terminal `./vendor/bin/pest` → 215 tests passed, 609 asserts
- [ ] `13-archief-empty-state.png` — `/clients/archive` zonder gearchiveerde → "Nog geen gearchiveerde cliënten"-empty-state

Opnamen worden pas gemaakt wanneer alle 16 user stories klaar zijn — per afspraak in één batch.
