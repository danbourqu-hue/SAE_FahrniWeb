"""Lädt alle Bilder aus content.json, verkleinert sie und schreibt images.json (Original-URL -> lokaler Pfad)."""
import hashlib, io, json, pathlib, re, sys, urllib.request
from concurrent.futures import ThreadPoolExecutor
from PIL import Image

C = json.loads(pathlib.Path(sys.argv[1]).read_text()); OUT = pathlib.Path(sys.argv[2]); OUT.mkdir(parents=True, exist_ok=True)
jobs = {}  # url -> (subdir, max width)
def add(u, sub, w):
    if u and u not in jobs: jobs[u] = (sub, w)
def original(u):  # Thumbnail-URL -> Originaldatei
    m = re.match(r"/media/filer_thumbnails/(filer/.+?\.(?:jpe?g|png))__", u, re.I)
    return "/media/" + m.group(1) if m else u

for t in C["home"]["teasers"]: add(original(t["img"]), "seiten", 1400)
for s in C["leistungen"]["sections"]: add(original(s["img"]), "leistungen", 1400)
for i in C["prinzipien"]["imgs"]: add(original(i), "seiten", 1400)
for n in C["news"]:
    for i in n["imgs"]: add(i, "news", 1400)
for r in C["referenzen"]["items"]:
    add(r["thumb"], "referenzen/thumb", 600)
    for g in r["gallery"]: add(g, "referenzen", 1280)

def slug(u):
    base = re.sub(r"__.*$", "", u.rsplit("/", 1)[-1]); base = re.sub(r"\.(png|jpe?g)$", "", base, flags=re.I)
    return re.sub(r"[^a-z0-9]+", "-", base.lower()).strip("-")[:60] + "-" + hashlib.md5(u.encode()).hexdigest()[:6] + ".jpg"

def fetch(u):
    sub, w = jobs[u]; dest = OUT / sub / slug(u)
    if dest.exists(): return u, str(dest.relative_to(OUT.parent))
    dest.parent.mkdir(parents=True, exist_ok=True)
    try:
        req = urllib.request.Request("https://www.fahrni.com" + u, headers={"User-Agent": "Mozilla/5.0 (site-redesign)"})
        data = urllib.request.urlopen(req, timeout=60).read()
        im = Image.open(io.BytesIO(data)); im = im.convert("RGB")
        if im.width > w: im = im.resize((w, round(im.height * w / im.width)), Image.LANCZOS)
        im.save(dest, quality=74, optimize=True, progressive=True)
        return u, str(dest.relative_to(OUT.parent))
    except Exception as e:
        print("FAIL", u, e, flush=True); return u, None

with ThreadPoolExecutor(6) as ex:
    res = dict(ex.map(fetch, list(jobs)))
mapping = {}
for u, p in res.items(): mapping[u] = p
# auch die Thumbnail-Schreibweise auf das Original abbilden
for t in C["home"]["teasers"]: mapping[t["img"]] = mapping.get(original(t["img"]))
for s in C["leistungen"]["sections"]:
    if s["img"]: mapping[s["img"]] = mapping.get(original(s["img"]))
for i in C["prinzipien"]["imgs"]: mapping[i] = mapping.get(original(i))
(OUT.parent / "images.json").write_text(json.dumps(mapping, indent=1))
print("done", sum(1 for v in res.values() if v), "of", len(res))
