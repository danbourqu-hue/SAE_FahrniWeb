"""Lädt die Presse-PDFs von fahrni.com nach assets/presse/ und ergänzt content.json um 'file' und 'year'.

Aufruf: python3 content/presse_pdfs.py
"""
import json, pathlib, re, urllib.request

HERE = pathlib.Path(__file__).resolve().parent.parent
CJ = HERE / "content/content.json"
C = json.loads(CJ.read_text(encoding="utf-8"))
OUT = HERE / "assets/presse"; OUT.mkdir(parents=True, exist_ok=True)

def year_of(item):
    name = item["pdf"].rsplit("/", 1)[-1].lower()
    m = re.search(r"nr-?_?\d{1,2}[-_]?((?:19|20)\d\d)", name) or re.search(r"ausgabe_\d+_((?:19|20)\d\d)", name)
    if m: return int(m.group(1))
    m = re.search(r"nr0?\d[-_]((?:19|20)\d\d)", name)
    if m: return int(m.group(1))
    m = re.search(r"/media/filer/((?:20)\d\d)/", item["pdf"])  # Upload-Jahr als Näherung
    return int(m.group(1)) if m else None

for item in C["presse"]:
    name = re.sub(r"[^a-z0-9._-]+", "-", item["pdf"].rsplit("/", 1)[-1].lower())
    dest = OUT / name
    if not dest.exists():
        req = urllib.request.Request(item["pdf"], headers={"User-Agent": "Mozilla/5.0 (site-redesign)"})
        dest.write_bytes(urllib.request.urlopen(req, timeout=120).read())
    item["file"] = f"presse/{name}"
    item["year"] = year_of(item)
    print(item["year"], f"{dest.stat().st_size/1e6:5.1f} MB", item["title"])

CJ.write_text(json.dumps(C, ensure_ascii=False, indent=1), encoding="utf-8")
