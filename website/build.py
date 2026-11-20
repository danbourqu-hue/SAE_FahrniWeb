"""Erzeugt alle Seiten des FAHRNI-Website-Entwurfs aus content/content.json.

Aufruf:  python3 build.py
Quelle:  content/content.json (Inhalt von fahrni.com), content/images.json (lokale Bilder),
         src/site.css, src/site.js
Ausgabe: *.html, referenzen/, news/, jobs/, assets/site.css, assets/site.js
"""
import base64, html, json, pathlib, re

HERE = pathlib.Path(__file__).parent
C = json.loads((HERE / "content/content.json").read_text(encoding="utf-8"))
IMG = json.loads((HERE / "content/images.json").read_text(encoding="utf-8"))
E = html.escape
PHONE = "+41 32 387 25 25"
TEL = "tel:+41323872525"

def img(url, prefix):
    p = IMG.get(url)
    return f"{prefix}assets/{p}" if p else None

def lead(s):  # Überbleibsel aus dem Parser entfernen
    return re.sub(r'^plugin-text-header-slave">\s*', "", s or "").strip()

# ---------------------------------------------------------------- Linkziele
REF = {r["slug"]: r for r in C["referenzen"]["items"]}
LINKS = {
    "/de/": "index.html", "/de/leistungen/": "leistungen.html", "/de/referenzen/": "referenzen.html",
    "/de/referenzen/archive/": "referenzen.html", "/de/unternehmen/": "unternehmen.html",
    "/de/unternehmen/prinzipien/": "prinzipien.html", "/de/unternehmen/presse/": "presse.html",
    "/de/unternehmen/dsgvo/": "datenschutz.html", "/de/unternehmen/allgemeine-beschaffungsbedingungen/": "beschaffungsbedingungen.html",
    "/de/news/": "news.html", "/de/jobs/": "jobs.html", "/de/kontakt/": "kontakt.html",
}
def local(href, prefix):
    h = href.replace("https://www.fahrni.com", "").split("#")[0]
    if h in LINKS: return prefix + LINKS[h]
    m = re.match(r"/de/(referenzen|news|jobs)/([^/]+)/$", h)
    if m: return f"{prefix}{m.group(1)}/{m.group(2)}.html"
    return href
def fix_html(s, prefix):
    s = re.sub(r'<a href="([^"]*)">\s*</a>', "", s)
    def rep(m):
        href = m.group(1)
        new = local(href, prefix)
        ext = new.startswith("http")
        return f'<a href="{new}"' + (' target="_blank" rel="noopener"' if ext else "") + ">"
    return re.sub(r'<a href="([^"]*)">', rep, s)

# ---------------------------------------------------------------- Bausteine
ICONS = """<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M4 12h15M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-out" viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-back" viewBox="0 0 24 24"><path d="M20 12H5M11 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-pause" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
  <symbol id="i-play" viewBox="0 0 24 24"><path d="M7 5v14l12-7z" fill="currentColor"/></symbol>
</svg>"""
def arrow(kind="arrow"): return f'<span class="arrow" aria-hidden="true"><svg><use href="#i-{kind}"/></svg></span>'
def ic(kind="arrow"): return f'<svg aria-hidden="true"><use href="#i-{kind}"/></svg>'

NAV = [("leistungen", "Leistungen"), ("referenzen", "Referenzen"), ("unternehmen", "Unternehmen"),
       ("news", "News"), ("jobs", "Jobs"), ("kontakt", "Kontakt")]

def header(prefix, active):
    nav = "".join(f'<a href="{prefix}{k}.html"' + (' aria-current="page"' if k == active else "") + f">{v}</a>" for k, v in NAV[:-1])
    menu = "".join(f'<a href="{prefix}{k}.html"><span>0{i+1}</span>{v}</a>' for i, (k, v) in enumerate(NAV))
    sub = f'<a class="sub" href="{prefix}prinzipien.html">Prinzipien</a><a class="sub" href="{prefix}presse.html">Presse</a>'
    menu = menu.replace("Unternehmen</a>", "Unternehmen</a>" + sub, 1)
    return f"""<header class="header" id="header"><div class="bar">
  <a class="brand" href="{prefix}index.html" aria-label="FAHRNI façade systems, zur Startseite"><img src="{prefix}assets/img/logo-dunkel.png" alt="FAHRNI façade systems" width="720" height="282"></a>
  <nav class="nav" aria-label="Hauptnavigation">{nav}</nav>
  <a class="btn btn-primary" href="{prefix}kontakt.html">Kontakt {ic()}</a>
  <button class="burger" id="burger" aria-expanded="false" aria-controls="menu" aria-label="Menü öffnen"><span></span><span></span><span></span></button>
</div></header>
<nav class="menu" id="menu" aria-label="Menü">{menu}</nav>"""

