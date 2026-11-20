"""Erzeugt eine einzelne HTML-Datei mit allen Assets eingebettet (Bilder, Schriften, Videos).

Aufruf: python3 build_standalone.py
Ergebnis: fahrni-website-standalone.html (lässt sich ohne Webserver per Doppelklick öffnen)
"""
import base64
import mimetypes
import pathlib
import re

HERE = pathlib.Path(__file__).parent
MIME = {".otf": "font/otf", ".mp4": "video/mp4", ".png": "image/png", ".jpg": "image/jpeg"}


def data_uri(match: re.Match) -> str:
    path = HERE / match.group(0)
    mime = MIME.get(path.suffix) or mimetypes.guess_type(path.name)[0] or "application/octet-stream"
    return f"data:{mime};base64,{base64.b64encode(path.read_bytes()).decode()}"


html = (HERE / "index.html").read_text(encoding="utf-8")
html = re.sub(r"assets/[\w./-]+\.(?:otf|mp4|png|jpg)", data_uri, html)
out = HERE / "fahrni-website-standalone.html"
out.write_text(html, encoding="utf-8")
print(f"{out.name}: {out.stat().st_size / 1024:.0f} KB")
