"""Nexora examen-presentatie generator (20 slides, ~18 min).

Genereert docs/presentatie/nexora-examen-presentatie.pptx + spreekteksten.md.
Run: python3 scripts/build_presentatie.py
"""

from __future__ import annotations

from pathlib import Path

from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.util import Inches, Pt, Emu

ROOT = Path(__file__).resolve().parent.parent
DOCS = ROOT / "docs"
OUT_DIR = DOCS / "presentatie"
OUT_PPTX = OUT_DIR / "nexora-examen-presentatie.pptx"
OUT_NOTES = OUT_DIR / "spreekteksten.md"

SLIDE_W = Inches(13.333)
SLIDE_H = Inches(7.5)
TOTAL_SLIDES = 20

PRIMARY = RGBColor(0x02, 0x80, 0x90)
PRIMARY_DARK = RGBColor(0x01, 0x55, 0x61)
SECONDARY = RGBColor(0x84, 0xB5, 0x9F)
ACCENT = RGBColor(0x1E, 0x27, 0x61)
BG_LIGHT = RGBColor(0xF8, 0xFA, 0xFA)
BG_PANEL = RGBColor(0xEC, 0xF3, 0xF4)
TEXT = RGBColor(0x1A, 0x23, 0x33)
TEXT_MUTED = RGBColor(0x5A, 0x6C, 0x7D)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
SUCCESS = RGBColor(0x2D, 0x7A, 0x3F)
RED = RGBColor(0xC4, 0x45, 0x69)

FONT_HEAD = "Calibri"
FONT_BODY = "Calibri"

SPEAKER_NOTES: list[tuple[int, str, str]] = []


# ---------- Helpers ----------

def add_blank_slide(prs, bg_color=BG_LIGHT):
    slide = prs.slides.add_slide(prs.slide_layouts[6])
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, SLIDE_W, SLIDE_H)
    bg.line.fill.background()
    bg.fill.solid()
    bg.fill.fore_color.rgb = bg_color
    bg.shadow.inherit = False
    return slide


def add_text(slide, left, top, width, height, text, *,
             size=14, bold=False, color=TEXT, align=PP_ALIGN.LEFT,
             anchor=MSO_ANCHOR.TOP, font=FONT_BODY, italic=False):
    box = slide.shapes.add_textbox(left, top, width, height)
    tf = box.text_frame
    tf.margin_left = Emu(0); tf.margin_right = Emu(0)
    tf.margin_top = Emu(0); tf.margin_bottom = Emu(0)
    tf.word_wrap = True
    tf.vertical_anchor = anchor
    p = tf.paragraphs[0]; p.alignment = align
    run = p.add_run()
    run.text = text
    run.font.name = font; run.font.size = Pt(size)
    run.font.bold = bold; run.font.italic = italic
    run.font.color.rgb = color
    return box


def add_multi_text(slide, left, top, width, height, paragraphs, *,
                   line_spacing=1.15, anchor=MSO_ANCHOR.TOP):
    box = slide.shapes.add_textbox(left, top, width, height)
    tf = box.text_frame
    tf.margin_left = Emu(0); tf.margin_right = Emu(0)
    tf.margin_top = Emu(0); tf.margin_bottom = Emu(0)
    tf.word_wrap = True
    tf.vertical_anchor = anchor
    for i, (txt, st) in enumerate(paragraphs):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = st.get("align", PP_ALIGN.LEFT)
        p.line_spacing = line_spacing
        if st.get("space_after"):
            p.space_after = Pt(st["space_after"])
        run = p.add_run()
        run.text = txt
        run.font.name = st.get("font", FONT_BODY)
        run.font.size = Pt(st.get("size", 14))
        run.font.bold = st.get("bold", False)
        run.font.italic = st.get("italic", False)
        run.font.color.rgb = st.get("color", TEXT)
    return box


def add_rect(slide, left, top, width, height, fill):
    shp = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, left, top, width, height)
    shp.fill.solid(); shp.fill.fore_color.rgb = fill
    shp.line.fill.background(); shp.shadow.inherit = False
    return shp


def add_rounded(slide, left, top, width, height, fill):
    shp = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, left, top, width, height)
    shp.fill.solid(); shp.fill.fore_color.rgb = fill
    shp.line.fill.background(); shp.shadow.inherit = False
    shp.adjustments[0] = 0.12
    return shp


def add_footer(slide, idx):
    add_text(slide, Inches(0.5), Inches(7.05), Inches(8), Inches(0.35),
             "Nexora  ·  PvB Software Developer N4  ·  Abdisamad",
             size=10, color=TEXT_MUTED, font=FONT_BODY)
    add_text(slide, Inches(11.5), Inches(7.05), Inches(1.3), Inches(0.35),
             f"{idx} / {TOTAL_SLIDES}",
             size=10, color=TEXT_MUTED, align=PP_ALIGN.RIGHT, font=FONT_BODY)


