"""Erzeugt die Installationspakete für WordPress.

Aufruf:  python3 wordpress/package.py [Zielordner]
Ergebnis: fahrni-theme.zip  (Theme, zum Hochladen unter Design → Themes)
          fahrni-inhalte.zip (Inhaltspaket für Werkzeuge → FAHRNI-Import)
Übernimmt vorher die aktuelle Gestaltung aus website/assets/ ins Theme.
"""
import pathlib, shutil, sys, zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent
THEME = ROOT / "wordpress" / "fahrni"
SITE = ROOT / "website"
OUT = pathlib.Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "wordpress" / "dist"
OUT.mkdir(parents=True, exist_ok=True)

for f in ("site.css", "site.js"):
    shutil.copy2(SITE / "assets" / f, THEME / "assets" / f)

with zipfile.ZipFile(OUT / "fahrni-theme.zip", "w", zipfile.ZIP_DEFLATED) as z:
    for p in sorted(THEME.rglob("*")):
        if p.is_file():
            z.write(p, pathlib.Path("fahrni") / p.relative_to(THEME))

with zipfile.ZipFile(OUT / "fahrni-inhalte.zip", "w", zipfile.ZIP_DEFLATED) as z:
    for rel in ("content/content.json", "content/images.json", "content/social.json", "content/geschichte.json"):
        z.write(SITE / rel, pathlib.Path("fahrni-import") / rel)
    for sub in ("assets/fotos", "assets/presse", "assets/video"):
        for p in sorted((SITE / sub).rglob("*")):
            if p.is_file():
                z.write(p, pathlib.Path("fahrni-import") / p.relative_to(SITE))

for f in ("fahrni-theme.zip", "fahrni-inhalte.zip"):
    print(f"{f}: {(OUT / f).stat().st_size / 1e6:.1f} MB")