def footer(prefix):
    loc = C["kontakt"]["locations"]
    locs = "".join(f"<li>{E(l['name'])}: {E(l['address'][-1].replace('CH-',''))}</li>" for l in loc)
    return f"""<footer class="footer"><div class="wrap">
  <div class="grid"><div class="tile t-video reveal">
    <div class="media"><video id="footer-video" muted playsinline loop preload="metadata" poster="{prefix}assets/video/footer-16x9.jpg" data-poster-mobile="{prefix}assets/video/footer-9x16.jpg" aria-label="FAHRNI Footer-Animation mit Logo, QR-Code und Standorten">
      <source src="{prefix}assets/video/footer-9x16.mp4" type="video/mp4" media="(max-width: 640px)">
      <source src="{prefix}assets/video/footer-16x9.mp4" type="video/mp4"></video></div>
    <button class="play" id="video-toggle" aria-label="Animation pausieren">{ic('pause')}</button>
  </div></div>
  <div class="foot-grid">
    <div><img src="{prefix}assets/img/logo-dunkel.png" alt="FAHRNI façade systems" width="720" height="282"><p class="claim-text">Praktisch schön und effizient, wir verhelfen Funktionalität und einzigartigem Design zum Leben.</p></div>
    <div><h4>Seiten</h4><ul>{''.join(f'<li><a href="{prefix}{k}.html">{v}</a></li>' for k, v in NAV)}</ul></div>
    <div><h4>Unternehmen</h4><ul><li><a href="{prefix}prinzipien.html">Prinzipien</a></li><li><a href="{prefix}presse.html">Presse</a></li><li><a href="{prefix}datenschutz.html">Datenschutz</a></li><li><a href="{prefix}beschaffungsbedingungen.html">Allgemeine Beschaffungsbedingungen</a></li></ul></div>
    <div><h4>Kontakt</h4><ul><li>Fahrni Fassadensysteme AG</li><li>Bernstrasse 84, CH-3250 Lyss</li><li><a href="{TEL}">T {PHONE}</a></li></ul></div>
  </div>
  <div class="foot-base"><span>© Fahrni Fassadensysteme AG</span><span>Website-Entwurf im neuen Corporate Design · Inhalte von fahrni.com</span></div>
</div></footer>"""

def page(path, title, body, active="", desc=""):
    depth = path.count("/"); prefix = "../" * depth
    doc = f"""<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{E(title)}</title>
<meta name="description" content="{E(desc or 'Fahrni Fassadensysteme AG, Lyss: Aluminium-, Glas- und Elementfassaden.')}">
<meta name="theme-color" content="#000000">
<link rel="icon" href="{prefix}assets/img/favicon.png">
<link rel="stylesheet" href="{prefix}assets/site.css">
</head>
<body>
<div class="ambient" aria-hidden="true"></div>
<div class="cone" aria-hidden="true"><div class="cone__halo"></div><div class="cone__dot"></div></div>
{ICONS}
{header(prefix, active)}
<main class="wrap" id="top">
{body(prefix)}
</main>
{footer(prefix)}
<script src="{prefix}assets/site.js"></script>
</body>
</html>
"""
    out = HERE / path; out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(doc, encoding="utf-8")

def head(prefix, title, lead_text="", crumbs=()):
    cr = ""
    if crumbs:
        parts = [f'<a href="{prefix}index.html">Start</a>'] + [f'<a href="{prefix}{h}">{E(t)}</a>' if h else f"<span>{E(t)}</span>" for t, h in crumbs]
        cr = '<nav class="crumbs fade-in" aria-label="Pfad">' + '<span aria-hidden="true">/</span>'.join(parts) + "</nav>"
    lt = f'<p class="lead fade-in">{E(lead_text)}</p>' if lead_text else ""
    return f'<header class="page-head">{cr}<h1><span class="rise"><span>{E(title)}</span></span></h1>{lt}</header>'

