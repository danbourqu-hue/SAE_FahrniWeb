"""Zieht den Inhalt aus den gespeicherten fahrni.com-Seiten in content.json."""
import html as H, json, pathlib, re, sys

SITE = pathlib.Path(sys.argv[1]); OUT = pathlib.Path(sys.argv[2])
pages = json.loads((SITE / "pages.json").read_text())
def load(name): return (SITE / f"{name}.html").read_text(encoding="utf-8")
def name_of(url): return pages[url]
def between(h, a, b):
    i = h.find(a)
    if i < 0: return ""
    j = h.find(b, i + len(a))
    return h[i: j if j > 0 else None]
def text(s):
    s = re.sub(r'<script.*?</script>', '', s, flags=re.S)
    return re.sub(r"\s+", " ", H.unescape(re.sub(r"<[^>]+>", " ", s))).strip()

KEEP = {"h2", "h3", "h4", "p", "strong", "b", "em", "br", "ul", "ol", "li", "a", "table", "tr", "td", "th", "blockquote"}
def clean(frag, drop_h1=False):
    frag = re.sub(r"<script.*?</script>|<style.*?</style>|<svg.*?</svg>|<!--.*?-->", "", frag, flags=re.S)
    frag = re.sub(r'<a [^>]*mailto-link-filterd[^>]*>.*?</a>', '<span class="email-todo">[E-Mail-Adresse ergänzen]</span>', frag, flags=re.S)
    def tag(m):
        close, name, attrs = m.group(1), m.group(2).lower(), m.group(3) or ""
        if name == "h1" and not drop_h1: name = "h2"
        if name not in KEEP: return ""
        if close: return f"</{name}>"
        if name == "a":
            href = re.search(r'href="([^"]*)"', attrs)
            href = href.group(1) if href else "#"
            if href.startswith("/media/"): href = "https://www.fahrni.com" + href
            return f'<a href="{href}">'
        return f"<{name}>"
    frag = re.sub(r"<(/?)([a-zA-Z0-9]+)([^>]*)>", tag, frag)
    frag = frag.replace("&nbsp;", " ")
    frag = re.sub(r"\s+", " ", frag)
    frag = re.sub(r"<p>\s*</p>|<p><br></p>", "", frag)
    frag = re.sub(r"<p>\s*<p>", "<p>", frag); frag = re.sub(r"</p>\s*</p>", "</p>", frag)
    return frag.strip()

def imgsrc(s):
    return [m for m in re.findall(r'<img[^>]+src="(/media/[^"]+)"', s)]

C = {}

# ---- Startseite
h = load("de"); page = between(h, '<div id="page"', "<footer")
C["home"] = {
    "intro": [text(p) for p in re.findall(r"<p>(.*?)</p>", between(page, "<h1", "plugin-cmsnewslatestarticle"), re.S) if text(p)],
    "teasers": [{"href": a, "img": i, "title": text(t), "text": text(x)} for a, i, t, x in re.findall(
        r'<a href="(/de/[^"]+)" class="pages-subpages-teaser[^>]*>.*?<img[^>]+src="([^"]+)".*?<div class="name">(.*?)</div><div class="text">(.*?)</div>', page, re.S)],
}

# ---- Leistungen
h = load("de_leistungen"); page = between(h, '<div id="page"', "<footer")
secs = []
for m in re.finditer(r'<(h1|h2)[^>]*>(.*?)</\1>(.*?)(?=<h1|<h2|$)', page, re.S):
    body = m.group(3)
    secs.append({"title": text(m.group(2)), "html": clean(re.sub(r"<img[^>]*>", "", body)), "img": (imgsrc(body) or [None])[0]})
ids = re.findall(r'href="#([^"]+)" class="anchorLink', page)
for s, i in zip(secs, ids): s["id"] = i
C["leistungen"] = {"lead": text(between(h, 'plugin-text-header-slave', "</div>")), "sections": secs}

# ---- Unternehmen (Zeitstrahl)
h = load("de_unternehmen"); page = between(h, '<div id="page"', "<footer")
tl = re.findall(r">\s*((?:19|20)\d\d)\s*<.*?<p>(.*?)</p>\s*</", page, re.S)
C["unternehmen"] = {"title": text((re.findall(r"<h1[^>]*>(.*?)</h1>", page, re.S) or [""])[0]),
                    "timeline": [{"year": y, "text": [text(x) for x in re.split(r"<br\s*/?>", t) if text(x)]} for y, t in tl],
                    "img": (imgsrc(page) or [None])[0]}

# ---- Prinzipien
h = load("de_unternehmen_prinzipien"); page = between(h, '<div id="page"', "<footer")
C["prinzipien"] = {"paras": [text(p) for p in re.findall(r"<p>(.*?)</p>", page, re.S) if text(p)], "imgs": imgsrc(page)}

# ---- Presse
h = load("de_unternehmen_presse"); page = between(h, '<div id="page"', "<footer")
items = []
for chunk in re.split(r"<br>\s*<br>|</p>\s*<p>|</p>", page):
    links = re.findall(r'href="(/media/[^"]+\.pdf)"', chunk)
    t = text(re.sub(r"<a[^>]*>.*?</a>", "", chunk, flags=re.S)).strip(" ,")
    if links and t:
        items.append({"title": t, "pdf": "https://www.fahrni.com" + links[-1]})
seen = set(); C["presse"] = [i for i in items if not (i["title"] in seen or seen.add(i["title"]))]

# ---- Rechtliches
for key, nm in (("dsgvo", "de_unternehmen_dsgvo"), ("abb", "de_unternehmen_allgemeine_beschaffungsbedingungen")):
    h = load(nm)
    C[key] = {"title": text((re.findall(r"<h1[^>]*>(.*?)</h1>", between(h, "headerWrap", '<div id="page"'), re.S) or [""])[0]),
              "lead": text(between(h, "plugin-text-header-slave", "</div>")),
              "html": clean(between(h, '<div id="page"', "<footer"))}

