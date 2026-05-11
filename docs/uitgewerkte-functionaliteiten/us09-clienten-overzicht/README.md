# Screenshots — US-09 Cliëntenoverzicht met rol-gebaseerde weergave, zoek en filter

Checklist voor de handmatige verificatie aan het einde van het project (batch-oplevering).

## Checklist

- [ ] `01-teamleider-tabel-overzicht.png` — `/clients` als teamleider met totaal-banner + tabel met 6 kolommen + "Cliënt toevoegen"-knop
- [ ] `02-zorgbegeleider-kaart-overzicht.png` — `/clients` als zorgbegeleider met eigen-caseload-banner + kaart-grid + rol-badges (primair/secundair/tertiair)
- [ ] `03-filter-bar.png` — Filter-bar in detail (zoekveld + status + zorgtype + sortering + filter-knop)
- [ ] `04-zoeken-resultaat.png` — Zoekterm ingevuld → gefilterde resultaten + paginatie die filters behoudt
- [ ] `05-status-filter-actief.png` — Filter op status=actief → alleen actieve cliënten + Reset-knop zichtbaar
- [ ] `06-caretype-filter-wmo.png` — Filter op zorgtype=wmo → alleen WMO-cliënten
- [ ] `07-sort-nieuwst-eerst.png` — Sortering op `created_at` → meest recente eerst
- [ ] `08-empty-state-zorgbegeleider.png` — Zorgbegeleider zonder koppelingen → "Je hebt momenteel geen cliënten toegewezen"
- [ ] `09-empty-state-filters.png` — Filter zonder resultaten → "Geen cliënten gevonden" + Reset-filters-knop
- [ ] `10-empty-state-teamleider.png` — Teamleider zonder cliënten → "Nog geen cliënten" + Cliënt-toevoegen-CTA
- [ ] `11-paginatie.png` — >15 cliënten → paginatie zichtbaar onderaan tabel
- [ ] `12-pest-output.png` — Terminal-output `./vendor/bin/pest tests/Feature/US-09.php` met 27 groene vinkjes
- [ ] `13-fullsuite-pest.png` — Terminal-output `./vendor/bin/pest` met alle 184+ tests groen

Opnamen volgen pas wanneer alle 16 user stories klaar zijn — per afspraak in één batch.
