# Screenshots — US-11 Concept-uren aanmaken en bewerken

Checklist voor de handmatige verificatie aan het einde van het project (batch-oplevering).

## Checklist

- [ ] `01-uren-index-concepten.png` — `/uren` als zorgbegeleider met 4 tabs + counts-badges + "Uren toevoegen"-knop
- [ ] `02-uren-create-form.png` — `/uren/create` met cliënt-dropdown (alleen eigen caseload) + datum + start/eindtijd + notities
- [ ] `03-uren-opgeslagen.png` — na POST → redirect `/uren?status=concept` + groene "Uren geregistreerd als concept (3,50 u)."-alert
- [ ] `04-validatie-eindtijd.png` — "Eindtijd moet na starttijd liggen"-fout + invoer behouden
- [ ] `05-validatie-future-datum.png` — "Uren in de toekomst registreren mag niet"-fout
- [ ] `06-tab-ingediend.png` — klik op Ingediend-tab → alleen ingediende rijen zichtbaar
- [ ] `07-uren-bewerken-concept.png` — `/uren/{id}/edit` met pre-filled velden + status-badge "Concept"
- [ ] `08-uren-bewerken-afgekeurd.png` — `/uren/{id}/edit` met status-badge "Afgekeurd" (ook editable)
- [ ] `09-uren-ingediend-readonly.png` — index ingediend-tab: rijen tonen "Read-only" tekst i.p.v. Bewerken-knop
- [ ] `10-uren-ingediend-403.png` — directe URL `/uren/{id}/edit` op ingediende entry → 403
- [ ] `11-empty-state-concepten.png` — nieuwe zorgbeg zonder uren → "Nog geen uren in deze tab" + "Uren toevoegen"-CTA
- [ ] `12-empty-state-geen-clienten.png` — zorgbeg zonder caseload opent `/uren/create` → "Geen cliënten toegewezen"-empty-state
- [ ] `13-teamleider-geen-create.png` — teamleider → `/uren/create` → 403
- [ ] `14-pest-output-us11.png` — Terminal `./vendor/bin/pest tests/Feature/US-11.php` → 28 groene vinkjes, 77 asserts
- [ ] `15-pest-full-suite.png` — Terminal `./vendor/bin/pest` → 212+ passed

Opnamen worden gemaakt wanneer alle 16 user stories klaar zijn — per afspraak in één batch.
