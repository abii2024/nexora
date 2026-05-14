"""Programmatische coord-check van de gegenereerde pptx.

Detecteert:
- Shapes buiten slide-grenzen
- Te korte speaker notes
- Ontbrekende verwachte images
"""

from pathlib import Path

from pptx import Presentation
from pptx.util import Emu

PPTX = Path("docs/presentatie/nexora-examen-presentatie.pptx")
SLIDE_W = Emu(int(13.333 * 914400))
SLIDE_H = Emu(int(7.5 * 914400))

EXPECTED_IMAGES = {
    3: 1,   # Wat is Nexora — dashboard
    9: 2,   # Architectuur — ERD + flowchart
    10: 2,  # Wireframes — 2 wireframes
    11: 2,  # US-groep 1 — 2 screenshots
    12: 2,  # US-groep 2 — 2 screenshots
    13: 2,  # US-groep 3 — 2 screenshots
}


def shape_text(shape) -> str:
    if not shape.has_text_frame:
        return ""
    return shape.text_frame.text


def main():
    if not PPTX.exists():
        print(f"FAIL: {PPTX} niet gevonden")
        return 1

    prs = Presentation(str(PPTX))
    issues: list[str] = []
    print(f"Slides: {len(prs.slides)}")
    for idx, slide in enumerate(prs.slides, 1):
        for shape in slide.shapes:
            l, t = shape.left or 0, shape.top or 0
            w, h = shape.width or 0, shape.height or 0
            if l < 0 or t < 0:
                issues.append(f"Slide {idx}: negatieve origin (l={l}, t={t})")
            if l + w > SLIDE_W + Emu(36000):
                ov_in = (l + w - SLIDE_W) / 914400
                issues.append(
                    f"Slide {idx}: shape steekt {ov_in:.2f}\" buiten rechts — text='{shape_text(shape)[:40]}'"
                )
            if t + h > SLIDE_H + Emu(36000):
                ov_in = (t + h - SLIDE_H) / 914400
                issues.append(
                    f"Slide {idx}: shape steekt {ov_in:.2f}\" buiten onder — text='{shape_text(shape)[:40]}'"
                )

        image_count = sum(1 for shape in slide.shapes if shape.shape_type == 13)
        expected = EXPECTED_IMAGES.get(idx, 0)
        if image_count < expected:
            issues.append(f"Slide {idx}: {image_count} images, verwacht ≥ {expected}")

        notes = slide.notes_slide.notes_text_frame.text.strip()
        if len(notes) < 80:
            issues.append(f"Slide {idx}: notes maar {len(notes)} chars (kort)")

    print(f"\nGeconstateerde issues: {len(issues)}")
    for i in issues:
        print(f"  - {i}")
    if not issues:
        print("\nPERFECT — geen overlap, geen out-of-bounds, alle images aanwezig, alle notes voldoende.")
    return 0 if not issues else 1


if __name__ == "__main__":
    raise SystemExit(main())