# ---- Kontakt
h = load("de_kontakt"); page = between(h, '<div id="page"', "<footer")
locs = []
for m in re.finditer(r"<h3[^>]*>(.*?)</h3>.*?<p>(.*?)</p>", page, re.S):
    lines = [text(x) for x in re.split(r"<br\s*/?>", m.group(2)) if text(x)]
    tel = next((l.replace("Tel.", "").strip() for l in lines if l.startswith("Tel")), "")
    addr = [l for l in lines if not l.startswith("Tel") and "spam" not in l and l != "Fahrni Fassadensysteme AG"]
    locs.append({"name": text(m.group(1)), "address": addr, "tel": tel})
C["kontakt"] = {"lead": text(between(h, "plugin-text-header-slave", "</div>")), "locations": locs}

# ---- Jobs
h = load("de_jobs"); page = between(h, '<div id="page"', "<footer")
jobs = []
for href, title in re.findall(r'<a [^>]*href="(/de/jobs/[^"]+)"[^>]*>\s*<div class="textBox">\s*<h3[^>]*>(.*?)</h3>', page, re.S):
    d = load(name_of("https://www.fahrni.com" + href)); dp = between(d, '<div id="page"', "<footer")
    body = dp.split("</h1>", 1)[1] if "</h1>" in dp else dp
    jobs.append({"slug": href.strip("/").split("/")[-1], "title": text(title), "html": clean(body), "imgs": imgsrc(dp)})
C["jobs"] = jobs

# ---- News
h = load("de_news"); page = between(h, '<div id="page"', "<footer")
news = []
for href, title, teaser in re.findall(r'<a [^>]*href="(/de/news/[^"]+)"[^>]*>\s*<div class="textBox">\s*<h3[^>]*>(.*?)</h3>(.*?)</a>', page, re.S):
    d = load(name_of("https://www.fahrni.com" + href)); dp = between(d, '<div id="page"', "<footer")
    ab = re.search(r'<h2[^>]*abstract[^>]*>(.*?)</h2>', dp, re.S)
    body = dp.split("</h2>", 1)[1] if ab else dp.split("</h1>", 1)[-1]
    date = re.search(r"(\d{1,2}\.\s?\d{1,2}\.\s?\d{4})", text(dp))
    news.append({"slug": href.strip("/").split("/")[-1], "title": text(title), "teaser": text(teaser),
                 "abstract": text(ab.group(1)) if ab else "", "html": clean(re.sub(r"<img[^>]*>", "", body)),
                 "imgs": imgsrc(dp), "date": date.group(1) if date else ""})
C["news"] = news

# ---- Referenzen
refs = []
for listname, default_group in (("de_referenzen", None), ("de_referenzen_archive", "Früher")):
    h = load(listname); page = between(h, '<div id="page"', "<footer")
    group = default_group
    for m in re.finditer(r'<h3 class="yearTitle[^"]*">\s*([^<]+?)\s*</h3>|<a href="(/de/referenzen/[^"]+)" class="referencesEntry[^"]*">(.*?)</a>', page, re.S):
        if m.group(1): group = m.group(1); continue
        href, inner = m.group(2), m.group(3)
        url = "https://www.fahrni.com" + href
        if url not in pages: continue
        d = load(pages[url]); dp = between(d, '<div id="page"', "<footer")
        body = between(dp, "yearTitleWrapper", '<div class="images')
        cols = re.findall(r'<div class="w-50 gutter">(.*?)</div>(?=<div class="w-50|$)', body, re.S)
        desc = ""
        m2 = re.search(r'</h2></div><div class="w-50 gutter">(.*?)</div><div class="w-50 gutter"><div class="builderArchitec', body, re.S)
        if m2: desc = m2.group(1)
        else:
            m3 = re.search(r'</h2></div><div class="w-50 gutter">(.*?)</div>', body, re.S)
            desc = m3.group(1) if m3 and "builderArchitec" not in m3.group(1) else ""
        facts = [(text(a), text(b)) for a, b in re.findall(r'builderArchitecTitle">(.*?)</div><div class="builderArchitecTxt">(.*?)</div>', body, re.S)]
        gallery = re.findall(r'<img class="image" src="(/media/[^"]+)"', between(dp, '<div class="images', "</div></div></div>"))
        refs.append({
            "slug": href.strip("/").split("/")[-1],
            "name": text((re.findall(r'referencesEntryName">(.*?)</div>', inner, re.S) or [""])[0]),
            "place": text((re.findall(r'referencesEntryPlace">(.*?)</div>', inner, re.S) or [""])[0]),
            "group": group or "Früher",
            "title": text((re.findall(r'<h2[^>]*>(.*?)</h2>', body, re.S) or [""])[0]),
            "thumb": (imgsrc(inner) or [None])[0],
            "html": clean(desc), "facts": facts, "gallery": gallery,
        })
C["referenzen"] = {"lead": text(between(load("de_referenzen"), "plugin-text-header-slave", "</div>")), "items": refs}

OUT.write_text(json.dumps(C, ensure_ascii=False, indent=1))
print("refs", len(refs), "gallery imgs", sum(len(r["gallery"]) for r in refs), "no-desc", sum(1 for r in refs if not r["html"]),
      "| news", len(news), "| jobs", len(jobs), "| leistungen", len(secs), "| timeline", len(tl), "| presse", len(C["presse"]),
      "| locs", [l["name"] for l in locs], "| teasers", len(C["home"]["teasers"]))