def ref_tile(r, prefix, extra=""):
    src = img(r["thumb"], prefix) or (img(r["gallery"][0], prefix) if r["gallery"] else None)
    pic = f'<div class="media"><img src="{src}" alt="" loading="lazy" width="600" height="600"></div>' if src else ""
    s = E((r["name"] + " " + r["place"]).lower())
    return (f'<a class="tile t-ref reveal" href="{prefix}referenzen/{r["slug"]}.html" data-group="{E(r["group"])}" data-search="{s}"{extra}>'
            f'{pic}<div class="tile-top">{arrow()}</div><div><h3>{E(r["name"])}</h3><p class="place">{E(r["place"])}</p></div></a>')

def news_tile(n, prefix, cls="span-4", d=0):
    src = img(n["imgs"][0], prefix) if n["imgs"] else None
    pic = f'<div class="media"><img src="{src}" alt="" loading="lazy"></div>' if src else ""
    where = n["title"].split(",", 1)[-1].strip() if "," in n["title"] else ""
    name = n["title"].replace("Neuer Auftrag ", "", 1)
    return (f'<a class="tile t-news {cls} reveal" style="--d:{d}s" href="{prefix}news/{n["slug"]}.html">{pic}'
            f'<div class="meta"><b>Neuer Auftrag</b><span>{E(where)}</span></div>'
            f'<div class="tile-top" style="align-items:end"><div><h3>{E(name)}</h3><p>{E(n["teaser"])}</p></div>{arrow()}</div></a>')

def ticker():
    words = ["<b>Aluminiumfassaden</b>", "Glasfassaden", "<b>Elementfassaden</b>", "Engineering", "<b>Testanlage</b>", "Produktion",
             "<b>Montage</b>", "Service &amp; Unterhalt", "<b>Seit 1923</b>", "Lyss · Subingen · Dietikon · Reinach"]
    one = "".join(f"<span>{w}</span>" for w in words)
    return f'<div class="tile ticker reveal" aria-hidden="true"><div class="track">{one}{one}</div></div>'

def sites(prefix):
    out = []
    for i, l in enumerate(C["kontakt"]["locations"]):
        city = l["address"][-1].split(" ", 1)[-1] if l["address"] else ""
        tel = l["tel"]; telh = "tel:" + re.sub(r"[^\d+]", "", tel)
        out.append(f'<article class="tile t-site reveal{" main" if i == 0 else ""}" style="--d:{i*.06}s" data-light>'
                   f'<div class="tile-top"><span class="kind">{E(l["name"])}</span><span class="pin"></span></div>'
                   f'<div><h3>{E(city)}</h3><address>{"<br>".join(E(a) for a in l["address"])}</address>'
                   + (f'<p style="margin-top:10px"><a class="tel" href="{telh}">{E(tel)}</a></p>' if tel else "") + "</div></article>")
    return "".join(out)

# ---------------------------------------------------------------- Startseite
refs = C["referenzen"]["items"]; news = C["news"]; jobs = C["jobs"]; L = C["leistungen"]["sections"]
teaser_img = {t["href"]: t["img"] for t in C["home"]["teasers"]}
intro = C["home"]["intro"]

