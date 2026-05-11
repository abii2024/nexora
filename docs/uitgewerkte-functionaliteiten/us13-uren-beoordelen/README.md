# Screenshots — US-13 Uren goedkeuren of afkeuren als teamleider

Checklist voor de handmatige verificatie aan het einde van het project (batch-oplevering).

## Checklist

- [ ] `01-teamleider-uren-index.png` — `/teamleider/uren` met tabel per medewerker + subtotaal-header + Goedkeuren/Afkeuren-knoppen
- [ ] `02-empty-state.png` — `/teamleider/uren` zonder ingediende uren → "Niets te beoordelen"
- [ ] `03-goedkeuren-flash.png` — na POST goedkeuren → redirect + groene "Uren goedgekeurd"-alert
- [ ] `04-afkeur-modal-geopend.png` — Native `<dialog>` met textarea open
- [ ] `05-afkeur-validatie-min10.png` — Server-side fout "Schrijf minstens 10 tekens uitleg"
- [ ] `06-afkeur-flash.png` — na valide afkeur → redirect + groene "Uren afgekeurd"-alert
- [ ] `07-zorgbeg-bell-goedgekeurd.png` — zorgbeg notifications-tabel met `type=uren_goedgekeurd`
- [ ] `08-zorgbeg-bell-afgekeurd.png` — zorgbeg notifications-tabel met `type=uren_afgekeurd` + reden in payload
- [ ] `09-zorgbeg-edit-banner.png` — `/uren/{id}/edit` op afgekeurde entry: rode/gele banner met teamleider-notitie bovenaan
- [ ] `10-zorgbeg-resubmit-flash.png` — na resubmit → redirect Ingediend-tab + flash
- [ ] `11-cross-team-403.png` — vreemde-team teamleider → 403 op `/teamleider/uren/{id}/goedkeuren`
- [ ] `12-zorgbeg-403.png` — zorgbeg → 403 op `/teamleider/uren`
- [ ] `13-sidebar-uren-beoordelen.png` — sidebar van teamleider met actieve "Uren beoordelen"-link
- [ ] `14-pest-us13.png` — Terminal `./vendor/bin/pest tests/Feature/US-13.php` → 27 groen
- [ ] `15-pest-full-suite.png` — Terminal `./vendor/bin/pest` → 301 tests passed

Opnamen volgen aan einde van alle 16 user stories — per afspraak.
