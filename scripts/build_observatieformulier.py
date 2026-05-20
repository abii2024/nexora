"""Genereer docs/observatieformulier/observatieformulier-nexora.{docx,pdf} — examen-observatieformulier B1-K1 & B1-K2."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

from docx import Document
from docx.enum.table import WD_ALIGN_VERTICAL, WD_ROW_HEIGHT_RULE
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Mm, Pt, RGBColor

ROOT = Path(__file__).resolve().parent.parent
OUTPUT_DIR = ROOT / "docs" / "observatieformulier"
OUTPUT_DOCX = OUTPUT_DIR / "observatieformulier-nexora.docx"
OUTPUT_PDF = OUTPUT_DIR / "observatieformulier-nexora.pdf"

HEADER_BG = "4A3358"
BODY_BG = "EDF4EF"
NOTE_BG = "FFFFFF"
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
BLACK = RGBColor(0x1A, 0x1A, 0x1A)
PURPLE = RGBColor(0x4A, 0x33, 0x58)
FONT = "Calibri"

PERSOON = [
    ("Datum", "20-05-2026"),
    ("Naam kandidaat", "Abdisamad Guled Abdulle"),
    ("Studentnummer", "9022236"),
    ("Klas/groep", "PALBVSOD3A"),
    ("Praktijkbeoordelaar", "Ömer Eryigit"),
    ("Schoolbeoordelaar", ""),
]

ALGEMEEN = [
    ("Examenvorm", "Praktijkexamen"),
    ("Kwalificatiedossier en cohort", "Softwaredevelopment — 2020 en verder"),
    ("Profiel en crebocode", "P1, Software Developer — 25604"),
    ("Kerntaak B1-K1", "B1-K1 Realiseert software"),
    (
        "Werkprocessen B1-K1",
        "W1 Plant werkzaamheden en bewaakt de voortgang · "
        "W2 Ontwerpt software · "
        "W3 Realiseert (onderdelen van) software · "
        "W4 Test software · "
        "W5 Doet verbetervoorstellen voor de software",
    ),
    ("Kerntaak B1-K2", "B1-K2 Werkt in een ontwikkelteam"),
    (
        "Werkprocessen B1-K2",
        "W1 Voert overleg · "
        "W2 Presenteert het opgeleverde werk · "
        "W3 Reflecteert op het werk",
    ),
    ("Project", "Nexora — zorgbegeleidingssysteem voor beschermd wonen (Laravel 12 + SQLite/PostgreSQL)"),
    ("Repository", "https://github.com/abii2024/nexora"),
]

WERKPROCESSEN = [
    {
        "kerntaak": "B1-K1 Realiseert software",
        "werkproces": "W1 — Plant werkzaamheden en bewaakt de voortgang",
        "criteria": [
            (
                "Eisen/wensen user stories",
                "16 user stories met expliciete eisen en wensen opgesteld in "
                "docs/user-stories.md, op basis van docs/eisen-wensen-uitgangspunten.md. "
                "Per US gekoppeld aan zorg-context (AVG, Wgbo, NEN 7510).",
            ),
            (
                "Criteria user stories",
                "Per US acceptatiecriteria + Definition of Done vastgelegd in "
                "docs/definition-of-done.md. AC's afgevinkt in DoD-checklist per US.",
            ),
            (
                "Planning maken",
                "4 sprints van 4 US's gepland (US-01..04, US-05..08, US-09..12, US-13..16). "
                "Sprintbacklog vastgelegd op Trello en gescreenshoot in "
                "docs/sprint-backlog-screenshots/begin-sprint/ (5 PNG's, prio's zichtbaar).",
            ),
            (
                "Voortgang bewaken",
                "Per sprint scrumboard-updates in docs/sprint-backlog-screenshots/. "
                "Sprint-tags sprint-1 t/m sprint-4 als snapshot na elke sprint. "
                "18 feature-PRs met groene tests vóór merge naar main.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K1 Realiseert software",
        "werkproces": "W2 — Ontwerpt software",
        "criteria": [
            (
                "Ontwerp",
                "Compleet ontwerpdocument docs/ontwerpdocument.md met drie deeldocumenten: "
                "verantwoorde verwerking (ethiek), gegevensbescherming (AVG), beveiliging "
                "(OWASP/NEN 7510). 32 wireframes (desktop + mobile) in docs/wireframes/.",
            ),
            (
                "Schematechnieken",
                "ERD in Mermaid (docs/erd-files/erd.mmd → erd.png), use-case in PlantUML "
                "(docs/usecase-files/usecase.puml → .png), flowchart urenregistratie in Mermaid "
                "(docs/flowchart-files/urenregistratie-workflow.mmd → .png).",
            ),
            (
                "Onderbouwing",
                "Per US een 'Waarom deze code-keuze' sectie in docs/code-bewijslast/README.md "
                "+ inline onderbouwing in docs/uitgewerkte-functionaliteiten/usNN-*/README.md "
                "(16 mappen). Architectuurkeuzes uitgelegd in docs/projectverslag.md §2.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K1 Realiseert software",
        "werkproces": "W3 — Realiseert (onderdelen van) software",
        "criteria": [
            (
                "Gerealiseerde user stories",
                "16 user stories volledig opgeleverd (US-01..US-16) + US-17 "
                "(Resend e-mail integratie als verbetervoorstel uit opdracht 4). "
                "Alle US's deployed naar Railway in productie.",
            ),
            (
                "Kwaliteit opgeleverde functionaliteiten",
                "Per US groene tests (Pest feature + unit) en handmatige browser-tests "
                "vastgelegd in docs/testplan/US-NN.md. Eindoordeel PASS voor alle 16 US's.",
            ),
            (
                "Kwaliteit code",
                "Laravel 12 best practices toegepast: Form Requests met validatedPayload(), "
                "Service-layer met DB::transaction(), Policies + middleware-combinatie, "
                "audit-trail pattern voor cliënt-mutaties (AVG art. 30), "
                "defense-in-depth (server-side scope-checks na policy/middleware).",
            ),
            (
                "Code conventions",
                "Pint (Laravel code-style) zonder warnings. Type-hinting strikt toegepast. "
                "Naming-conventies consistent (snake_case database, camelCase methods, "
                "kebab-case routes).",
            ),
            (
                "Verzorging code",
                "Geen dead code, consistente naming, geen TODO's, geen losse debug-statements. "
                "Refactored op patroon-niveau (Service-layer voor business logic, geen "
                "fat controllers).",
            ),
            (
                "Versie beheer",
                "Feature-branches per US (feature/us-NN-korte-naam), 18 PRs naar main met "
                "AC-checklist in PR-body, sprint-tags sprint-1..sprint-4 voor reproduceerbaarheid. "
                "Volledige git-graph + branch-overview in docs/github-bewijslast/README.md.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K1 Realiseert software",
        "werkproces": "W4 — Test software",
        "criteria": [
            (
                "Testplan",
                "Centraal testplan in docs/testplan/README.md: Pest feature-tests, unit-tests, "
                "handmatige browser-tests, regressie-tests. Testaccounts gedocumenteerd "
                "(teamleider, zorgbegeleider, inactieve gebruiker).",
            ),
            (
                "Testscenario",
                "16 testplan-bestanden docs/testplan/US-NN.md met TC-XX-tabellen "
                "(verwacht + werkelijk resultaat) per user story.",
            ),
            (
                "Testrapport",
                "Eindresultaat: 360 Pest-tests / 953 asserts — allemaal groen. "
                "Per sprint: Sprint-1 70 tests · Sprint-2 85 · Sprint-3 117 · Sprint-4 86. "
                "Per US conclusies (Functioneel / Privacy / Code quality / Eindoordeel PASS) "
                "in docs/testplan/US-NN.md §5.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K1 Realiseert software",
        "werkproces": "W5 — Doet verbetervoorstellen voor de software",
        "criteria": [
            (
                "Verbetervoorstel testen",
                "Analyse van testresultaten + PO-feedback + retrospectives in "
                "docs/uitgewerkte-functionaliteiten/opdracht-4-verbetervoorstellen/README.md §1-2. "
                "3 verbetervoorstellen geformuleerd (VV-1 t/m VV-3) met onderbouwing.",
            ),
            (
                "Verbetervoorstel oplevering",
                "US-17 (Resend e-mail) toegevoegd aan product backlog met tijdsinschatting "
                "(4 uur) + prioriteit (Should have) + Trello-screenshot. Vervolgens "
                "geïmplementeerd: resend/resend-laravel package, MAIL_MAILER=resend, "
                "mail aantoonbaar aangekomen in Gmail-inbox.",
            ),
            (
                "Reflectie",
                "Per sprint reflectie-document docs/reflectie/ met positieve en verbeterpunten "
                "voor proces, samenwerking met PO, en eigen prestaties. Overkoepelende "
                "reflectie in docs/reflectie/README.md §6.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K2 Werkt in een ontwikkelteam",
        "werkproces": "W1 — Voert overleg",
        "criteria": [
            (
                "Actieve deelname",
                "Wekelijkse Trello-reviews met Product Owner Badreddine. Trello-bord zelf "
                "opgezet inclusief AC's per US op basis van Badreddine's project. "
                "PO uitgevoerd review-acties + comments op user stories.",
            ),
            (
                "Afstemmen",
                "Bij elke US vooraf afstemming met PO (acceptatiecriteria gevalideerd). "
                "Aanpassingen op basis van feedback gedocumenteerd in "
                "docs/overleggen/README.md §2 + projectverslag §11.",
            ),
            (
                "Afspraken vastleggen",
                "Afspraken-tabel in docs/overleggen/README.md (3 screenshots Trello-"
                "activiteitenlog + tabel afspraak → actie → bewijs). Sprint-backlog "
                "screenshots per sprint als snapshot van afspraken-status.",
            ),
            (
                "Afspraken nakomen",
                "Alle 16 user stories opgeleverd binnen geplande sprint. Per afspraak een "
                "concrete actie + bewijs in docs/overleggen/README.md §4. Sprint-tags "
                "valideren afspraak-naleving (alle 4 sprints op tijd gesloten).",
            ),
        ],
    },
    {
        "kerntaak": "B1-K2 Werkt in een ontwikkelteam",
        "werkproces": "W2 — Presenteert het opgeleverde werk",
        "criteria": [
            (
                "Presentatie",
                "Examen-presentatie nexora-examen-presentatie.pptx opgeleverd "
                "(docs/presentatie/). Volledige slidedeck: probleem, architectuur, demo's "
                "per US-cluster, testresultaten, reflectie.",
            ),
            (
                "Informeren betrokkenen",
                "PO continu geïnformeerd via Trello (status per US zichtbaar in board) + "
                "sprint-reviews. Beoordelaar geïnformeerd via docs/examen-checklist.md "
                "(klikbare leesgids met alle bewijslast).",
            ),
            (
                "Reactie op feedback",
                "PO-feedback per sprint verwerkt in volgende sprints (zichtbaar in "
                "docs/reflectie/ per sprint). Opdracht-4 verbetervoorstellen (US-17 Resend) "
                "ontstaan uit PO-feedback over ontbrekende mail-bevestiging.",
            ),
        ],
    },
    {
        "kerntaak": "B1-K2 Werkt in een ontwikkelteam",
        "werkproces": "W3 — Reflecteert op het werk",
        "criteria": [
            (
                "Feedbackproces",
                "Per sprint reflectie in docs/reflectie/sprint-N.md met expliciete "
                "+/− /→ secties voor Proces, Samenwerking met PO en Eigen prestaties. "
                "Overkoepelende reflectie §6 met patronen over alle 4 sprints heen.",
            ),
            (
                "Reactie op feedback",
                "Feedback uit retrospectives direct doorgevoerd: o.a. testdekking "
                "verhoogd na sprint-2 retro (van 85 naar 117 tests in sprint-3), "
                "code-bewijslast met permalinks toegevoegd na PO-vraag in sprint-3 retro.",
            ),
            (
                "Proactieve houding",
                "Proactief Trello-bord opgezet met AC's per US (PO deed alleen review). "
                "US-17 (Resend mail) zelf geïdentificeerd als verbetervoorstel + dezelfde "
                "sessie geïmplementeerd en aangetoond werkend. Examen-checklist met live "
                "GitHub-permalinks als leesgids voor beoordelaar opgezet.",
            ),
        ],
    },
]


def shade(cell, hex_color: str) -> None:
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hex_color)
    cell._tc.get_or_add_tcPr().append(shd)


def set_borders(table, color: str = "B8AEC5") -> None:
    tbl = table._tbl
    tblPr = tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        b = OxmlElement(f"w:{edge}")
        b.set(qn("w:val"), "single")
        b.set(qn("w:sz"), "4")
        b.set(qn("w:color"), color)
        borders.append(b)
    tblPr.append(borders)


def set_cell_margins(cell, top: int = 80, bottom: int = 80, left: int = 120, right: int = 120) -> None:
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement("w:tcMar")
    for side, value in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        node = OxmlElement(f"w:{side}")
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")
        tcMar.append(node)
    tcPr.append(tcMar)


def write_cell(
    cell,
    text: str,
    *,
    bold: bool = False,
    color: RGBColor = BLACK,
    size: int = 10,
    align=WD_ALIGN_PARAGRAPH.LEFT,
    vertical=WD_ALIGN_VERTICAL.CENTER,
) -> None:
    cell.text = ""
    p = cell.paragraphs[0]
    p.alignment = align
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    run = p.add_run(text)
    run.bold = bold
    run.font.name = FONT
    run.font.size = Pt(size)
    run.font.color.rgb = color
    cell.vertical_alignment = vertical
    set_cell_margins(cell)


def set_row_height(row, mm: float, exact: bool = False) -> None:
    row.height = Mm(mm)
    row.height_rule = WD_ROW_HEIGHT_RULE.EXACTLY if exact else WD_ROW_HEIGHT_RULE.AT_LEAST


def add_heading(doc: Document, text: str, size: int = 18, space_before: int = 0, space_after: int = 6) -> None:
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    run = p.add_run(text)
    run.bold = True
    run.font.name = FONT
    run.font.size = Pt(size)
    run.font.color.rgb = PURPLE


def add_spacer(doc: Document, pt: int = 4) -> None:
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(pt)


def add_key_value_table(doc: Document, title: str, rows: list[tuple[str, str]], left_cm: float = 5.0) -> None:
    table = doc.add_table(rows=len(rows) + 1, cols=2)
    table.autofit = False
    set_borders(table)

    header_cells = table.rows[0].cells
    header_cells[0].merge(header_cells[1])
    write_cell(
        header_cells[0],
        title,
        bold=True,
        color=WHITE,
        size=11,
        align=WD_ALIGN_PARAGRAPH.CENTER,
    )
    shade(header_cells[0], HEADER_BG)
    set_row_height(table.rows[0], 7)

    for i, (label, value) in enumerate(rows, start=1):
        write_cell(table.rows[i].cells[0], label, bold=True)
        write_cell(table.rows[i].cells[1], value)
        shade(table.rows[i].cells[0], BODY_BG)
        shade(table.rows[i].cells[1], NOTE_BG)
        table.rows[i].cells[0].width = Cm(left_cm)
        table.rows[i].cells[1].width = Cm(17.0 - left_cm)
        set_row_height(table.rows[i], 7)


def add_werkproces(doc: Document, kerntaak: str, werkproces: str, criteria: list[tuple[str, str]]) -> None:
    add_heading(doc, kerntaak, size=13, space_before=6, space_after=2)
    add_heading(doc, werkproces, size=11, space_before=0, space_after=4)

    table = doc.add_table(rows=len(criteria) + 1, cols=2)
    table.autofit = False
    set_borders(table)

    header = table.rows[0].cells
    write_cell(header[0], "Beoordelingscriteria", bold=True, color=WHITE, size=11)
    write_cell(header[1], "Aantekeningen / bewijs", bold=True, color=WHITE, size=11)
    shade(header[0], HEADER_BG)
    shade(header[1], HEADER_BG)
    set_row_height(table.rows[0], 7)

    for i, (criterium, aantekening) in enumerate(criteria, start=1):
        write_cell(table.rows[i].cells[0], criterium, bold=True, vertical=WD_ALIGN_VERTICAL.TOP)
        write_cell(table.rows[i].cells[1], aantekening, vertical=WD_ALIGN_VERTICAL.TOP)
        shade(table.rows[i].cells[0], BODY_BG)
        shade(table.rows[i].cells[1], NOTE_BG)
        table.rows[i].cells[0].width = Cm(5.0)
        table.rows[i].cells[1].width = Cm(12.0)


def add_handtekeningen(doc: Document) -> None:
    add_heading(doc, "Handtekeningen", size=14, space_before=10, space_after=4)

    table = doc.add_table(rows=5, cols=4)
    table.autofit = False
    set_borders(table)

    header = table.rows[0].cells
    write_cell(header[0], "Rol", bold=True, color=WHITE, size=11)
    write_cell(header[1], "Naam", bold=True, color=WHITE, size=11)
    write_cell(header[2], "Datum", bold=True, color=WHITE, size=11)
    write_cell(header[3], "Handtekening", bold=True, color=WHITE, size=11)
    for c in range(4):
        shade(header[c], HEADER_BG)
    set_row_height(table.rows[0], 7)

    widths = [Cm(4.0), Cm(5.0), Cm(2.5), Cm(5.5)]

    def fill_block(start_row: int, rol: str) -> None:
        # Eerste rij: persoonsgegevens, lege handtekening
        cells = table.rows[start_row].cells
        write_cell(cells[0], rol, bold=True, vertical=WD_ALIGN_VERTICAL.TOP)
        write_cell(cells[1], "", vertical=WD_ALIGN_VERTICAL.TOP)
        write_cell(cells[2], "20-05-2026", vertical=WD_ALIGN_VERTICAL.TOP)
        write_cell(cells[3], "", vertical=WD_ALIGN_VERTICAL.TOP)
        shade(cells[0], BODY_BG)
        for c in range(1, 4):
            shade(cells[c], NOTE_BG)
        for c in range(4):
            cells[c].width = widths[c]
        set_row_height(table.rows[start_row], 25, exact=True)

        # Tweede rij: voor scan/print spacer
        spacer = table.rows[start_row + 1].cells
        spacer[0].merge(spacer[1]).merge(spacer[2]).merge(spacer[3])
        write_cell(
            spacer[0],
            "Voor akkoord — ondertekening na print of digitaal",
            color=RGBColor(0x6C, 0x5B, 0x7C),
            size=8,
            align=WD_ALIGN_PARAGRAPH.CENTER,
        )
        shade(spacer[0], NOTE_BG)
        set_row_height(table.rows[start_row + 1], 4)

    fill_block(1, "Praktijkbeoordelaar")
    fill_block(3, "Schoolbeoordelaar")


def build_docx() -> None:
    doc = Document()

    # Page setup — wat ruimer dan logbook want we hebben veel tabellen
    style = doc.styles["Normal"]
    style.font.name = FONT
    style.font.size = Pt(10)

    for section in doc.sections:
        section.left_margin = Cm(1.8)
        section.right_margin = Cm(1.8)
        section.top_margin = Cm(1.4)
        section.bottom_margin = Cm(1.4)

    # Titel
    add_heading(doc, "Observatieformulier B1-K1 & B1-K2", size=20, space_after=4)
    add_heading(doc, "Praktijkexamen Software Developer — crebo 25604", size=11, space_after=8)

    # Algemene informatie + persoonsinformatie
    add_key_value_table(doc, "Algemene informatie", ALGEMEEN, left_cm=5.5)
    add_spacer(doc)
    add_key_value_table(doc, "Persoonsinformatie", PERSOON, left_cm=5.0)
    add_spacer(doc, pt=8)

    # Werkprocessen
    for wp in WERKPROCESSEN:
        add_werkproces(doc, wp["kerntaak"], wp["werkproces"], wp["criteria"])
        add_spacer(doc)

    # Handtekeningen
    add_handtekeningen(doc)

    OUTPUT_DOCX.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUTPUT_DOCX)
    print(f"Wrote {OUTPUT_DOCX.relative_to(ROOT)}")


def convert_to_pdf() -> bool:
    """Converteer DOCX → PDF via docx2pdf (Word.app op macOS) of pandoc als fallback."""
    try:
        from docx2pdf import convert
        convert(str(OUTPUT_DOCX), str(OUTPUT_PDF))
        if OUTPUT_PDF.exists():
            print(f"Wrote {OUTPUT_PDF.relative_to(ROOT)}")
            return True
        print("docx2pdf voltooide maar PDF niet aangemaakt", file=sys.stderr)
    except Exception as exc:  # noqa: BLE001
        print(f"docx2pdf faalde: {exc}", file=sys.stderr)

    # Fallback: pandoc met weasyprint
    try:
        import os
        env = os.environ.copy()
        brew_lib = "/opt/homebrew/lib"
        if Path(brew_lib).is_dir():
            existing = env.get("DYLD_FALLBACK_LIBRARY_PATH", "")
            env["DYLD_FALLBACK_LIBRARY_PATH"] = f"{brew_lib}:{existing}" if existing else brew_lib
        result = subprocess.run(
            [
                "pandoc",
                str(OUTPUT_DOCX),
                "-o", str(OUTPUT_PDF),
                "--pdf-engine=weasyprint",
                "--standalone",
            ],
            check=False,
            env=env,
        )
        if result.returncode == 0:
            print(f"Wrote {OUTPUT_PDF.relative_to(ROOT)} (via pandoc fallback)")
            return True
    except FileNotFoundError:
        print("pandoc niet gevonden", file=sys.stderr)
    return False


def main() -> int:
    build_docx()
    if not convert_to_pdf():
        print(
            "PDF-conversie mislukt. DOCX is wel aangemaakt — open in Word en exporteer "
            "handmatig naar PDF, of installeer LibreOffice voor headless conversie.",
            file=sys.stderr,
        )
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
