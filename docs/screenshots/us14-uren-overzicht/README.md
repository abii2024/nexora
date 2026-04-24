# Screenshots — US-14 Urenoverzicht met filters (teamleider)

Checklist voor de handmatige verificatie aan het einde van het project.

## Checklist

- [ ] `01-urenoverzicht-default.png` — `/teamleider/uren-overzicht` met 3 filters + 20-rijen tabel + week-summary-header
- [ ] `02-filter-status-afgekeurd.png` — status=afgekeurd in URL + alleen afgekeurde rijen
- [ ] `03-filter-medewerker-piet.png` — medewerker=Piet dropdown + alleen Piet's rijen
- [ ] `04-filter-week-2026-W17.png` — week-picker met ISO-week input
- [ ] `05-filters-kombinatie-url.png` — URL toont `?status=ingediend&medewerker=5&week=2026-W17`
- [ ] `06-paginatie-filter-behoud.png` — pagina 2 URL bevat nog steeds filters
- [ ] `07-sorteren-duur-desc.png` — klik op "Uren"-kolom → pijl ↓ + hoogste uren bovenaan
- [ ] `08-sorteren-duur-asc.png` — nog een klik → pijl ↑ + laagste uren bovenaan
- [ ] `09-week-summary-header.png` — "Piet: 38,00 · Anna: 38,00 · Totaal zichtbaar: 76,00 uur"
- [ ] `10-empty-state.png` — filter zonder resultaten → "Geen uren gevonden"
- [ ] `11-reset-knop.png` — Reset zichtbaar wanneer filters actief
- [ ] `12-zorgbeg-403.png` — zorgbeg → 403 op `/teamleider/uren-overzicht`
- [ ] `13-sidebar-urenoverzicht.png` — sidebar voor teamleider met actieve "Urenoverzicht"-link
- [ ] `14-pest-us14.png` — Terminal `./vendor/bin/pest tests/Feature/US-14.php` → 22 groen
- [ ] `15-pest-full-suite.png` — Terminal `./vendor/bin/pest` → 323 tests passed

Opnamen volgen aan einde van project.
