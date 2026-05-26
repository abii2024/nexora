"""Genereer docs/examen-checklist.pdf — leesgids voor de examinator (Canvas-inlevering)."""

from __future__ import annotations

import argparse
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
INPUT = ROOT / "docs" / "examen-checklist.md"
OUTPUT = ROOT / "docs" / "examen-checklist.pdf"

GITHUB_BASE = "https://github.com/abii2024/nexora"

LINK_RE = re.compile(r"\[([^\]]+)\]\(([^)]+)\)")


def rewrite_target(url: str) -> str:
    """Maak van een relatieve docs-link een absolute GitHub-URL."""
    if url.startswith(("http://", "https://", "#", "mailto:")):
        return url
    if url.startswith("./"):
        url = url[2:]
    if url.startswith("../"):
        return f"{GITHUB_BASE}/blob/main/{url[3:]}"
    if url.endswith("/"):
        return f"{GITHUB_BASE}/tree/main/docs/{url.rstrip('/')}/"
    return f"{GITHUB_BASE}/blob/main/docs/{url}"


def rewrite_links(md: str) -> str:
    return LINK_RE.sub(lambda m: f"[{m.group(1)}]({rewrite_target(m.group(2))})", md)


CSS = """\
@page {
  size: A4;
  margin: 2cm;
  @bottom-right {
    content: counter(page) " / " counter(pages);
    font-family: -apple-system, "Segoe UI", Calibri, sans-serif;
    font-size: 9pt;
    color: #6c5b7c;
  }
  @bottom-left {
    content: "Nexora — Examen-documentatie";
    font-family: -apple-system, "Segoe UI", Calibri, sans-serif;
    font-size: 9pt;
    color: #6c5b7c;
  }
}
body {
  font-family: -apple-system, "Segoe UI", Calibri, sans-serif;
  font-size: 10.5pt;
  color: #1a1a1a;
  line-height: 1.45;
}
h1 { color: #4A3358; font-size: 22pt; margin-top: 0.2em; margin-bottom: 0.4em; }
h2 { color: #4A3358; font-size: 15pt; margin-top: 1.4em; border-bottom: 2px solid #4A3358; padding-bottom: 0.2em; page-break-after: avoid; }
h3 { color: #4A3358; font-size: 12pt; page-break-after: avoid; }
a { color: #1f6feb; text-decoration: none; word-wrap: break-word; }
code {
  font-family: ui-monospace, "SFMono-Regular", Menlo, monospace;
  background: #f3eff7;
  padding: 1px 5px;
  border-radius: 3px;
  font-size: 9.5pt;
}
pre {
  background: #f6f3fa;
  padding: 10px;
  border-radius: 6px;
  font-size: 9pt;
  overflow-x: auto;
  white-space: pre-wrap;
  word-wrap: break-word;
}
pre code { background: transparent; padding: 0; }
table {
  border-collapse: collapse;
  width: 100%;
  margin: 0.7em 0;
  font-size: 9.5pt;
  page-break-inside: avoid;
}
th, td { border: 1px solid #d8d2e0; padding: 5px 8px; vertical-align: top; text-align: left; }
th { background: #4A3358; color: white; font-weight: 600; }
tr:nth-child(even) td { background: #f7f4fb; }
blockquote {
  border-left: 4px solid #4A3358;
  background: #f7f4fb;
  padding: 0.6em 1em;
  margin: 0.8em 0;
}
hr { border: 0; border-top: 1px solid #d8d2e0; margin: 1.5em 0; }
#TOC {
  background: #f7f4fb;
  border: 1px solid #d8d2e0;
  border-radius: 6px;
  padding: 0.8em 1.2em;
  margin: 1em 0 1.5em 0;
  font-size: 10pt;
}
#TOC ul { margin: 0.2em 0; padding-left: 1.4em; }
"""


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--dump-rewritten",
        action="store_true",
        help="Print de herschreven MD naar stdout en stop (voor verificatie van link-rewrite).",
    )
    args = parser.parse_args()

    md = INPUT.read_text(encoding="utf-8")
    rewritten = rewrite_links(md)

    if args.dump_rewritten:
        sys.stdout.write(rewritten)
        return 0

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)

    with tempfile.TemporaryDirectory() as tmpdir:
        tmp = Path(tmpdir)
        md_tmp = tmp / "examen-checklist.md"
        css_tmp = tmp / "style.css"
        md_tmp.write_text(rewritten, encoding="utf-8")
        css_tmp.write_text(CSS, encoding="utf-8")

        cmd = [
            "pandoc",
            str(md_tmp),
            "-o", str(OUTPUT),
            "--pdf-engine=weasyprint",
            "--css", str(css_tmp),
            "--standalone",
            "--toc",
            "--toc-depth=2",
            "--metadata", "lang=nl",
            "--metadata", "author=Abdisamad Guled Abdulle (abii2024)",
            "--metadata", "date=2026-05-26",
        ]
        # Weasyprint laadt libgobject/pango/cairo via dlopen — wijs ze aan in /opt/homebrew/lib
        # (Apple Silicon brew-prefix; geen-op op Intel/Linux waar libs op standaardpad staan).
        env = os.environ.copy()
        brew_lib = "/opt/homebrew/lib"
        if Path(brew_lib).is_dir():
            existing = env.get("DYLD_FALLBACK_LIBRARY_PATH", "")
            env["DYLD_FALLBACK_LIBRARY_PATH"] = f"{brew_lib}:{existing}" if existing else brew_lib
        result = subprocess.run(cmd, check=False, env=env)
        if result.returncode != 0:
            print(f"pandoc faalde met exitcode {result.returncode}", file=sys.stderr)
            return result.returncode

    print(f"Wrote {OUTPUT.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