def home(p):
    hero_img = img(refs[0]["gallery"][0], p) if refs[0]["gallery"] else img(teaser_img["/de/referenzen/"], p)
    svc = "".join(
        f'<a class="tile span-3 reveal" style="--d:{(i%4)*.05}s" href="{p}leistungen.html#{s["id"]}">'
        + (f'<div class="media"><img src="{img(s["img"], p)}" alt="" loading="lazy"></div>' if s["img"] and img(s["img"], p) else "")
        + f'<div class="tile-top"><span class="num">0{i+1}</span>{arrow()}</div><h3>{E(s["title"])}</h3></a>'
        for i, s in enumerate(L))
    refs6 = "".join(ref_tile(r, p, f' style="--d:{(i%3)*.06}s"') for i, r in enumerate(refs[:6]))
    tl = C["unternehmen"]["timeline"]
    job_list = "".join(f'<li><a href="{p}jobs/{j["slug"]}.html">{E(j["title"])}</a> <span>0{i+1}</span></li>' for i, j in enumerate(jobs))
    return f"""
<section aria-labelledby="hero-title" style="padding-top:0">
  <div class="grid">
    <article class="tile t-hero" data-light>
      <div class="media"><img src="{hero_img}" alt="{E(refs[0]['name'])}, {E(refs[0]['place'])}" fetchpriority="high"></div>
      <div class="hero-copy">
        <p class="eyebrow fade-in"><b>Fahrni Fassadensysteme AG</b> · Lyss</p>
        <h1 id="hero-title"><span class="line"><span>Ihre Vision,</span></span><span class="line"><span>unsere Motivation.</span></span></h1>
        <p class="lead">Bauen Sie mit Fahrni! Aluminium-, Glas- und Elementfassaden, innovativ und energieeffizient, in der Schweiz und ausgewählten Ländern Europas.</p>
        <div class="actions"><a class="btn btn-primary" href="{p}referenzen.html">Referenzen ansehen {ic()}</a><a class="btn btn-ghost" href="{p}leistungen.html">Leistungen</a></div>
      </div>
    </article>
    <a class="tile t-blue reveal" href="{p}unternehmen.html">
      <div class="tile-top"><img class="fa" src="{p}assets/img/fa-icon.png" alt="" width="68" height="68">{arrow()}</div>
      <div class="claim"><h2>Vom Einzel&shy;unternehmen zum führenden Fassadenbauer der Schweiz!</h2><span class="link-line">Unternehmen {ic()}</span></div>
    </a>
    <a class="tile t-contact reveal" href="{p}kontakt.html" style="--d:.08s">
      <div class="tile-top"><span class="eyebrow">Die beste Adresse für Ihre Fassade</span>{arrow()}</div>
      <div class="claim"><span class="status"><i></i>Hauptsitz Lyss</span><span class="phone">{PHONE}</span><p>Bernstrasse 84, CH-3250 Lyss</p></div>
    </a>
    {ticker()}
  </div>
</section>

<section aria-labelledby="l-title">
  <div class="section-head reveal"><h2 id="l-title">Leistungen. <span>Alles aus einer Hand.</span></h2><p>{E(lead(C['leistungen']['lead']))}</p></div>
  <div class="grid">{svc}</div>
</section>

<section aria-labelledby="r-title">
  <div class="section-head reveal"><h2 id="r-title">Referenzen. <span>Praktisch schön und effizient.</span></h2><a class="btn btn-ghost" href="{p}referenzen.html">Alle {len(refs)} Referenzen {ic()}</a></div>
  <div class="grid">{refs6}</div>
</section>

<section aria-labelledby="u-title">
  <div class="grid">
    <a class="tile t-about span-7 x-tall reveal" href="{p}unternehmen.html">
      <p class="eyebrow"><b>Unternehmen</b> · seit {tl[0]['year']}</p>
      <h2 id="u-title">Vom Einzel&shy;unternehmen zum <em>führenden</em> Fassadenbauer der Schweiz.</h2>
      <p style="max-width:60ch">{E(intro[1] if len(intro) > 1 else '')}</p>
      <span class="link-line">Geschichte ansehen {ic()}</span>
    </a>
    <a class="tile span-5 x-tall reveal" style="--d:.08s; background:var(--blue)" href="{p}unternehmen.html">
      <div class="tile-top"><span class="eyebrow" style="color:rgba(255,255,255,.7)">{E(tl[0]['text'][0])}</span>{arrow()}</div>
      <div class="big-num">{tl[-1]['year']}<small>{E(tl[-1]['text'][0])}</small></div>
    </a>
  </div>
</section>

<section aria-labelledby="s-title">
  <div class="section-head reveal"><h2 id="s-title">Vier Standorte. <span>Kurze Wege.</span></h2><p>Hauptsitz und Werk 1 in Lyss, Werk 2 in Subingen, Niederlassungen in Zürich und Basel.</p></div>
  <div class="grid">{sites(p)}</div>
</section>

<section aria-labelledby="n-title">
  <div class="section-head reveal"><h2 id="n-title">Neue Aufträge.</h2><a class="btn btn-ghost" href="{p}news.html">Alle News {ic()}</a></div>
  <div class="grid">{''.join(news_tile(n, p, 'span-4', i*.06) for i, n in enumerate(news[:3]))}</div>
</section>

<section aria-labelledby="j-title">
  <div class="grid">
    <a class="tile span-8 x-tall reveal" href="{p}jobs.html" style="justify-content:flex-end">
      <div class="media"><img src="{img(teaser_img['/de/jobs/'], p)}" alt="" loading="lazy"></div>
      <div style="display:grid; gap:14px; max-width:60ch"><span class="caption" style="background:var(--blue)">Jobs</span><h3 id="j-title" style="font-size:clamp(36px,4.4vw,64px)">Bauen Sie mit Fahrni.</h3><p>Offene Stellen in Produktion, Montage, Planung und Logistik.</p><span class="link-line">Offene Stellen {ic()}</span></div>
    </a>
    <div class="tile span-4 reveal" style="--d:.08s; background:var(--tile-2)" data-light>
      <span class="eyebrow"><b>{len(jobs)} offene Stellen</b></span>
      <ul class="steps">{job_list}</ul>
    </div>
  </div>
</section>"""
page("index.html", "FAHRNI Fassadensysteme AG", home, "", "Fahrni Fassadensysteme AG: Ihre Vision, unsere Motivation. Aluminium-, Glas- und Elementfassaden aus Lyss.")