def add_title_bar(slide, title, kicker=None):
    add_rect(slide, Inches(0.5), Inches(0.55), Inches(0.08), Inches(0.55), PRIMARY)
    if kicker:
        add_text(slide, Inches(0.75), Inches(0.45), Inches(11), Inches(0.3),
                 kicker.upper(), size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
        add_text(slide, Inches(0.75), Inches(0.78), Inches(11), Inches(0.55),
                 title, size=30, bold=True, color=TEXT, font=FONT_HEAD)
    else:
        add_text(slide, Inches(0.75), Inches(0.55), Inches(12), Inches(0.7),
                 title, size=32, bold=True, color=TEXT, font=FONT_HEAD)


def add_notes(slide, idx, title, notes_text):
    slide.notes_slide.notes_text_frame.text = notes_text.strip()
    SPEAKER_NOTES.append((idx, title, notes_text.strip()))


# ---------- Slides ----------

def slide_01_title(prs, idx):
    s = add_blank_slide(prs, PRIMARY_DARK)
    add_rect(s, 0, Inches(5.5), SLIDE_W, Inches(0.06), SECONDARY)
    add_text(s, Inches(0.75), Inches(1.6), Inches(11), Inches(0.4),
             "PVB SOFTWARE DEVELOPER  ·  MBO NIVEAU 4",
             size=14, bold=True, color=SECONDARY, font=FONT_HEAD)
    add_text(s, Inches(0.75), Inches(2.1), Inches(11.5), Inches(1.4),
             "Nexora", size=80, bold=True, color=WHITE, font=FONT_HEAD)
    add_text(s, Inches(0.75), Inches(3.4), Inches(11.5), Inches(0.9),
             "Zorgbegeleidingssysteem voor beschermd wonen",
             size=26, color=WHITE, font=FONT_BODY)
    add_text(s, Inches(0.75), Inches(4.1), Inches(11.5), Inches(0.5),
             "Webapplicatie voor zorgbegeleiders en teamleiders — AVG-compliant, getest, gedocumenteerd.",
             size=14, italic=True, color=SECONDARY, font=FONT_BODY)
    add_text(s, Inches(0.75), Inches(5.95), Inches(6), Inches(0.4),
             "Abdisamad", size=20, bold=True, color=WHITE, font=FONT_HEAD)
    add_text(s, Inches(0.75), Inches(6.35), Inches(6), Inches(0.35),
             "Examenproject — mei 2026", size=14, color=SECONDARY, font=FONT_BODY)
    add_text(s, Inches(7.5), Inches(5.95), Inches(5.3), Inches(0.4),
             "github.com/abii2024/nexora",
             size=14, color=WHITE, align=PP_ALIGN.RIGHT, font=FONT_BODY)
    add_text(s, Inches(7.5), Inches(6.35), Inches(5.3), Inches(0.35),
             "Laravel 12  ·  PHP 8.4  ·  Pest v4",
             size=12, color=SECONDARY, align=PP_ALIGN.RIGHT, font=FONT_BODY)
    add_notes(s, idx, "Titelslide", (
        "Goedemorgen / goedemiddag. Mijn naam is Abdisamad en ik presenteer mijn afstudeerproject "
        "Nexora — een zorgbegeleidingssysteem voor beschermd wonen. Dit is mijn praktijkbeoordeling voor "
        "Software Developer MBO niveau 4. Vandaag laat ik zien hoe ik 16 user stories heb gerealiseerd "
        "in 4 sprints, getest met 360 Pest-tests die allemaal groen zijn, en hoe ik alle examen-werkprocessen "
        "B1, K1, K2, W3 en W5 in mijn project heb verwerkt. Ik neem jullie mee door wat Nexora is, "
        "het werkproces, een korte demo en mijn reflectie."
    ))


def slide_02_agenda(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Wat ga ik laten zien", kicker="Agenda")
    blocks = [
        ("01", "Project", "Probleem · doelgroep · oplossing"),
        ("02", "Analyse (B1)", "Eisen · wireframes · ERD · use-cases"),
        ("03", "Realisatie (K1)", "16 user stories in 3 blokken · tests"),
        ("04", "Overleg (K2)", "Samenwerking met PO Badreddine"),
        ("05", "Verbeteren & reflectie (W5 + W3)", "Verbetervoorstellen + retro's per sprint"),
    ]
    top = Inches(2.0); h = Inches(4.2)
    margin = Inches(0.5); gap = Inches(0.2)
    available = SLIDE_W - margin * 2 - gap * (len(blocks) - 1)
    w = Emu(int(available / len(blocks)))
    for i, (num, hdr, body) in enumerate(blocks):
        left = Emu(int(margin + (w + gap) * i))
        add_rounded(s, left, top, w, h, BG_PANEL)
        add_rounded(s, left + Inches(0.3), top + Inches(0.3), Inches(0.9), Inches(0.55), PRIMARY)
        add_text(s, left + Inches(0.3), top + Inches(0.32), Inches(0.9), Inches(0.55),
                 num, size=18, bold=True, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left + Inches(0.3), top + Inches(1.1), w - Inches(0.6), Inches(0.6),
                 hdr, size=16, bold=True, color=TEXT, font=FONT_HEAD)
        add_text(s, left + Inches(0.3), top + Inches(1.75), w - Inches(0.6), Inches(2.0),
                 body, size=12, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Agenda", (
        "Mijn presentatie heeft vijf blokken. Eerst het project: wat is Nexora en voor wie. Daarna het "
        "analyseren — eisen, ontwerp en planning. Het derde blok is de realisatie: ik loop de zestien "
        "user stories langs in drie functionele blokken met screenshots — authenticatie en teambeheer, "
        "cliëntdossiers, en urenregistratie. Vervolgens hoe ik samenwerk met mijn product owner Badreddine. "
        "Tot slot het verbetervoorstel dat is doorgevoerd plus mijn reflectie per sprint. Ik probeer rond "
        "de achttien minuten te blijven en eindig met ruimte voor vragen."
    ))


def slide_03_what_is_nexora(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Wat is Nexora?", kicker="Het project")
    add_multi_text(s, Inches(0.5), Inches(1.85), Inches(6.5), Inches(5.0), [
        ("Nexora is een webapplicatie voor zorgorganisaties die beschermd wonen aanbieden.",
         {"size": 18, "bold": True, "color": TEXT, "space_after": 14}),
        ("Het ondersteunt zorgbegeleiders en teamleiders bij hun dagelijkse werk.",
         {"size": 14, "color": TEXT_MUTED, "space_after": 18}),
        ("Vijf kern-functies:",
         {"size": 14, "bold": True, "color": PRIMARY, "space_after": 8}),
        ("•  Cliëntdossiers beheren (persoonsgegevens, zorgtype, status)",
         {"size": 13, "color": TEXT, "space_after": 4}),
        ("•  Begeleiders koppelen (primair · secundair · tertiair)",
         {"size": 13, "color": TEXT, "space_after": 4}),
        ("•  Urenregistratie — van concept tot goedkeuring",
         {"size": 13, "color": TEXT, "space_after": 4}),
        ("•  Teambeheer (medewerkers + rollen)",
         {"size": 13, "color": TEXT, "space_after": 4}),
        ("•  Rolgebaseerde toegang via Policies + middleware",
         {"size": 13, "color": TEXT, "space_after": 4}),
    ])
    shot = DOCS / "uitgewerkte-functionaliteiten" / "us04-medewerkers-overzicht" / "01-medewerkers-overzicht.png"
    if shot.exists():
        add_rect(s, Inches(7.3), Inches(1.85), Inches(5.55), Inches(4.4), SECONDARY)
        s.shapes.add_picture(str(shot), Inches(7.35), Inches(1.9),
                             width=Inches(5.45), height=Inches(4.3))
        add_text(s, Inches(7.3), Inches(6.35), Inches(5.55), Inches(0.3),
                 "Medewerkersoverzicht — Nexora dashboard",
                 size=10, italic=True, color=TEXT_MUTED, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Wat is Nexora?", (
        "Nexora is een webapplicatie die zorgorganisaties helpt met beschermd wonen. "
        "Stel je een woongroep voor met twintig kwetsbare cliënten die elk een team van zorgbegeleiders "
        "om zich heen hebben. Die begeleiders moeten dagelijks uren registreren per cliënt, "
        "teamleiders moeten die uren goedkeuren, en alle persoonsgegevens moeten AVG-compliant worden "
        "bewaard. Nexora vangt dat in vijf kernfuncties: cliëntdossiers, begeleider-koppelingen, "
        "urenregistratie, teambeheer en rol-gebaseerde toegang. Rechts zie je het medewerkersoverzicht."
    ))


def slide_04_audience_roles(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Doelgroep, rollen en wettelijk kader", kicker="Context")
    panel_top = Inches(1.65); panel_h = Inches(3.65); panel_w = Inches(6.05)
    # Zorgbegeleider
    add_rounded(s, Inches(0.5), panel_top, panel_w, panel_h, BG_PANEL)
    add_rounded(s, Inches(0.5), panel_top, Inches(0.18), panel_h, PRIMARY)
    add_text(s, Inches(0.85), panel_top + Inches(0.2), panel_w - Inches(0.5), Inches(0.3),
             "ZORGBEGELEIDER", size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
    add_text(s, Inches(0.85), panel_top + Inches(0.55), panel_w - Inches(0.5), Inches(0.55),
             "Eigen caseload", size=24, bold=True, color=TEXT, font=FONT_HEAD)
    add_text(s, Inches(0.85), panel_top + Inches(1.15), panel_w - Inches(0.5), Inches(0.3),
             "Wat kan hij doen?", size=10, bold=True, color=TEXT_MUTED, font=FONT_HEAD)
    add_multi_text(s, Inches(0.85), panel_top + Inches(1.5),
                   panel_w - Inches(0.5), Inches(2.0), [
        ("•  Inloggen + eigen profiel / wachtwoord beheren",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Eigen cliënten inzien (gefilterd door koppeling)",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Cliëntdossier bekijken (rol-gefilterd)",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Concept-uren aanmaken en bewerken per cliënt",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Uren indienen, terugtrekken, resubmit na afkeuring",
         {"size": 12, "color": TEXT, "space_after": 5}),
    ])
    # Teamleider
    tl_left = Inches(6.78)
    add_rounded(s, tl_left, panel_top, panel_w, panel_h, BG_PANEL)
    add_rounded(s, tl_left, panel_top, Inches(0.18), panel_h, ACCENT)
    add_text(s, tl_left + Inches(0.35), panel_top + Inches(0.2),
             panel_w - Inches(0.5), Inches(0.3),
             "TEAMLEIDER", size=11, bold=True, color=ACCENT, font=FONT_HEAD)
    add_text(s, tl_left + Inches(0.35), panel_top + Inches(0.55),
             panel_w - Inches(0.5), Inches(0.55),
             "Volledig beheer", size=24, bold=True, color=TEXT, font=FONT_HEAD)
    add_text(s, tl_left + Inches(0.35), panel_top + Inches(1.15),
             panel_w - Inches(0.5), Inches(0.3),
             "Alles wat zorgbegeleider kan + extra rechten:",
             size=10, bold=True, color=TEXT_MUTED, font=FONT_HEAD)
    add_multi_text(s, tl_left + Inches(0.35), panel_top + Inches(1.5),
                   panel_w - Inches(0.5), Inches(2.0), [
        ("•  Medewerkers aanmaken, bewerken, (de)activeren",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Cliëntdossiers aanmaken, bewerken, archiveren",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Begeleiders koppelen — primair / secundair / tertiair",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Uren goedkeuren of afkeuren (met verplichte reden)",
         {"size": 12, "color": TEXT, "space_after": 5}),
        ("•  Filter-rich urenoverzicht over hele organisatie",
         {"size": 12, "color": TEXT, "space_after": 5}),
    ])
    # Wettelijk kader
    law_top = Inches(5.5); law_h = Inches(1.5); law_w = Inches(4.1)
    laws = [
        ("AVG", "art. 5 · 9 · 30 · 32",
         "Dataminimalisatie · bijzondere persoonsgegevens · verwerkingsregister · beveiliging"),
        ("Wgbo", "Bewaartermijn 20 jaar",
         "Medisch / zorg-dossier · recht op rectificatie en inzage"),
        ("NEN 7510", "Informatiebeveiliging zorg",
         "Leidraad voor authenticatie, autorisatie, audit-trail en versleuteling"),
    ]
    for i, (h, sub, body) in enumerate(laws):
        left = Inches(0.5) + (law_w + Inches(0.1)) * i
        add_rounded(s, left, law_top, law_w, law_h, BG_PANEL)
        add_text(s, left + Inches(0.2), law_top + Inches(0.15),
                 law_w - Inches(0.4), Inches(0.4),
                 h, size=16, bold=True, color=PRIMARY, font=FONT_HEAD)
        add_text(s, left + Inches(0.2), law_top + Inches(0.5),
                 law_w - Inches(0.4), Inches(0.3),
                 sub, size=10, bold=True, color=ACCENT, font=FONT_HEAD)
        add_text(s, left + Inches(0.2), law_top + Inches(0.8),
                 law_w - Inches(0.4), Inches(0.65),
                 body, size=10, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Doelgroep en rollen", (
        "Er zijn twee gebruikersrollen in Nexora. Een zorgbegeleider mag inloggen, eigen cliënten inzien — "
        "gefilterd op koppeling — en voor die cliënten uren registreren. Een teamleider heeft alles wat een "
        "zorgbegeleider kan plus medewerkers en cliëntdossiers beheren, begeleiders koppelen, en uren "
        "goedkeuren of afkeuren. Dat rolonderscheid wordt afgedwongen via Laravel Policies én middleware — "
        "defense in depth. Onderaan zie je het wettelijk kader: AVG, Wgbo en NEN 7510."
    ))


def slide_05_assessment(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Beoordelingskader: B1 · K1 · K2 · W3 · W5",
                  kicker="30 criteria · 6 categorieën")
    pillars = [
        ("B1", "Analyseert", "Eisen, ontwerp & planning", "12 criteria",
         "eisen-wensen · user-stories · ERD · use-cases · wireframes"),
        ("K1", "Realiseert", "Functionaliteit + bewijslast", "3 criteria",
         "16 user stories · 18 PRs · 257 commits · 4 sprint-tags"),
        ("K1", "Realiseert", "Testen", "4 criteria",
         "360 Pest-tests · 953 asserts · per-US testplannen"),
        ("K2", "Onderhoudt", "Overleg met PO", "3 criteria",
         "Trello-activiteitenlog · sprint-reviews · PO-comments"),
        ("W5", "Verbetert", "Verbetervoorstellen", "5 criteria",
         "VV-1, VV-2, VV-3 · US-17 (Resend) doorgevoerd"),
        ("W3", "Reflecteert", "Reflectie per sprint", "3 criteria",
         "Per sprint +/− /→ op proces · PO · prestaties"),
    ]
    top = Inches(1.85); card_w = Inches(4.1); card_h = Inches(2.45)
    gap_x = Inches(0.15); gap_y = Inches(0.18)
    for i, (code, label, title, count, body) in enumerate(pillars):
        row, col = divmod(i, 3)
        left = Inches(0.5) + (card_w + gap_x) * col
        top_i = top + (card_h + gap_y) * row
        add_rounded(s, left, top_i, card_w, card_h, BG_PANEL)
        add_rounded(s, left + Inches(0.25), top_i + Inches(0.2), Inches(0.9), Inches(0.45), PRIMARY)
        add_text(s, left + Inches(0.25), top_i + Inches(0.22), Inches(0.9), Inches(0.45),
                 code, size=14, bold=True, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left + Inches(1.25), top_i + Inches(0.25), Inches(2.7), Inches(0.4),
                 label.upper(), size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
        add_text(s, left + Inches(0.25), top_i + Inches(0.75), card_w - Inches(0.5), Inches(0.5),
                 title, size=15, bold=True, color=TEXT, font=FONT_HEAD)
        add_text(s, left + Inches(0.25), top_i + Inches(1.25), card_w - Inches(0.5), Inches(0.3),
                 count, size=11, italic=True, color=ACCENT, font=FONT_BODY)
        add_text(s, left + Inches(0.25), top_i + Inches(1.6), card_w - Inches(0.5), Inches(0.8),
                 body, size=10, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Beoordelingskader", (
        "Mijn examen volgt het werkproces B1, K1, K2, W3 en W5. Dat is in totaal dertig criteria "
        "verdeeld over zes categorieën: analyseren, twee keer realiseren (functioneel en testen), "
        "overleg met de product owner, verbetervoorstellen en reflectie. Per categorie heb ik bewijs "
        "in de docs-folder van het GitHub-project. De rest van deze presentatie loopt deze zes pijlers af."
    ))


def slide_06_moscow(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Eisen & wensen — MoSCoW", kicker="B1 · Analyseren")
    add_rounded(s, Inches(0.5), Inches(1.85), Inches(3.0), Inches(4.4), PRIMARY)
    add_text(s, Inches(0.5), Inches(2.0), Inches(3.0), Inches(0.5),
             "MUST-HAVES", size=12, bold=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(2.6), Inches(3.0), Inches(2.0),
             "16", size=140, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(5.0), Inches(3.0), Inches(0.5),
             "E01 t/m E16", size=16, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(5.5), Inches(3.0), Inches(0.6),
             "Alle MUST haves zijn gerealiseerd.",
             size=12, italic=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_multi_text(s, Inches(3.85), Inches(1.85), Inches(9.0), Inches(5.0), [
        ("MUST  ·  16 eisen", {"size": 16, "bold": True, "color": PRIMARY, "space_after": 4}),
        ("Login, rollen, teambeheer, cliëntdossiers, uren-flow, wachtwoord-reset, profielbeheer.",
         {"size": 12, "color": TEXT, "space_after": 16}),
        ("SHOULD  ·  4 wensen", {"size": 16, "bold": True, "color": ACCENT, "space_after": 4}),
        ("E-mailnotificaties (S01) · audit-log dossier (S02) · CSV-export uren (S03) · dashboard-widgets (S04).",
         {"size": 12, "color": TEXT, "space_after": 16}),
        ("COULD  ·  4 wensen", {"size": 16, "bold": True, "color": SECONDARY, "space_after": 4}),
        ("Bulk-goedkeuring · type-ahead filter · donker/licht thema · meertaligheid.",
         {"size": 12, "color": TEXT, "space_after": 16}),
        ("WON'T  ·  4 buiten scope", {"size": 16, "bold": True, "color": TEXT_MUTED, "space_after": 4}),
        ("Native mobile app · salaris-koppelingen · SUWInet/ZorgMail · cliënt-portaal.",
         {"size": 12, "color": TEXT_MUTED, "space_after": 8}),
    ])
    add_footer(s, idx)
    add_notes(s, idx, "MoSCoW", (
        "Ik heb alle eisen geprioriteerd via MoSCoW. Zestien Must-haves vormen de scope van het examen — "
        "allemaal afgerond. Should-haves zijn waardevolle uitbreidingen die ik bewust buiten de MVP heb "
        "gehouden, en die staan in mijn verbetervoorstellen. Could's zijn nice-to-have. Won't-haves "
        "zijn nadrukkelijk geen scope."
    ))


def slide_07_techstack(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Technische uitgangspunten", kicker="B1 · Analyseren")
    rows = [
        ("Backend", "PHP 8.4 + Laravel 12",
         "Modern PHP met strikte types · MVC-framework met ingebakken auth, validatie en CSRF."),
        ("Frontend", "Blade + Tailwind CSS v4",
         "Server-rendered — geen SPA-complexiteit · utility-first styling, consistent design."),
        ("Database", "SQLite (dev / examen)",
         "Zero-config · productie-migratie naar PostgreSQL is triviaal."),
        ("ORM", "Eloquent",
         "Mass-assignment via $fillable · relations · casts · query-builder."),
        ("Testing", "Pest v4 + pest-plugin-laravel",
         "Leesbaarder dan PHPUnit · RefreshDatabase trait · feature- en browser-tests."),
        ("Autorisatie", "Policies + Middleware",
         "Defense in depth — policy per resource + middleware per rol-groep."),
        ("Code-style", "Laravel Pint (PSR-12)",
         "Afgedwongen per commit · `vendor/bin/pint --dirty` als gate."),
        ("Versiebeheer", "Git + GitHub",
         "Feature-branches · 18 PRs · 4 sprint-tags · geen --force-push, geen --amend."),
        ("Lokaal hosten", "Laravel Herd",
         "nexora.test · zero-config PHP + Node + DB."),
    ]
    top = Inches(1.85); card_w = Inches(4.1); card_h = Inches(1.65)
    gap_x = Inches(0.15); gap_y = Inches(0.15)
    for i, (label, tech, body) in enumerate(rows):
        row, col = divmod(i, 3)
        left = Inches(0.5) + (card_w + gap_x) * col
        top_i = top + (card_h + gap_y) * row
        add_rounded(s, left, top_i, card_w, card_h, BG_PANEL)
        add_text(s, left + Inches(0.25), top_i + Inches(0.18), card_w - Inches(0.5), Inches(0.3),
                 label.upper(), size=10, bold=True, color=PRIMARY, font=FONT_HEAD)
        add_text(s, left + Inches(0.25), top_i + Inches(0.48), card_w - Inches(0.5), Inches(0.4),
                 tech, size=15, bold=True, color=TEXT, font=FONT_HEAD)
        add_text(s, left + Inches(0.25), top_i + Inches(0.93), card_w - Inches(0.5), Inches(0.7),
                 body, size=10, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Techstack", (
        "De techniek is bewust gekozen op vier criteria: bewezen ecosysteem, security-by-default, "
        "leesbare tests en simpel deploybaar. Laravel 12 met PHP 8.4 geeft CSRF-tokens, mass-assignment-"
        "bescherming en Policies out of the box. Blade en Tailwind houden de frontend server-rendered. "
        "Voor autorisatie pas ik defense in depth toe: Policies op resource-niveau én middleware op rol-niveau."
    ))


def slide_08_user_stories(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "16 user stories  ·  4 sprints", kicker="B1 · Analyseren")
    stats = [
        ("16", "User stories", PRIMARY),
        ("4", "Sprints", ACCENT),
        ("18", "Pull requests", SECONDARY),
        ("257", "Commits", PRIMARY),
    ]
    sx = Inches(0.5); sw = Inches(3.05); sh = Inches(0.9)
    for i, (n, lbl, c) in enumerate(stats):
        left = sx + (sw + Inches(0.1)) * i
        add_rounded(s, left, Inches(1.85), sw, sh, c)
        add_text(s, left, Inches(1.9), sw, Inches(0.55),
                 n, size=30, bold=True, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left, Inches(2.35), sw, Inches(0.4),
                 lbl, size=11, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_BODY)
    table_top = Inches(3.0); col_w = Inches(6.2)
    rows_left = [
        ("Sprint 1 — Auth + team basis", PRIMARY),
        ("US-01  Inloggen op Nexora", None),
        ("US-02  Rolgebaseerde toegang", None),
        ("US-03  Nieuwe zorgbegeleider aanmaken", None),
        ("US-04  Medewerkersoverzicht (zoek + filter)", None),
        ("Sprint 2 — Team compleet + cliënt basis", PRIMARY),
        ("US-05  Teamlid bewerken (rol + dienstverband)", None),
        ("US-06  Teamlid deactiveren & heractiveren", None),
        ("US-07  Cliënt aanmaken met persoonsgegevens", None),
        ("US-08  Cliënten koppelen aan begeleiders", None),
    ]
    rows_right = [
        ("Sprint 3 — Cliënt compleet + uren basis", PRIMARY),
        ("US-09  Cliëntenoverzicht rol-gebaseerd", None),
        ("US-10  Cliënt bewerken & archiveren", None),
        ("US-11  Concept-uren aanmaken & bewerken", None),
        ("US-12  Uren indienen & terugtrekken", None),
        ("Sprint 4 — Uren compleet + auth afronding", PRIMARY),
        ("US-13  Uren goedkeuren of afkeuren", None),
        ("US-14  Urenoverzicht met filters", None),
        ("US-15  Wachtwoord vergeten & reset", None),
        ("US-16  Profielbeheer", None),
    ]
    row_h = Inches(0.34)
    for i, (txt, color) in enumerate(rows_left):
        y = table_top + row_h * i
        if color is not None:
            add_rect(s, Inches(0.5), y, col_w, row_h, color)
            add_text(s, Inches(0.65), y + Inches(0.04), col_w - Inches(0.3), Inches(0.3),
                     txt, size=11, bold=True, color=WHITE, font=FONT_HEAD)
        else:
            add_text(s, Inches(0.65), y + Inches(0.04), col_w - Inches(0.3), Inches(0.3),
                     txt, size=12, color=TEXT, font=FONT_BODY)
            add_text(s, Inches(6.4), y + Inches(0.04), Inches(0.3), Inches(0.3),
                     "✓", size=14, bold=True, color=SUCCESS, font=FONT_HEAD, align=PP_ALIGN.RIGHT)
    for i, (txt, color) in enumerate(rows_right):
        y = table_top + row_h * i
        if color is not None:
            add_rect(s, Inches(6.85), y, col_w, row_h, color)
            add_text(s, Inches(7.0), y + Inches(0.04), col_w - Inches(0.3), Inches(0.3),
                     txt, size=11, bold=True, color=WHITE, font=FONT_HEAD)
        else:
            add_text(s, Inches(7.0), y + Inches(0.04), col_w - Inches(0.3), Inches(0.3),
                     txt, size=12, color=TEXT, font=FONT_BODY)
            add_text(s, Inches(12.75), y + Inches(0.04), Inches(0.3), Inches(0.3),
                     "✓", size=14, bold=True, color=SUCCESS, font=FONT_HEAD, align=PP_ALIGN.RIGHT)
    add_footer(s, idx)
    add_notes(s, idx, "User stories", (
        "Zestien user stories in vier sprints van vier. Sprint 1 is auth en teambeheer, sprint 2 maakt "
        "teambeheer compleet en start cliëntdossiers, sprint 3 maakt cliëntdossiers compleet en start "
        "urenregistratie, sprint 4 sluit de uren-flow af samen met wachtwoord-reset en profielbeheer. "
        "Alle zestien afgevinkt. Achttien pull requests, tweehonderd zevenenvijftig commits. In de drie "
        "volgende slides loop ik per blok langs met screenshots."
    ))


def slide_09_architecture(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Architectuur — ERD en workflow", kicker="B1 · Analyseren")
    erd = DOCS / "erd-files" / "erd.png"
    fc = DOCS / "flowchart-files" / "urenregistratie-workflow.png"
    add_rect(s, Inches(0.5), Inches(1.85), Inches(6.2), Inches(0.4), PRIMARY)
    add_text(s, Inches(0.65), Inches(1.9), Inches(6.0), Inches(0.3),
             "DATAMODEL  ·  ERD", size=12, bold=True, color=WHITE, font=FONT_HEAD)
    if erd.exists():
        s.shapes.add_picture(str(erd), Inches(0.5), Inches(2.3),
                             width=Inches(6.2), height=Inches(4.3))
    add_text(s, Inches(0.5), Inches(6.65), Inches(6.2), Inches(0.3),
             "5 hoofdtabellen: users · clients · client_caregivers · urenregistratie · audit_logs",
             size=10, italic=True, color=TEXT_MUTED, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_rect(s, Inches(6.95), Inches(1.85), Inches(6.0), Inches(0.4), ACCENT)
    add_text(s, Inches(7.1), Inches(1.9), Inches(5.8), Inches(0.3),
             "PROCES  ·  URENREGISTRATIE-FLOW", size=12, bold=True, color=WHITE, font=FONT_HEAD)
    if fc.exists():
        s.shapes.add_picture(str(fc), Inches(6.95), Inches(2.3),
                             width=Inches(6.0), height=Inches(4.3))
    add_text(s, Inches(6.95), Inches(6.65), Inches(6.0), Inches(0.3),
             "Concept → Ingediend → Goedgekeurd of Afgekeurd (US-11 t/m US-13)",
             size=10, italic=True, color=TEXT_MUTED, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Architectuur", (
        "Het datamodel links toont vijf hoofdtabellen: users, clients, client_caregivers als koppeltabel, "
        "urenregistratie en audit_logs. Op de koppeltabel zit een partial unique index op DB-niveau — "
        "maximaal één primair en één secundair per cliënt. Rechts staat de uren-workflow: Concept → "
        "Ingediend → Goedgekeurd of Afgekeurd. Alle transities centraal in een service met allowed-matrix."
    ))


def slide_10_wireframes(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Wireframes & sprint-aanpak", kicker="B1 · Analyseren")
    # Left: 2 wireframes
    wf1 = DOCS / "wireframes" / "desktop" / "01-login-us-01.png"
    wf2 = DOCS / "wireframes" / "desktop" / "04-dashboard-zorgbegeleider-us-04.png"
    if wf1.exists():
        add_rect(s, Inches(0.5), Inches(1.85), Inches(4.0), Inches(0.35), PRIMARY)
        add_text(s, Inches(0.65), Inches(1.9), Inches(3.8), Inches(0.25),
                 "Wireframe — login (US-01)", size=11, bold=True, color=WHITE, font=FONT_HEAD)
        s.shapes.add_picture(str(wf1), Inches(0.5), Inches(2.25),
                             width=Inches(4.0), height=Inches(2.4))
    if wf2.exists():
        add_rect(s, Inches(0.5), Inches(4.75), Inches(4.0), Inches(0.35), PRIMARY)
        add_text(s, Inches(0.65), Inches(4.8), Inches(3.8), Inches(0.25),
                 "Wireframe — dashboard zorgbegeleider",
                 size=11, bold=True, color=WHITE, font=FONT_HEAD)
        s.shapes.add_picture(str(wf2), Inches(0.5), Inches(5.15),
                             width=Inches(4.0), height=Inches(1.85))
    # Right: sprint workflow steps + ontwerpdoc
    add_text(s, Inches(4.85), Inches(1.85), Inches(8.0), Inches(0.4),
             "SPRINT-WORKFLOW (per user story)", size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
    steps = [
        ("01", "Sprint-planning — 4 US uit backlog naar sprint"),
        ("02", "Feature-branch — `feature/us0X-naam`"),
        ("03", "Code + Pest — TDD + handmatig testen"),
        ("04", "Pull request — self-review, merge naar `main`"),
        ("05", "Sprint-tag — annotated tag `sprint-N`"),
    ]
    step_top = Inches(2.3)
    for i, (n, h) in enumerate(steps):
        y = step_top + Inches(0.55) * i
        add_rounded(s, Inches(4.85), y, Inches(0.55), Inches(0.45), PRIMARY)
        add_text(s, Inches(4.85), y + Inches(0.05), Inches(0.55), Inches(0.4),
                 n, size=14, bold=True, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, Inches(5.55), y + Inches(0.08), Inches(7.3), Inches(0.4),
                 h, size=12, color=TEXT, font=FONT_BODY)
    # Ontwerpdocument bottom
    add_text(s, Inches(4.85), Inches(5.4), Inches(8.0), Inches(0.3),
             "ONTWERPDOCUMENT (3 deeldocumenten)",
             size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
    sub = [
        ("Verantwoorde verwerking", "Ethiek · rolscheiding · geen surveillance"),
        ("Gegevensbescherming", "AVG · dataminimalisatie · Wgbo"),
        ("Beveiliging", "HTTPS · bcrypt · CSRF · NEN 7510"),
    ]
    sub_w = Inches(2.6)
    for i, (h, b) in enumerate(sub):
        left = Inches(4.85) + (sub_w + Inches(0.07)) * i
        add_rounded(s, left, Inches(5.8), sub_w, Inches(1.1), BG_PANEL)
        add_text(s, left + Inches(0.15), Inches(5.9), sub_w - Inches(0.3), Inches(0.3),
                 h, size=11, bold=True, color=PRIMARY, font=FONT_HEAD)
        add_text(s, left + Inches(0.15), Inches(6.2), sub_w - Inches(0.3), Inches(0.7),
                 b, size=9, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, "Wireframes en sprint-aanpak", (
        "Voordat ik begon te bouwen heb ik per pagina een wireframe gemaakt voor desktop en mobiel — "
        "links twee voorbeelden. Rechts staat mijn sprint-workflow in vijf stappen: planning, feature-"
        "branch, code en testen, pull request en sprint-tag. Ik gebruik nooit force-push of amend op "
        "gepushte commits — de Git-historie is leesbare bewijslast. Onderaan rechts: drie ontwerp-"
        "deeldocumenten voor ethiek, AVG en beveiliging."
    ))


# ---------- 3 US-group slides ----------

def us_group_slide(prs, idx, *, block_no, title, kicker_sub,
                   us_rows, key_points, screenshots, notes_text):
    """Layout: left info panel (US-list + key points) + right 2 stacked screenshots."""
    s = add_blank_slide(prs)
    add_title_bar(s, title, kicker=f"Blok {block_no} · {kicker_sub}")
    # Left panel
    panel_left = Inches(0.5); panel_top = Inches(1.65)
    panel_w = Inches(6.0); panel_h = Inches(5.35)
    add_rounded(s, panel_left, panel_top, panel_w, panel_h, BG_PANEL)
    # US list header
    add_text(s, panel_left + Inches(0.25), panel_top + Inches(0.2),
             panel_w - Inches(0.5), Inches(0.3),
             "USER STORIES IN DIT BLOK",
             size=10, bold=True, color=PRIMARY, font=FONT_HEAD)
    # US rows table
    row_h = Inches(0.31)
    for i, (us_no, us_title, tests) in enumerate(us_rows):
        y = panel_top + Inches(0.55) + row_h * i
        add_text(s, panel_left + Inches(0.25), y, Inches(0.85), Inches(0.3),
                 us_no, size=11, bold=True, color=ACCENT, font=FONT_HEAD)
        add_text(s, panel_left + Inches(1.1), y, Inches(3.65), Inches(0.3),
                 us_title, size=11, color=TEXT, font=FONT_BODY)
        add_text(s, panel_left + Inches(4.8), y, Inches(1.0), Inches(0.3),
                 tests, size=10, italic=True, color=TEXT_MUTED,
                 align=PP_ALIGN.RIGHT, font=FONT_BODY)
    # Separator
    sep_y = panel_top + Inches(0.55) + row_h * len(us_rows) + Inches(0.15)
    add_rect(s, panel_left + Inches(0.25), sep_y,
             panel_w - Inches(0.5), Inches(0.03), PRIMARY)
    # Key points header
    kp_y = sep_y + Inches(0.18)
    add_text(s, panel_left + Inches(0.25), kp_y,
             panel_w - Inches(0.5), Inches(0.3),
             "KEY TECHNICAL POINTS",
             size=10, bold=True, color=PRIMARY, font=FONT_HEAD)
    kp_paras = [(f"•  {kp}",
                 {"size": 11, "color": TEXT, "space_after": 4})
                for kp in key_points]
    add_multi_text(s, panel_left + Inches(0.25), kp_y + Inches(0.35),
                   panel_w - Inches(0.5), Inches(2.5), kp_paras)

    # Right: 2 stacked screenshots
    img_left = Inches(6.7); img_w = Inches(6.15)
    img_h_top = Inches(2.55); img_h_bot = Inches(2.55)
    gap = Inches(0.25)
    top1 = panel_top
    top2 = top1 + img_h_top + gap
    for i, (shot_path, caption) in enumerate(screenshots[:2]):
        shot = DOCS / shot_path
        cur_top = top1 if i == 0 else top2
        h = img_h_top if i == 0 else img_h_bot
        add_rect(s, img_left, cur_top, img_w, h, SECONDARY)
        if shot.exists():
            s.shapes.add_picture(str(shot),
                                 img_left + Inches(0.05),
                                 cur_top + Inches(0.05),
                                 width=img_w - Inches(0.1),
                                 height=h - Inches(0.1))
        # Caption strip on top of image (inside, narrow band)
        add_text(s, img_left + Inches(0.15), cur_top + Inches(0.1),
                 img_w - Inches(0.3), Inches(0.25),
                 caption, size=9, italic=True, color=TEXT_MUTED, font=FONT_BODY)
    add_footer(s, idx)
    add_notes(s, idx, title, notes_text)


def slide_11_group_auth_team(prs, idx):
    us_group_slide(
        prs, idx,
        block_no=1,
        title="Authenticatie + Teambeheer",
        kicker_sub="Sprint 1 + start sprint 2  ·  6 user stories",
        us_rows=[
            ("US-01", "Inloggen op Nexora", "10 tests"),
            ("US-02", "Rolgebaseerde toegang (Policies + middleware)", "26 tests"),
            ("US-03", "Nieuwe zorgbegeleider aanmaken", "15 tests"),
            ("US-04", "Medewerkersoverzicht (zoek + filter)", "19 tests"),
            ("US-05", "Teamlid bewerken (rol + dienstverband)", "16 tests"),
            ("US-06", "Teamlid deactiveren & heractiveren", "19 tests"),
        ],
        key_points=[
            "Defense in depth — Policies per resource + middleware per rol-groep",
            "Login: rate-limit (5/60s) + user-enumeration-protection + session-regeneratie",
            "Audit-logs via UserService::updateWithAudit (AVG art. 30) vanaf US-05",
            "Self-demotion guard — teamleider kan zichzelf niet ontmachtigen",
            "Runtime sessie-invalidatie bij deactiveren (CheckActiveUser middleware)",
            "105 Pest-tests · 295 asserts · alle 6 US's PASS",
        ],
        screenshots=[
            ("uitgewerkte-functionaliteiten/us01-inloggen/01-inloggen.png",
             "US-01 · Login-scherm"),
            ("uitgewerkte-functionaliteiten/us04-medewerkers-overzicht/01-medewerkers-overzicht.png",
             "US-04 · Medewerkersoverzicht met zoek + filter"),
        ],
        notes_text=(
            "Blok 1: authenticatie en teambeheer — zes user stories. US-01 is inloggen met rol-gebaseerde "
            "redirect. US-02 is mijn security-kern: defense in depth — Policies per resource én middleware "
            "per rol-groep, een zorgbegeleider die /team opent krijgt 403. US-03 voor aanmaken, US-04 voor "
            "het overzicht met zoek en filter. In sprint 2 ronden US-05 en US-06 het teambeheer af met "
            "bewerken en (de)activeren. Belangrijke security-features: rate-limiting vanaf US-01, "
            "user-enumeration-protection, audit-logs voor AVG-traceerbaarheid, en een self-demotion guard "
            "die voorkomt dat een teamleider zichzelf ontmachtigt. Honderd-vijf Pest-tests dekken dit blok."
        ),
    )


def slide_12_group_clients(prs, idx):
    us_group_slide(
        prs, idx,
        block_no=2,
        title="Cliëntdossiers + Begeleider-koppelingen",
        kicker_sub="Sprint 2 + sprint 3  ·  4 user stories",
        us_rows=[
            ("US-07", "Cliënt aanmaken met persoonsgegevens", "21 tests"),
            ("US-08", "Cliënten koppelen (primair/secundair/tertiair)", "29 tests"),
            ("US-09", "Cliëntenoverzicht rol-gebaseerd + filter", "27 tests"),
            ("US-10", "Cliënt bewerken & archiveren (soft delete)", "31 tests"),
        ],
        key_points=[
            "Drie rol-niveaus per cliënt: primair · secundair · tertiair",
            "Partial unique index op DB-niveau (max 1 primair + 1 secundair per cliënt)",
            "Zorgtypes: WMO · WLZ · Jeugdwet — Form Request validatie",
            "Rol-gefilterd overzicht — zorgbegeleider ziet alleen eigen caseload",
            "Soft-delete via Eloquent SoftDeletes + apart 'archief'-tab",
            "N+1-regressietest via DB::listen op alle lijst-endpoints",
            "108 Pest-tests · 286 asserts — alle 4 US's PASS",
        ],
        screenshots=[
            ("uitgewerkte-functionaliteiten/us07-client-aanmaken/01-client-aanmaken.png",
             "US-07 · Cliënt aanmaken met persoonsgegevens"),
            ("uitgewerkte-functionaliteiten/us09-clienten-overzicht/01-clienten-overzicht.png",
             "US-09 · Cliëntenoverzicht (rol-gefilterd)"),
        ],
        notes_text=(
            "Blok 2: cliëntdossiers — vier user stories die het hart van Nexora vormen. US-07 voor "
            "aanmaken met persoonsgegevens en zorgtype WMO, WLZ of Jeugdwet. US-08 voor het koppelen van "
            "begeleiders in drie rollen: primair, secundair en tertiair — met op database-niveau een "
            "partial unique index die afdwingt dat er maar één primair en één secundair kan zijn. Dat is "
            "een harde DB-constraint, geen application-level validatie. US-09 toont het overzicht — "
            "rol-gefilterd zodat zorgbegeleiders alleen hun eigen caseload zien. US-10 voegt bewerken en "
            "archiveren toe via soft-delete. Op alle lijst-endpoints heb ik een N-plus-één-regressietest "
            "via DB-listen. Honderd-acht Pest-tests dekken dit blok."
        ),
    )


def slide_13_group_uren(prs, idx):
    us_group_slide(
        prs, idx,
        block_no=3,
        title="Urenregistratie + Auth-afronding",
        kicker_sub="Sprint 3 + sprint 4  ·  6 user stories",
        us_rows=[
            ("US-11", "Concept-uren aanmaken & bewerken", "28 tests"),
            ("US-12", "Uren indienen + terugtrekken + resubmit", "31 tests"),
            ("US-13", "Uren goedkeuren of afkeuren (teamleider)", "27 tests"),
            ("US-14", "Urenoverzicht met filters (status/wk/medew.)", "22 tests"),
            ("US-15", "Wachtwoord vergeten & reset (60 min token)", "16 tests"),
            ("US-16", "Profielbeheer + wachtwoord wijzigen", "21 tests"),
        ],
        key_points=[
            "State-machine: Concept → Ingediend → Goedgekeurd / Afgekeurd",
            "UrenregistratieService::transition() met centrale allowed-matrix",
            "Status-tabs via URL — shareable, werkt zonder JavaScript",
            "Afkeuren vereist reden (min. 10 chars), reset bij resubmit",
            "Wachtwoord-reset: signed URL, single-use, 60 min geldig",
            "Mass-assignment-probes op role / is_active / team_id",
            "US-17 (Resend) toegevoegd na PO-feedback op US-15",
            "145 Pest-tests · 372 asserts — alle 6 US's PASS",
        ],
        screenshots=[
            ("uitgewerkte-functionaliteiten/us11-concept-uren-aanmaken/01-uren-aanmaken.png",
             "US-11 · Concept-uren aanmaken"),
            ("uitgewerkte-functionaliteiten/us13-uren-beoordelen/01-uren-beoordelen.png",
             "US-13 · Uren beoordelen (teamleider)"),
        ],
        notes_text=(
            "Blok 3: urenregistratie en auth-afronding — zes user stories. US-11 voor concept-uren "
            "aanmaken, US-12 voor indienen, terugtrekken en resubmit na afkeuring. Alle transities zitten "
            "in één centrale service met een allowed-matrix, zodat US-13 — goedkeuren en afkeuren door de "
            "teamleider — daarop kon voortbouwen zonder de matrix te wijzigen. Open/Closed-principe. "
            "Status-tabs werken via URL, dus shareable en JavaScript-vrij. US-14 voegt een filter-rich "
            "urenoverzicht toe voor de teamleider. US-15 is de wachtwoord-reset met signed URL, geldig "
            "voor 60 minuten en single-use. US-16 is profielbeheer. In sprint 4 heb ik daarnaast mass-"
            "assignment-probes toegevoegd op kritieke velden zoals role en is_active. Honderd-vijfenveertig "
            "Pest-tests dekken dit blok."
        ),
    )


# ---------- Closing slides ----------

def slide_14_tests_git(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Tests + Git-bewijslast", kicker="K1 · Realiseren")
    add_rounded(s, Inches(0.5), Inches(1.85), Inches(7.5), Inches(3.0), PRIMARY_DARK)
    add_text(s, Inches(0.5), Inches(2.0), Inches(7.5), Inches(0.45),
             "PEST-TESTS  ·  ALLEMAAL GROEN", size=14, bold=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(2.55), Inches(3.7), Inches(1.6),
             "360", size=110, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(4.0), Inches(3.7), Inches(0.4),
             "tests", size=14, color=SECONDARY, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_text(s, Inches(4.3), Inches(2.55), Inches(3.7), Inches(1.6),
             "953", size=110, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(4.3), Inches(4.0), Inches(3.7), Inches(0.4),
             "asserts", size=14, color=SECONDARY, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_text(s, Inches(0.5), Inches(4.5), Inches(7.5), Inches(0.3),
             "Sprint 1: 70  ·  Sprint 2: 85  ·  Sprint 3: 117  ·  Sprint 4: 86",
             size=12, color=WHITE, align=PP_ALIGN.CENTER, font=FONT_BODY)
    git_stats = [
        ("18", "Pull requests", "Allemaal gemerged"),
        ("257", "Commits", "Met scope-prefix"),
        ("4", "Sprint-tags", "sprint-1 t/m sprint-4"),
        ("0", "Force-pushes", "Geen --amend op pushed"),
    ]
    top = Inches(1.85); box_w = Inches(2.4); box_h = Inches(1.45)
    gap = Inches(0.12); base = Inches(8.2)
    for i, (n, lbl, sub) in enumerate(git_stats):
        row, col = divmod(i, 2)
        left = base + (box_w + gap) * col
        top_i = top + (box_h + gap) * row
        add_rounded(s, left, top_i, box_w, box_h, BG_PANEL)
        add_text(s, left, top_i + Inches(0.15), box_w, Inches(0.6),
                 n, size=38, bold=True, color=PRIMARY,
                 align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left, top_i + Inches(0.75), box_w, Inches(0.3),
                 lbl, size=12, bold=True, color=TEXT,
                 align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left, top_i + Inches(1.05), box_w, Inches(0.3),
                 sub, size=10, color=TEXT_MUTED,
                 align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_rounded(s, Inches(0.5), Inches(5.0), Inches(12.4), Inches(2.0), BG_PANEL)
    add_text(s, Inches(0.8), Inches(5.15), Inches(11.8), Inches(0.4),
             "TESTAANPAK PER USER STORY", size=12, bold=True, color=PRIMARY, font=FONT_HEAD)
    add_multi_text(s, Inches(0.8), Inches(5.5), Inches(11.8), Inches(1.5), [
        ("•  Pest feature-tests met `RefreshDatabase` — minstens 1 happy-path én 1 unhappy-path per acceptatiecriterium",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Per US een eigen testbestand:  `tests/Feature/US-01.php` t/m `US-16.php`",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Handmatige browser-tests met checklist per rol  ·  testplan-doc per US in  /docs/testplan/US-NN.md",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Verificatie:  `php artisan test --compact`  →  0 failures",
         {"size": 12, "color": TEXT, "space_after": 4}),
    ])
    add_footer(s, idx)
    add_notes(s, idx, "Tests en Git-bewijslast", (
        "Mijn testen zijn het hardste bewijs dat het werkt. Driehonderd zestig Pest-tests, "
        "negenhonderd drieënvijftig asserts, allemaal groen. Per user story minstens één happy-path "
        "én één unhappy-path per acceptatiecriterium. Rechts: achttien pull requests gemerged, "
        "tweehonderd zevenenvijftig commits met scope-prefix, vier annotated sprint-tags en nul "
        "force-pushes. Verifieren met één commando: php artisan test --compact."
    ))


def slide_15_po_overleg(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Samenwerking met PO Badreddine", kicker="K2 · Overleggen")
    add_multi_text(s, Inches(0.5), Inches(1.85), Inches(6.0), Inches(5.0), [
        ("Overlegstructuur", {"size": 14, "bold": True, "color": PRIMARY, "space_after": 8}),
        ("•  Dagelijkse check-ins (Zoom + in persoon)",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Sprint-reviews aan einde van elke sprint (4×)",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Sprint-retrospectives (solo, vastgelegd)",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Ad-hoc PO-feedback via Trello-comments",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Trello-update werkdagelijks (asynchroon)",
         {"size": 12, "color": TEXT, "space_after": 16}),
        ("Vastlegging van afspraken",
         {"size": 14, "bold": True, "color": PRIMARY, "space_after": 8}),
        ("•  Trello-bord — kaarten met AC's + tijdsinschatting",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  Definition of Done — 10 criteria per US",
         {"size": 12, "color": TEXT, "space_after": 4}),
        ("•  PO-comments blijven zichtbaar op de Trello-kaart",
         {"size": 12, "color": TEXT, "space_after": 4}),
    ])
    add_rounded(s, Inches(6.85), Inches(1.85), Inches(6.0), Inches(4.95), BG_PANEL)
    add_text(s, Inches(7.1), Inches(2.05), Inches(5.5), Inches(0.4),
             "GESLOTEN FEEDBACK-LOOP",
             size=11, bold=True, color=ACCENT, font=FONT_HEAD)
    add_text(s, Inches(7.1), Inches(2.4), Inches(5.5), Inches(0.5),
             "Van PO-comment tot werkende feature",
             size=18, bold=True, color=TEXT, font=FONT_HEAD)
    add_multi_text(s, Inches(7.1), Inches(3.05), Inches(5.5), Inches(3.7), [
        ('"Graag een gmail-account opvoeren om te testen of het reset-mailtje aankomt. Kijk naar SendGrid of Resend."',
         {"size": 12, "italic": True, "color": TEXT_MUTED, "space_after": 8}),
        ("— PO-comment Badreddine op US-15",
         {"size": 10, "color": TEXT_MUTED, "space_after": 16}),
        ("1   PO-comment op Trello-kaart US-15",
         {"size": 12, "color": TEXT, "bold": True, "space_after": 4}),
        ("2   US-17 aangemaakt op sprint backlog",
         {"size": 12, "color": TEXT, "bold": True, "space_after": 4}),
        ("3   Resend geïntegreerd · DoD-checklist uitgebreid",
         {"size": 12, "color": TEXT, "bold": True, "space_after": 4}),
        ("4   Test-mail aangekomen in echte Gmail-inbox",
         {"size": 12, "color": TEXT, "bold": True, "space_after": 16}),
        ("Doorlooptijd: 1 werkdag.",
         {"size": 12, "italic": True, "color": PRIMARY, "space_after": 4}),
    ])
    add_footer(s, idx)
    add_notes(s, idx, "Overleg met PO", (
        "Mijn product owner is Badreddine. Dagelijkse check-ins, sprint-reviews aan einde van elke sprint, "
        "en asynchroon via Trello. Het Trello-activiteitenlog toont objectief dat hij alle zestien kaarten "
        "zelf van 'ready for review' naar 'done' heeft verplaatst. Rechts een concreet voorbeeld van een "
        "gesloten feedback-loop: zijn comment op US-15 leidde binnen één werkdag tot US-17 met testmail "
        "aangekomen in een echte Gmail-inbox."
    ))


def slide_16_verbetervoorstellen(prs, idx):
    s = add_blank_slide(prs)
    add_title_bar(s, "Verbetervoorstellen → US-17 doorgevoerd", kicker="W5 · Verbetert")
    add_rounded(s, Inches(0.5), Inches(1.85), Inches(3.5), Inches(4.4), ACCENT)
    add_text(s, Inches(0.5), Inches(2.0), Inches(3.5), Inches(0.4),
             "VERBETERVOORSTELLEN", size=11, bold=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(2.5), Inches(3.5), Inches(1.6),
             "3", size=140, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(4.4), Inches(3.5), Inches(0.5),
             "VV-1 · VV-2 · VV-3", size=18, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(5.0), Inches(3.5), Inches(1.0),
             "Op basis van testresultaten, PO-feedback en retrospective.",
             size=12, italic=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_multi_text(s, Inches(4.3), Inches(1.85), Inches(8.6), Inches(5.0), [
        ("VV-1  ·  Externe-flow-validatie opnemen in DoD",
         {"size": 14, "bold": True, "color": PRIMARY, "space_after": 4}),
        ("Mail-, webhook- en betalingsflows handmatig testen in échte omgeving — niet alleen automated tests.",
         {"size": 11, "color": TEXT_MUTED, "space_after": 14}),
        ("VV-2  ·  Backed-enums vanaf model-start",
         {"size": 14, "bold": True, "color": PRIMARY, "space_after": 4}),
        ("Bij vast vocabularium (status, type, rol) direct een backed enum — geen stringly-typed start.",
         {"size": 11, "color": TEXT_MUTED, "space_after": 14}),
        ("VV-3  ·  Halverwege-demo's bij UI-formulieren",
         {"size": 14, "bold": True, "color": PRIMARY, "space_after": 4}),
        ("Vijf-minuten tussentijdse demo aan PO bij US's met formulieren — voorkomt rework.",
         {"size": 11, "color": TEXT_MUTED, "space_after": 16}),
        ("Doorgevoerd:  US-17  ·  Resend-integratie",
         {"size": 16, "bold": True, "color": SUCCESS, "space_after": 4}),
        ("4 uur · Should-have · `MAIL_MAILER=resend` · seeders aangepast · test-mail aangekomen in Gmail-inbox.",
         {"size": 11, "color": TEXT, "space_after": 4}),
    ])
    add_footer(s, idx)
    add_notes(s, idx, "Verbetervoorstellen", (
        "Drie verbetervoorstellen op basis van testresultaten, PO-feedback en retrospectives. "
        "VV-1: externe-flow-validatie in de DoD — een groene Pest-test bij een mail-flow betekent niet "
        "dat het in productie werkt. VV-2: backed enums vanaf model-start. VV-3: halverwege-demo's bij "
        "UI-formulieren. US-17 is direct doorgevoerd: complete Resend-integratie, vier uur, testmail "
        "aangekomen in echte Gmail-inbox."
    ))


def reflection_columns_slide(prs, idx, title, kicker_suffix, cols_data, notes_text):
    s = add_blank_slide(prs)
    add_title_bar(s, title, kicker=kicker_suffix)
    top = Inches(1.95); col_w = Inches(4.1); col_h = Inches(4.95); gap = Inches(0.15)
    for i, (sym, hdr, c, items) in enumerate(cols_data):
        left = Inches(0.5) + (col_w + gap) * i
        add_rounded(s, left, top, col_w, col_h, BG_PANEL)
        add_rounded(s, left + Inches(0.25), top + Inches(0.3), Inches(0.7), Inches(0.7), c)
        add_text(s, left + Inches(0.25), top + Inches(0.35), Inches(0.7), Inches(0.6),
                 sym, size=28, bold=True, color=WHITE,
                 align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left + Inches(1.05), top + Inches(0.4), col_w - Inches(1.3), Inches(0.5),
                 hdr, size=14, bold=True, color=TEXT, font=FONT_HEAD)
        item_lines = [(f"•  {it}", {"size": 11, "color": TEXT, "space_after": 6}) for it in items]
        add_multi_text(s, left + Inches(0.25), top + Inches(1.15),
                       col_w - Inches(0.5), Inches(3.6), item_lines)
    add_footer(s, idx)
    add_notes(s, idx, title, notes_text)


def slide_17_reflection_proces(prs, idx):
    cols = [
        ("+", "Wat ging goed", SUCCESS, [
            "Pest-first vanaf US-01 — geen post-merge bugfixes",
            "Design-system eerst geport — sprint-1 visueel consistent",
            "Scoped commit-prefixes (feat/test/docs/chore)",
            "State-machine voor uren — sprint 4 bouwt erop voort",
        ]),
        ("−", "Wat kan beter", RED, [
            "Testbestandnamen pas in sprint 2 omgenoemd naar US-XX",
            "Stringly-typed enums in sprint 3 (later refactor)",
            "Mail-flow US-15 niet end-to-end gevalideerd",
            "Docs-batch werkte slecht voor externe afhankelijkheden",
        ]),
        ("→", "Wat ga ik anders doen", PRIMARY, [
            "DoD uitbreiden met 'gevalideerd in echte omgeving'",
            "Backed enums vanaf de start, niet halverwege",
            "Code- + docs- + validatie-tasks op elke Trello-kaart",
            "Route-volgordeproblemen via expliciete tests vangen",
        ]),
    ]
    reflection_columns_slide(prs, idx,
                             "Reflectie — het proces",
                             "W3 · Reflecteert (1 van 3)",
                             cols, (
        "Eerste reflectie-as: het proces. Wat ging goed: Pest-first vanaf US-01, design-system eerst "
        "geport, scoped commit-prefixes, en de uren-state-machine zit zo netjes in een service dat sprint 4 "
        "erop kon voortbouwen. Wat beter kan: testbestand-naming, stringly-typed enums in sprint 3, en de "
        "mail-flow niet end-to-end gevalideerd. Wat ik anders ga doen: DoD uitbreiden met externe-flow-"
        "validatie en backed enums vanaf de start."
    ))


def slide_18_reflection_po(prs, idx):
    cols = [
        ("+", "Wat ging goed", SUCCESS, [
            "PO accepteerde alle 16 kaarten zelf (Trello-log)",
            "PO-feedback op US-15 binnen 1 dag opgevolgd (US-17)",
            "Trello-comments als asynchrone communicatie",
            "Sprint-reviews met live-demo per sprint",
        ]),
        ("−", "Wat kan beter", RED, [
            "Twijfels niet altijd direct op de Trello-kaart gevraagd",
            "Weinig tussentijdse demo's tijdens een sprint",
            "Ideeën voor uitbreidingen niet meteen op backlog",
            "DoD-detectie deels door PO i.p.v. door mij vóór review",
        ]),
        ("→", "Wat ga ik anders doen", PRIMARY, [
            "Elke 'ja-tenzij'-twijfel als Trello-comment vóór bouwen",
            "Bij UI-formulieren: 5-min Zoom-demo halverwege",
            "Backlog-ideeën meteen als kaart op idee-label",
            "Externe-afhankelijkheden-checklist vóór review",
        ]),
    ]
    reflection_columns_slide(prs, idx,
                             "Reflectie — samenwerking met PO",
                             "W3 · Reflecteert (2 van 3)",
                             cols, (
        "Tweede reflectie-as: samenwerking met de PO. Wat ging goed: hij heeft alle zestien kaarten zelf "
        "naar done verplaatst, zijn feedback op US-15 leidde binnen één dag tot US-17. Wat beter kan: bij "
        "twijfel over een AC bouwde ik soms door in plaats van vragen, en ik gaf te weinig tussentijdse "
        "demo's. Wat ik anders ga doen: elke ja-tenzij-twijfel direct op de Trello-kaart, en bij UI-"
        "formulieren een vijf-minuten tussentijdse Zoom-demo."
    ))


def slide_19_reflection_self(prs, idx):
    cols = [
        ("+", "Wat ging goed", SUCCESS, [
            "100% van 16 user stories opgeleverd in 4 sprints",
            "Security-by-default vanaf US-01 (rate-limit, mass-assign)",
            "Self-demotion guard proactief toegevoegd in US-05",
            "Snelle response op PO-feedback (US-17 in 4 uur)",
        ]),
        ("−", "Wat kan beter", RED, [
            "Onderschatte planning sprint 1 (3 vs werkelijke 4 US)",
            "Lange code-sessies → typo-commits (`teamleidder`)",
            "Blind vertrouwen op Pest-test bij externe mail-flow",
            "Tijdsinschatting per US niet consistent op Trello",
        ]),
        ("→", "Wat ga ik anders doen", PRIMARY, [
            "Uren-schatting per US op de Trello-kaart bij start",
            "Pomodoro-pauzes (focus 25 / pauze 5)",
            "Externe SaaS-docs lezen vóór code (free-tier limits)",
            "Per US zowel code- als docs- als validatie-tasks",
        ]),
    ]
    reflection_columns_slide(prs, idx,
                             "Reflectie — eigen prestaties",
                             "W3 · Reflecteert (3 van 3)",
                             cols, (
        "Derde reflectie-as: eigen prestaties. Wat ging goed: alle zestien user stories binnen vier sprints, "
        "security vanaf US-01, en op PO-feedback heb ik binnen vier uur US-17 opgeleverd. Wat beter kan: "
        "onderschatte planning, te lange code-sessies, en blind vertrouwen op een groene Pest-test bij de "
        "mail-flow. Wat ik anders ga doen: uren-schatting per US, Pomodoro-pauzes, en eerst provider-docs "
        "lezen voor externe SaaS."
    ))


def slide_20_conclusion(prs, idx):
    s = add_blank_slide(prs, PRIMARY_DARK)
    add_rect(s, 0, Inches(1.05), SLIDE_W, Inches(0.06), SECONDARY)
    add_text(s, Inches(0.75), Inches(0.5), Inches(11.5), Inches(0.4),
             "CONCLUSIE  ·  PROJECT COMPLEET",
             size=14, bold=True, color=SECONDARY, font=FONT_HEAD)
    stats = [
        ("16/16", "User stories opgeleverd"),
        ("360", "Pest-tests groen"),
        ("953", "Asserts groen"),
        ("18", "Pull requests gemerged"),
        ("4", "Sprints + git-tags"),
    ]
    top = Inches(1.7); stat_w = Inches(2.45); stat_h = Inches(2.3); gap = Inches(0.13)
    total_w = stat_w * 5 + gap * 4
    start_x = Inches((13.333 - total_w / 914400) / 2)
    for i, (n, lbl) in enumerate(stats):
        left = start_x + (stat_w + gap) * i
        add_rounded(s, left, top, stat_w, stat_h, RGBColor(0x02, 0x6A, 0x77))
        add_text(s, left, top + Inches(0.35), stat_w, Inches(1.2),
                 n, size=44, bold=True, color=WHITE,
                 align=PP_ALIGN.CENTER, font=FONT_HEAD)
        add_text(s, left, top + Inches(1.5), stat_w, Inches(0.7),
                 lbl, size=12, color=SECONDARY,
                 align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_text(s, Inches(0.75), Inches(4.4), Inches(11.5), Inches(0.6),
             "Alle 6 examen-categorieën gedekt  ·  B1 · K1 · K2 · W3 · W5",
             size=20, bold=True, color=WHITE,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.75), Inches(5.0), Inches(11.5), Inches(0.5),
             "Documentatie volledig  ·  Tests groen  ·  PO heeft alle US's geaccepteerd",
             size=14, italic=True, color=SECONDARY,
             align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_rounded(s, Inches(3.5), Inches(5.85), Inches(6.3), Inches(1.0), SECONDARY)
    add_text(s, Inches(3.5), Inches(6.0), Inches(6.3), Inches(0.7),
             "Vragen?", size=32, bold=True, color=PRIMARY_DARK,
             align=PP_ALIGN.CENTER, font=FONT_HEAD)
    add_text(s, Inches(0.5), Inches(7.05), Inches(12.4), Inches(0.35),
             "github.com/abii2024/nexora", size=12,
             color=SECONDARY, align=PP_ALIGN.CENTER, font=FONT_BODY)
    add_notes(s, idx, "Conclusie", (
        "Tot zover. In samenvatting: zestien van zestien user stories opgeleverd, driehonderd zestig "
        "Pest-tests met negenhonderd drieënvijftig asserts allemaal groen, achttien pull requests gemerged "
        "en vier sprints afgesloten met annotated git-tags. Alle zes examen-categorieën zijn afgedekt met "
        "bewijs in de docs-folder. De PO heeft alle user stories zelf geaccepteerd en zijn feedback op "
        "US-15 leidde tot een directe doorvoering via US-17. Ik heb nog tijd voor vragen. Dank voor jullie aandacht."
    ))


# ---------- Export + main ----------

def export_spreekteksten():
    md_lines = [
        "# Nexora — Spreekteksten per slide",
        "",
        "> Printbare backup van de spreekteksten uit `nexora-examen-presentatie.pptx`.",
        "> Doel: voorbereiden + tijdens presentatie kunnen meelezen.",
        "",
    ]
    for idx, title, notes in SPEAKER_NOTES:
        md_lines.append(f"## Slide {idx} — {title}")
        md_lines.append("")
        md_lines.append(notes)
        md_lines.append("")
    OUT_NOTES.write_text("\n".join(md_lines), encoding="utf-8")


def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    prs = Presentation()
    prs.slide_width = SLIDE_W
    prs.slide_height = SLIDE_H

    idx_iter = iter(range(1, TOTAL_SLIDES + 1))
    n = lambda: next(idx_iter)  # noqa: E731

    # Opening (5)
    slide_01_title(prs, n())
    slide_02_agenda(prs, n())
    slide_03_what_is_nexora(prs, n())
    slide_04_audience_roles(prs, n())
    slide_05_assessment(prs, n())

    # B1 Analyseren (5)
    slide_06_moscow(prs, n())
    slide_07_techstack(prs, n())
    slide_08_user_stories(prs, n())
    slide_09_architecture(prs, n())
    slide_10_wireframes(prs, n())

    # K1 — 3 US-groep slides
    slide_11_group_auth_team(prs, n())
    slide_12_group_clients(prs, n())
    slide_13_group_uren(prs, n())

    # Bewijslast + overleg + verbetervoorstellen
    slide_14_tests_git(prs, n())
    slide_15_po_overleg(prs, n())
    slide_16_verbetervoorstellen(prs, n())

    # Reflectie (3) + conclusie
    slide_17_reflection_proces(prs, n())
    slide_18_reflection_po(prs, n())
    slide_19_reflection_self(prs, n())
    slide_20_conclusion(prs, n())

    actual = len(prs.slides)
    assert actual == TOTAL_SLIDES, f"Expected {TOTAL_SLIDES} but got {actual}"

    prs.save(str(OUT_PPTX))
    export_spreekteksten()
    print(f"OK  ·  {actual} slides  ·  {OUT_PPTX}")
    print(f"OK  ·  spreekteksten  ·  {OUT_NOTES}")


if __name__ == "__main__":
    main()