# ---------------------------------------------------------------- Leistungen
def leistungen(p):
    jump = "".join(f'<a class="chip" href="#{s["id"]}">{E(s["title"])}</a>' for s in L)
    rows = []
    for i, s in enumerate(L):
        pic = img(s["img"], p) if s["img"] else None
        rows.append(f'<div class="service" id="{s["id"]}">'
                    f'<article class="tile s-text reveal" data-light><span class="num">0{i+1} / 0{len(L)}</span><h2>{E(s["title"])}</h2><div class="prose" style="grid-column:auto">{fix_html(s["html"], p)}</div></article>'
                    + (f'<div class="tile s-img reveal" style="--d:.08s" data-light><div class="media"><img src="{pic}" alt="{E(s["title"])}" loading="lazy"></div><span class="caption">{E(s["title"])}</span></div>' if pic else "")
                    + "</div>")
    return head(p, "Leistungen", lead(C["leistungen"]["lead"]), [("Leistungen", None)]) + f'<nav class="jump" aria-label="Leistungen">{jump}</nav>' + "".join(rows)
page("leistungen.html", "Leistungen · FAHRNI", leistungen, "leistungen", lead(C["leistungen"]["lead"]))

# ---------------------------------------------------------------- Referenzen
def referenzen(p):
    groups = []
    for r in refs:
        if r["group"] not in groups: groups.append(r["group"])
    chips = f'<button class="chip" data-group="alle" aria-pressed="true">Alle <small>{len(refs)}</small></button>' + "".join(
        f'<button class="chip" data-group="{E(g)}" aria-pressed="false">{E(g)} <small>{sum(1 for r in refs if r["group"] == g)}</small></button>' for g in groups)
    tiles = "".join(ref_tile(r, p) for r in refs)
    return (head(p, "Referenzen", lead(C["referenzen"]["lead"]), [("Referenzen", None)])
            + f'<div class="filters" role="group" aria-label="Referenzen filtern">{chips}<input id="ref-search" class="search" type="search" placeholder="Projekt oder Ort suchen" aria-label="Projekt oder Ort suchen"></div>'
            + f'<div class="grid" id="ref-grid">{tiles}</div><p class="empty" id="ref-empty" hidden>Keine Referenz gefunden.</p>')
page("referenzen.html", "Referenzen · FAHRNI", referenzen, "referenzen", lead(C["referenzen"]["lead"]))

def lightbox():
    return ('<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Bildansicht">'
            '<img alt=""><button class="lb-close" aria-label="Schliessen">×</button><button class="lb-prev" aria-label="Vorheriges Bild">‹</button>'
            '<button class="lb-next" aria-label="Nächstes Bild">›</button><div class="lb-count"></div></div>')

for i, r in enumerate(refs):
    prev_r, next_r = refs[i - 1] if i > 0 else None, refs[i + 1] if i + 1 < len(refs) else None
    def ref_page(p, r=r, prev_r=prev_r, next_r=next_r):
        facts = "".join(f"<dt>{E(a)}</dt><dd>{E(b)}</dd>" for a, b in r["facts"])
        facts = f'<div class="prose-card tile" data-light><dl>{facts}</dl></div>' if facts else ""
        place = f'<div class="prose-card tile" data-light><dl><dt>Ort</dt><dd>{E(r["place"])}</dd><dt>Jahr</dt><dd>{E(r["group"])}</dd></dl></div>'
        shots = "".join(f'<button type="button" aria-label="Bild {k+1} vergrössern"><img src="{img(g, p)}" alt="{E(r["name"])}, Bild {k+1}" loading="{"eager" if k == 0 else "lazy"}"></button>'
                        for k, g in enumerate(r["gallery"]) if img(g, p))
        pager = '<nav class="pager" aria-label="Weitere Referenzen">'
        pager += (f'<a class="tile" href="{prev_r["slug"]}.html"><span class="dir">← Vorherige</span><h3>{E(prev_r["name"])}</h3></a>' if prev_r else "<span></span>")
        pager += (f'<a class="tile next" href="{next_r["slug"]}.html"><span class="dir">Nächste →</span><h3>{E(next_r["name"])}</h3></a>' if next_r else "<span></span>")
        pager += "</nav>"
        return (head(p, r["name"], r["place"], [("Referenzen", "referenzen.html"), (r["name"], None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light>{fix_html(r["html"], p) or "<p>Fassade von Fahrni Fassadensysteme AG.</p>"}</div>'
                + f'<div class="facts">{facts}{place}</div></div>'
                + f'<div class="gallery">{shots}</div>{pager}{lightbox()}')
    page(f"referenzen/{r['slug']}.html", f"{r['name']}, {r['place']} · Referenzen · FAHRNI", ref_page, "referenzen", f"Referenz {r['name']}, {r['place']}")

# ---------------------------------------------------------------- Unternehmen
U = C["unternehmen"]
def unternehmen(p):
    years = "".join(f'<article class="tile t-year reveal{" now" if k == len(U["timeline"]) - 1 else ""}" style="--d:{(k%4)*.05}s" data-light>'
                    f'<span class="yr">{y["year"]}</span><div>{"".join(f"<p>{E(t)}</p>" for t in y["text"])}</div></article>'
                    for k, y in enumerate(U["timeline"]))
    pr = C["prinzipien"]["paras"]
    return (head(p, "Unternehmen", U["title"], [("Unternehmen", None)])
            + f'<section><div class="grid">'
            + f'<a class="tile t-about span-7 tall reveal" href="{p}prinzipien.html"><p class="eyebrow"><b>Prinzipien</b></p><h2 style="font-size:clamp(30px,3.2vw,48px);max-width:22ch">{E(pr[0])}</h2><span class="link-line">Unsere Prinzipien {ic()}</span></a>'
            + f'<a class="tile span-5 tall reveal" style="--d:.08s" href="{p}presse.html"><div class="tile-top"><span class="eyebrow"><b>Presse</b></span>{arrow()}</div><div><h3>{len(C["presse"])} Fachberichte</h3><p>Projekte von Fahrni in der Fachpresse.</p></div></a>'
            + f'</div></section><section aria-labelledby="g-title"><div class="section-head reveal"><h2 id="g-title">Geschichte. <span>{U["timeline"][0]["year"]} bis heute.</span></h2></div>'
            + f'<div class="timeline">{years}</div></section>')
page("unternehmen.html", "Unternehmen · FAHRNI", unternehmen, "unternehmen", U["title"])

def prinzipien(p):
    pr = C["prinzipien"]["paras"]; ims = [img(i, p) for i in C["prinzipien"]["imgs"]]
    blocks = []
    for k in range(0, len(pr), 2):
        pic = ims[k // 2] if k // 2 < len(ims) else None
        blocks.append(f'<div class="service"><article class="tile s-text reveal" data-light><div class="prose" style="grid-column:auto">{"".join(f"<p style=font-size:clamp(20px,2vw,28px);color:#fff>{E(x)}</p>" for x in pr[k:k+2])}</div></article>'
                      + (f'<div class="tile s-img reveal" data-light><div class="media"><img src="{pic}" alt="" loading="lazy"></div></div>' if pic else "") + "</div>")
    return head(p, "Prinzipien", "", [("Unternehmen", "unternehmen.html"), ("Prinzipien", None)]) + "".join(blocks)
page("prinzipien.html", "Prinzipien · FAHRNI", prinzipien, "unternehmen")

def presse(p):
    t = "".join(f'<a class="tile reveal" style="--d:{(k%3)*.05}s" href="{E(x["pdf"])}" target="_blank" rel="noopener"><div class="tile-top"><span class="pdf">PDF</span>{arrow("out")}</div><h3>{E(x["title"])}</h3></a>'
                for k, x in enumerate(C["presse"]))
    return head(p, "Presse", "Fahrni-Projekte in der Fachpresse. Die Berichte öffnen sich als PDF.", [("Unternehmen", "unternehmen.html"), ("Presse", None)]) + f'<div class="list-tiles">{t}</div>'
page("presse.html", "Presse · FAHRNI", presse, "unternehmen")

for key, path, label in (("dsgvo", "datenschutz.html", "Datenschutz"), ("abb", "beschaffungsbedingungen.html", "Allgemeine Beschaffungsbedingungen")):
    d = C[key]
    def legal(p, d=d, label=label):
        return head(p, d["title"] if key != "dsgvo" else "Datenschutz", lead(d["lead"]), [("Unternehmen", "unternehmen.html"), (label, None)]) + \
            f'<div class="detail"><div class="prose prose-card" style="grid-column:1/-1; max-width:900px">{fix_html(d["html"], p)}</div></div>'
    page(path, f"{label} · FAHRNI", legal, "unternehmen")

# ---------------------------------------------------------------- News
def news_list(p):
    return head(p, "News", "Neue Aufträge der Fahrni Fassadensysteme AG.", [("News", None)]) + \
        f'<div class="grid">{"".join(news_tile(n, p, "span-4", (k%3)*.06) for k, n in enumerate(news))}</div>'
page("news.html", "News · FAHRNI", news_list, "news")
for k, n in enumerate(news):
    prev_n, next_n = news[k - 1] if k > 0 else None, news[k + 1] if k + 1 < len(news) else None
    def news_page(p, n=n, prev_n=prev_n, next_n=next_n):
        shots = "".join(f'<button type="button" aria-label="Bild vergrössern"><img src="{img(g, p)}" alt="{E(n["title"])}"></button>' for g in n["imgs"] if img(g, p))
        pager = '<nav class="pager" aria-label="Weitere News">'
        pager += (f'<a class="tile" href="{prev_n["slug"]}.html"><span class="dir">← Neuer</span><h3>{E(prev_n["title"])}</h3></a>' if prev_n else "<span></span>")
        pager += (f'<a class="tile next" href="{next_n["slug"]}.html"><span class="dir">Älter →</span><h3>{E(next_n["title"])}</h3></a>' if next_n else "<span></span>")
        pager += "</nav>"
        return (head(p, n["title"].replace("Neuer Auftrag ", ""), n["abstract"], [("News", "news.html"), ("Neuer Auftrag", None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light><p class="eyebrow"><b>Neuer Auftrag</b></p>{fix_html(n["html"], p)}</div>'
                + f'<div class="facts"><a class="tile" href="{p}referenzen.html"><div class="tile-top"><span class="eyebrow">Weitere Projekte</span>{arrow()}</div><h3>Alle Referenzen</h3></a></div></div>'
                + f'<div class="gallery">{shots}</div>{pager}{lightbox()}')
    page(f"news/{n['slug']}.html", f"{n['title']} · News · FAHRNI", news_page, "news", n["abstract"])

# ---------------------------------------------------------------- Jobs
def jobs_list(p):
    t = "".join(f'<a class="tile span-6 tall reveal" style="--d:{(k%2)*.06}s" href="{p}jobs/{j["slug"]}.html"><div class="tile-top"><span class="num">Stelle 0{k+1}</span>{arrow()}</div><h3 style="font-size:clamp(28px,3vw,44px)">{E(j["title"])}</h3></a>'
                for k, j in enumerate(jobs))
    pic = img(teaser_img["/de/jobs/"], p)
    hero = f'<div class="grid" style="margin-bottom:var(--gap)"><div class="tile span-12 tall reveal" data-light style="justify-content:flex-end"><div class="media"><img src="{pic}" alt="" loading="lazy"></div><h2 style="font-size:clamp(36px,5vw,72px)">Bauen Sie mit Fahrni.</h2></div></div>'
    return head(p, "Jobs", "Offene Stellen", [("Jobs", None)]) + hero + f'<div class="grid">{t}</div>'
page("jobs.html", "Jobs · FAHRNI", jobs_list, "jobs")
for j in jobs:
    def job_page(p, j=j):
        return (head(p, j["title"], "", [("Jobs", "jobs.html"), ("Stelle", None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light>{fix_html(j["html"], p)}</div>'
                + f'<div class="facts"><div class="tile" data-light style="background:var(--blue)"><span class="eyebrow" style="color:rgba(255,255,255,.75)">Bewerbung</span><h3>Fahrni Fassadensysteme AG</h3><p style="color:rgba(255,255,255,.85)">Bernstrasse 84, 3250 Lyss<br><a href="{TEL}">Tel. 032 387 25 25</a></p></div>'
                + f'<a class="tile" href="{p}jobs.html"><div class="tile-top"><span class="eyebrow">Weitere Stellen</span>{arrow()}</div><h3>Alle offenen Stellen</h3></a></div></div>')
    page(f"jobs/{j['slug']}.html", f"{j['title']} · Jobs · FAHRNI", job_page, "jobs", j["title"])

# ---------------------------------------------------------------- Kontakt
def kontakt(p):
    pic = img(teaser_img["/de/kontakt/"], p)
    return (head(p, "Kontakt", lead(C["kontakt"]["lead"]), [("Kontakt", None)])
            + f'<div class="grid" style="margin-bottom:var(--gap)"><a class="tile span-8 tall reveal" href="{TEL}" style="justify-content:flex-end"><div class="media"><img src="{pic}" alt="" loading="lazy"></div>'
            + f'<div style="display:grid;gap:10px"><span class="eyebrow"><b>Hauptsitz</b> · Lyss</span><span style="font-family:var(--head);font-size:clamp(40px,5vw,76px);line-height:1">{PHONE}</span><p>Fahrni Fassadensysteme AG, Bernstrasse 84, CH-3250 Lyss</p></div></a>'
            + f'<div class="tile span-4 tall reveal" style="--d:.08s;background:var(--blue)" data-light><span class="eyebrow" style="color:rgba(255,255,255,.75)">E-Mail</span><div><h3>Schreiben Sie uns</h3><p style="color:rgba(255,255,255,.85)"><span class="email-todo" style="color:#ffe08a">[E-Mail-Adresse ergänzen]</span></p></div></div></div>'
            + f'<div class="grid">{sites(p)}</div>')
page("kontakt.html", "Kontakt · FAHRNI", kontakt, "kontakt", lead(C["kontakt"]["lead"]))

# ---------------------------------------------------------------- Assets
fonts = {k: "data:font/otf;base64," + base64.b64encode((HERE / "assets/fonts" / f).read_bytes()).decode()
         for k, f in (("@@FONT_HEAD@@", "FrutigerNext-Condensed-Bold.otf"), ("@@FONT_BODY@@", "Frutiger-45-Light.otf"))}
css = (HERE / "src/site.css").read_text(encoding="utf-8")
for k, v in fonts.items(): css = css.replace(k, v)
(HERE / "assets/site.css").write_text(css, encoding="utf-8")
(HERE / "assets/site.js").write_text((HERE / "src/site.js").read_text(encoding="utf-8"), encoding="utf-8")
n = len(list(HERE.glob("*.html"))) + len(list(HERE.glob("*/*.html")))
print(f"{n} Seiten erzeugt")
