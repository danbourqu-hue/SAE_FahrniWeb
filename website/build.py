"""Erzeugt alle Seiten des FAHRNI-Website-Entwurfs aus content/content.json.

Aufruf:  python3 build.py
Quelle:  content/content.json (Inhalt von fahrni.com), content/images.json (lokale Bilder),
         src/site.css, src/site.js
Ausgabe: *.html, referenzen/, news/, jobs/, assets/site.css, assets/site.js
"""
import base64, html, json, pathlib, re, subprocess, sys

HERE = pathlib.Path(__file__).parent
# Eine Variante: helle Kopfleiste mit Menüband UND Burger-Button (grosses Menü mit Bildern)
VARIANT = "hell"
VARIANTS = {"hell": ("", "Kopfleiste mit Burger-Menü")}
HELL = True
MENU = False
OUT = HERE / VARIANTS[VARIANT][0] if VARIANTS[VARIANT][0] else HERE
CUR = {"path": "index.html"}
C = json.loads((HERE / "content/content.json").read_text(encoding="utf-8"))
S = json.loads((HERE / "content/social.json").read_text(encoding="utf-8"))
CH = {c["id"]: c for c in S["channels"]}
IMG = json.loads((HERE / "content/images.json").read_text(encoding="utf-8"))
E = html.escape
PHONE = "+41 32 387 25 25"
TEL = "tel:+41323872525"

def img(url, prefix):
    p = IMG.get(url)
    return f"{prefix}assets/{p}" if p else None

def pdf_slug(x): return x["file"].rsplit("/", 1)[-1].rsplit(".", 1)[0]

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
       ("news", "News"), ("jobs", "Karriere"), ("social", "Social"), ("kontakt", "Kontakt")]

MENU_INFO = {  # Bild und Kurztext je Menüpunkt (wie im Menü von fahrni.com)
    "leistungen": ("/de/leistungen/", None), "referenzen": ("/de/referenzen/", None), "unternehmen": ("/de/unternehmen/", None),
    "news": ("/de/news/", "Neue Aufträge der Fahrni Fassadensysteme AG."), "jobs": ("/de/jobs/", None),
    "social": (None, "Beiträge aus LinkedIn, Instagram und YouTube."), "kontakt": ("/de/kontakt/", None)}

def header_burger(prefix, active):
    teaser = {t["href"]: t for t in C["home"]["teasers"]}
    links, pics = [], []
    for i, (k, v) in enumerate(NAV):
        href, txt = MENU_INFO[k]
        t = teaser.get(href) if href else None
        desc = txt or (t["text"] if t and t["text"] else "")
        src = img(t["img"], prefix) if t else (f"{prefix}assets/video/social/{S['posts'][0]['id']}.jpg")
        cur = ' aria-current="page"' if k == active else ""
        sub = ""
        if k == "unternehmen":
            sub = f'<span class="mm-sub"><a href="{prefix}prinzipien.html">Prinzipien</a><a href="{prefix}presse.html">Presse</a></span>'
        links.append(f'<li style="--k:{i}"><a class="mm-link" href="{prefix}{k}.html" data-pic="{i}"{cur}><span class="mm-num">0{i+1}</span>'
                     f'<span class="mm-label">{E(v)}</span><span class="mm-desc">{E(desc)}</span></a>{sub}</li>')
        pics.append(f'<img src="{src}" alt="" data-pic="{i}"{" class=is-on" if i == 0 else ""} loading="lazy">')
    loc = C["kontakt"]["locations"]
    return f"""<header class="header hdr-burger" id="header">
  <a class="brand-solo" href="{prefix}index.html" aria-label="FAHRNI façade systems, zur Startseite"><img src="{prefix}assets/img/logo-dunkel.png" alt="FAHRNI façade systems" width="720" height="282"></a>
  <button class="mburger" id="burger" aria-expanded="false" aria-controls="menu" aria-label="Menü öffnen"><span class="mburger-label">Menü</span><span class="mburger-icon" aria-hidden="true"><i></i><i></i><i></i></span></button>
</header>
<div class="menu mmenu" id="menu">
  <div class="mm-inner">
    <nav class="mm-nav" aria-label="Hauptnavigation"><ol>{"".join(links)}</ol></nav>
    <div class="mm-side">
      <div class="mm-visual" aria-hidden="true">{"".join(pics)}</div>
      <div class="mm-meta">
        <div><span class="eyebrow">Hauptsitz</span><a class="mm-phone" href="{TEL}">{PHONE}</a><p>Fahrni Fassadensysteme AG<br>Bernstrasse 84, CH-3250 Lyss</p></div>
        <div><span class="eyebrow">Standorte</span><p>{"<br>".join(E(l["name"]) + ", " + E(l["address"][-1].split(" ", 1)[-1]) for l in loc)}</p></div>
      </div>
    </div>
  </div>
</div>"""

def header(prefix, active):
    full = header_burger(prefix, active)
    big_menu = full[full.index('<div class="menu mmenu" id="menu">'):]
    nav = "".join(f'<a href="{prefix}{k}.html"' + (' aria-current="page"' if k == active else "") + f">{v}</a>" for k, v in NAV[:-1])
    return f"""<header class="header" id="header"><div class="bar">
  <a class="brand" href="{prefix}index.html" aria-label="FAHRNI façade systems, zur Startseite"><img src="{prefix}assets/img/logo-dunkel.png" alt="FAHRNI façade systems" width="720" height="282"></a>
  <nav class="nav" aria-label="Hauptnavigation">{nav}</nav>
  <a class="btn btn-primary" href="{prefix}kontakt.html">Kontakt {ic()}</a>
  <button class="mburger" id="burger" aria-expanded="false" aria-controls="menu" aria-label="Menü öffnen"><span class="mburger-label">Menü</span><span class="mburger-icon" aria-hidden="true"><i></i><i></i><i></i></span></button>
</div></header>
{big_menu}"""
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

def versions(prefix):
    up = prefix + ("../" if VARIANTS[VARIANT][0] else "")
    return " · ".join((f'<b>{E(lbl)}</b>' if key == VARIANT else f'<a class="theme-switch" href="{up}{d}{CUR["path"]}">{E(lbl)}</a>')
                      for key, (d, lbl) in VARIANTS.items())

def footer(prefix):
    sites_html = "".join(
        f'<li><b>{E(l["name"])}</b><br>{"<br>".join(E(x) for x in l["address"])}'
        + (f'<br><a href="tel:{re.sub(r"[^0-9+]", "", l["tel"])}">{E(l["tel"])}</a>' if l["tel"] else "") + "</li>"
        for l in C["kontakt"]["locations"])
    return f"""<footer class="footer"><div class="foot-band"><div class="wrap">
  <div class="foot-grid">
    <div class="foot-brand"><img src="{prefix}assets/img/logo-footer.png" alt="FAHRNI façade systems" width="720" height="282">
      <p class="claim-text">Praktisch schön und effizient, wir verhelfen Funktionalität und einzigartigem Design zum Leben.</p>
      <a class="foot-phone" href="{TEL}">{PHONE}</a></div>
    <div><h4>Seiten</h4><ul>{''.join(f'<li><a href="{prefix}{k}.html">{v}</a></li>' for k, v in NAV)}</ul></div>
    <div><h4>Unternehmen</h4><ul><li><a href="{prefix}prinzipien.html">Prinzipien</a></li><li><a href="{prefix}presse.html">Presse</a></li><li><a href="{prefix}datenschutz.html">Datenschutz</a></li><li><a href="{prefix}beschaffungsbedingungen.html">Allgemeine Beschaffungsbedingungen</a></li></ul></div>
    <div><h4>Standorte</h4><ul class="foot-sites">{sites_html}</ul></div>
  </div>
  <div class="foot-base"><span>© Fahrni Fassadensysteme AG</span><span>Website-Entwurf im neuen Corporate Design · Inhalte von fahrni.com</span></div>
</div></div></footer>"""

def wayfinding(prefix, path, active):
    """Feste Wege zurück: Startseite, Karriere, Referenzen, Kontakt (ohne die aktuelle Rubrik)."""
    if path == "index.html": return ""
    ways = [("index.html", "", "Startseite", "Zurück zum Überblick"), ("jobs.html", "jobs", "Karriere", "Offene Stellen und Lehrstellen"),
            ("referenzen.html", "referenzen", "Referenzen", f"{len(C['referenzen']['items'])} Projekte"), ("kontakt.html", "kontakt", "Kontakt", PHONE)]
    tiles = "".join(f'<a class="tile way{" way-home" if not key else ""}" href="{prefix}{h}"><div class="tile-top"><span class="eyebrow">{E(sub)}</span>{arrow()}</div><h3>{E(lbl)}</h3></a>'
                    for h, key, lbl, sub in ways if key != active or not key)
    return f'<nav class="ways" aria-label="Weiter auf der Website"><h2 class="sr-only">Weiter auf der Website</h2>{tiles}</nav>'

def page(path, title, body, active="", desc=""):
    CUR["path"] = path
    depth = path.count("/"); prefix = "../" * depth
    doc = f"""<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{E(title)}</title>
<meta name="description" content="{E(desc or 'Fahrni Fassadensysteme AG, Lyss: Aluminium-, Glas- und Elementfassaden.')}">
<meta name="theme-color" content="#f3f4f4">
<link rel="icon" href="{prefix}assets/img/favicon.png">
<link rel="stylesheet" href="{prefix}assets/site.css">
</head>
<body{' class="nav-burger"' if MENU else ''}>
<div class="ambient" aria-hidden="true"></div>
<div class="cone" aria-hidden="true"><div class="cone__halo"></div><div class="cone__dot"></div></div>
{ICONS}
{header(prefix, active)}
<main class="wrap" id="top">
{body(prefix)}
{wayfinding(prefix, path, active)}
</main>
{footer(prefix)}
<script src="{prefix}assets/site.js"></script>
</body>
</html>
"""
    if VARIANTS[VARIANT][0]:  # Unterordner-Variante: Assets liegen eine Ebene höher und werden geteilt
        doc = doc.replace(f'"{prefix}assets/', f'"{prefix}../assets/')
    if HELL:
        doc = doc.replace("img/logo-dunkel.png", "img/logo-blau.png")
        doc = doc.replace('<meta name="theme-color" content="#f3f4f4">', '<meta name="theme-color" content="#f3f4f4">')
    out = OUT / path; out.parent.mkdir(parents=True, exist_ok=True)
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
        out.append(f'<article class="tile t-site reveal{" main" if i == 0 else (" tile-grey" if i % 2 else "")}" style="--d:{i*.06}s" data-light>'
                   f'<div class="tile-top"><span class="kind">{E(l["name"])}</span><span class="pin"></span></div>'
                   f'<div><h3>{E(city)}</h3><address>{"<br>".join(E(a) for a in l["address"])}</address>'
                   + (f'<p style="margin-top:10px"><a class="tel" href="{telh}">{E(tel)}</a></p>' if tel else "") + "</div></article>")
    return "".join(out)

def post_video(post, p):
    if post.get("video"): return f"{p}assets/{post['video']}", f"{p}assets/{post['poster']}"
    return f"{p}assets/video/social/{post['id']}.mp4", f"{p}assets/video/social/{post['id']}.jpg"

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
    <a class="tile tile-dark t-contact reveal" href="{p}kontakt.html" style="--d:.08s">
      <div class="tile-top"><span class="eyebrow">Die beste Adresse für Ihre Fassade</span>{arrow()}</div>
      <div class="claim"><span class="status"><i></i>Hauptsitz Lyss</span><span class="phone">{PHONE}</span><p>Bernstrasse 84, CH-3250 Lyss</p></div>
    </a>
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
    <a class="tile tile-grey t-about span-7 x-tall reveal" href="{p}unternehmen.html">
      <p class="eyebrow"><b>Unternehmen</b> · seit {tl[0]['year']}</p>
      <h2 id="u-title">Vom Einzel&shy;unternehmen zum <em>führenden</em> Fassadenbauer der Schweiz.</h2>
      <p style="max-width:60ch">{E(intro[1] if len(intro) > 1 else '')}</p>
      <span class="link-line">Geschichte ansehen {ic()}</span>
    </a>
    <a class="tile tile-blue span-5 x-tall reveal" style="--d:.08s" href="{p}unternehmen.html">
      <div class="tile-top"><span class="eyebrow">{E(tl[0]['text'][0])}</span>{arrow()}</div>
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

<section aria-labelledby="sm-title">
  <div class="section-head reveal"><h2 id="sm-title">Social Media. <span>Neu aus unseren Kanälen.</span></h2><a class="btn btn-ghost" href="{p}social.html">Alle Beiträge {ic()}</a></div>
  <div class="grid">{"".join(f'<a class="tile span-4 sm-tile reveal" style="--d:{k*.06}s" href="{p}social.html#post-{post["id"]}"><div class="media"><video muted playsinline loop preload="none" poster="{post_video(post, p)[1]}"><source src="{post_video(post, p)[0]}" type="video/mp4"></video></div><div class="tile-top"><span class="caption">{E(CH[post["channel"]]["name"])}</span>{arrow()}</div><div><h3>{E(post["title"])}</h3><p>{E(post["text"][:96].rsplit(" ", 1)[0])} …</p></div></a>' for k, post in enumerate(S["posts"][:3]))}</div>
</section>

<section aria-labelledby="j-title">
  <div class="grid">
    <a class="tile span-8 x-tall reveal" href="{p}jobs.html" style="justify-content:flex-end">
      <div class="media"><img src="{img(teaser_img['/de/jobs/'], p)}" alt="" loading="lazy" class="pos-top"></div>
      <div style="display:grid; gap:14px; max-width:60ch"><span class="caption accent">Karriere</span><h3 id="j-title" style="font-size:clamp(36px,4.4vw,64px)">Bauen Sie mit Fahrni.</h3><p>Offene Stellen in Produktion, Montage, Planung und Logistik.</p><span class="link-line">Offene Stellen {ic()}</span></div>
    </a>
    <div class="tile tile-grey span-4 reveal" style="--d:.08s" data-light>
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
                    f'<article class="tile s-text reveal{" tile-grey" if i % 2 else ""}" data-light><span class="num">0{i+1} / 0{len(L)}</span><h2>{E(s["title"])}</h2><div class="prose" style="grid-column:auto">{fix_html(s["html"], p)}</div></article>'
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
        place = f'<div class="prose-card tile tile-grey" data-light><dl><dt>Ort</dt><dd>{E(r["place"])}</dd><dt>Jahr</dt><dd>{E(r["group"])}</dd></dl></div>'
        shots = "".join(f'<button type="button" aria-label="Bild {k+1} vergrössern"><img src="{img(g, p)}" alt="{E(r["name"])}, Bild {k+1}" loading="{"eager" if k == 0 else "lazy"}"></button>'
                        for k, g in enumerate(r["gallery"]) if img(g, p))
        pager = '<nav class="pager" aria-label="Weitere Referenzen">'
        pager += (f'<a class="tile tile-grey" href="{prev_r["slug"]}.html"><span class="dir">← Vorherige</span><h3>{E(prev_r["name"])}</h3></a>' if prev_r else "<span></span>")
        pager += (f'<a class="tile tile-grey next" href="{next_r["slug"]}.html"><span class="dir">Nächste →</span><h3>{E(next_r["name"])}</h3></a>' if next_r else "<span></span>")
        pager += "</nav>"
        return (head(p, r["name"], r["place"], [("Referenzen", "referenzen.html"), (r["name"], None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light>{fix_html(r["html"], p) or "<p>Fassade von Fahrni Fassadensysteme AG.</p>"}</div>'
                + f'<div class="facts">{facts}{place}</div></div>'
                + f'<div class="gallery">{shots}</div>{pager}{lightbox()}')
    page(f"referenzen/{r['slug']}.html", f"{r['name']}, {r['place']} · Referenzen · FAHRNI", ref_page, "referenzen", f"Referenz {r['name']}, {r['place']}")

# ---------------------------------------------------------------- Unternehmen
U = C["unternehmen"]
GS = json.loads((HERE / "content/geschichte.json").read_text(encoding="utf-8"))
LS = {x["id"]: x for x in C["leistungen"]["sections"]}
LOC = {l["name"]: l for l in C["kontakt"]["locations"]}

def ms_image(key, p):
    t = {x["href"]: x["img"] for x in C["home"]["teasers"]}
    if key in LS: return img(LS[key]["img"], p), LS[key]["title"]
    if key == "unternehmen": return img(t["/de/unternehmen/"], p), "Unternehmen"
    if key == "referenzen": return img(t["/de/referenzen/"], p), "Referenzen"
    if key == "kontakt": return img(t["/de/kontakt/"], p), "Kontakt"
    if key == "prinzipien-2": return img(C["prinzipien"]["imgs"][1], p), "Prinzipien"
    pat = {"london": "London|LONDON", "basel": "Basel|BASEL", "biel": "Biel|BIEL|Bienne"}.get(key)
    if pat:
        r = next((r for r in refs if re.search(pat, r["place"])), None)
        if r: return (img(r["gallery"][0], p) if r["gallery"] else img(r["thumb"], p)), f'{r["name"]}, {r["place"]}'
    if key == "neueste": return img(refs[0]["gallery"][0], p), f'{refs[0]["name"]}, {refs[0]["place"]}'
    return None, ""

def milestones(p):
    """Detail-Dialoge der Meilensteine: Originaltext plus passende Inhalte anderer Seiten."""
    T = U["timeline"]; years = [int(y["year"]) for y in T]
    out, counts = [], {}
    for k, y in enumerate(T):
        g = GS.get(y["year"], {}); yr = years[k]; nxt = years[k + 1] if k + 1 < len(T) else 9999
        parts = []
        if g.get("home"):
            parts.append(f'<section class="ms-sec"><h3>Aus «Startseite»</h3>{"".join(f"<p>{E(x)}</p>" for x in C["home"]["intro"])}'
                         f'<a class="link-line" href="{p}index.html">Zur Startseite {ic()}</a></section>')
        if g.get("prinzipien"):
            pr = C["prinzipien"]["paras"]
            parts.append(f'<section class="ms-sec"><h3>Aus «Prinzipien»</h3>{"".join(f"<p>{E(pr[i])}</p>" for i in g["prinzipien"] if i < len(pr))}'
                         f'<a class="link-line" href="{p}prinzipien.html">Zu den Prinzipien {ic()}</a></section>')
        for lid in g.get("leistungen", []):
            l = LS[lid]
            parts.append(f'<section class="ms-sec"><h3>Aus «Leistungen»: {E(l["title"])}</h3><div class="prose">{fix_html(l["html"], p)}</div>'
                         f'<a class="link-line" href="{p}leistungen.html#{lid}">Zur Leistung {E(l["title"])} {ic()}</a></section>')
        if g.get("standorte"):
            cards = "".join(f'<div class="ms-site"><b>{E(n)}</b><br>{"<br>".join(E(a) for a in LOC[n]["address"])}'
                            + (f'<br><a href="tel:{re.sub(r"[^0-9+]", "", LOC[n]["tel"])}">{E(LOC[n]["tel"])}</a>' if LOC[n]["tel"] else "") + "</div>"
                            for n in g["standorte"] if n in LOC)
            parts.append(f'<section class="ms-sec"><h3>{"Standort" if len(g["standorte"]) == 1 else "Standorte heute"}</h3><div class="ms-sites">{cards}</div></section>')
        rs = []
        if g.get("referenzen"): rs = [r for r in refs if re.search(g["referenzen"], r["place"] + " " + r["name"])]
        if g.get("neueste_referenzen"): rs = refs[: g["neueste_referenzen"]]
        if rs:
            q = g.get("referenzen", "").split("|")[0]
            tiles = "".join(f'<a class="ms-ref" href="{p}referenzen/{r["slug"]}.html"><img src="{img(r["thumb"], p) or ""}" alt="" loading="lazy"><span><b>{E(r["name"])}</b>{E(r["place"])}</span></a>' for r in rs[:6])
            more = (f'<a class="link-line" href="{p}referenzen.html?q={q}">Alle {len(rs)} Referenzen mit «{E(q)}» {ic()}</a>' if q and len(rs) > 6
                    else f'<a class="link-line" href="{p}referenzen.html">Alle Referenzen {ic()}</a>')
            title = "Neueste Referenzen" if g.get("neueste_referenzen") else f"Referenzen ({len(rs)})"
            parts.append(f'<section class="ms-sec"><h3>{title}</h3><div class="ms-refs">{tiles}</div>{more}</section>')
        if g.get("news"):
            items = "".join(f'<li><a href="{p}news/{n["slug"]}.html">{E(n["title"])}</a></li>' for n in news[: g["news"]])
            parts.append(f'<section class="ms-sec"><h3>Neue Aufträge</h3><ul class="ms-list">{items}</ul></section>')
        pr_items = [x for x in C["presse"] if x.get("year") and yr <= x["year"] < nxt]
        if pr_items:
            items = "".join(f'<li><a href="{p}presse/{pdf_slug(x)}.html">{E(x["title"])}</a> <span>{x["year"]} · PDF</span></li>' for x in pr_items)
            parts.append(f'<section class="ms-sec"><h3>Fachpresse {yr}{" bis heute" if nxt == 9999 else ("" if nxt - 1 == yr else "–" + str(nxt - 1))}</h3><ul class="ms-list">{items}</ul></section>')
        src, cap = ms_image(g.get("bild", ""), p)
        pic = f'<figure class="ms-media"><img src="{src}" alt="{E(cap)}" loading="lazy"><figcaption>{E(cap)}</figcaption></figure>' if src else ""
        prev_b = f'<button type="button" class="btn btn-ghost" data-ms-go="ms-{T[k-1]["year"]}">← {T[k-1]["year"]}</button>' if k else "<span></span>"
        next_b = f'<button type="button" class="btn btn-ghost" data-ms-go="ms-{T[k+1]["year"]}">{T[k+1]["year"]} →</button>' if k + 1 < len(T) else "<span></span>"
        counts[y["year"]] = len(rs) + len(pr_items)
        out.append(f'<dialog class="ms" id="ms-{y["year"]}" aria-labelledby="ms-{y["year"]}-t">'
                   f'<button type="button" class="ms-close" aria-label="Schliessen">×</button><div class="ms-grid">{pic}<div class="ms-body">'
                   f'<p class="eyebrow"><b>Meilenstein</b> · {k+1:02d} / {len(T):02d}</p><div class="ms-year">{y["year"]}</div>'
                   f'<h2 id="ms-{y["year"]}-t">{E(g.get("thema", y["text"][0]))}</h2>'
                   f'<ul class="ms-facts">{"".join(f"<li>{E(t)}</li>" for t in y["text"])}</ul>'
                   f'{"".join(parts)}<nav class="ms-nav" aria-label="Weitere Meilensteine">{prev_b}{next_b}</nav></div></div></dialog>')
    return "".join(out), counts

def unternehmen(p):
    dialogs, counts = milestones(p)
    T = U["timeline"]
    items = []
    for k, y in enumerate(T):
        last = k == len(T) - 1
        items.append(f'<li class="tl-item{" tl-now" if last else ""}" data-year="{y["year"]}">'
                     f'<span class="tl-dot" aria-hidden="true"></span><span class="tl-link" aria-hidden="true"></span>'
                     f'<article class="tile tl-card{" tile-blue" if last else (" tile-grey" if int(y["year"]) < 2000 else "")}" data-light><span class="yr">{y["year"]}</span>'
                     f'<div>{"".join(f"<p>{E(t)}</p>" for t in y["text"])}</div>'
                     f'<button type="button" class="ms-open" data-ms-go="ms-{y["year"]}">Mehr erfahren <span aria-hidden="true">+</span></button></article></li>')
    span = int(T[-1]["year"]) - int(T[0]["year"])
    years = (f'<div class="tl" id="tl" aria-labelledby="g-title"><div class="tl-sticky">'
             f'<div class="tl-top wrap"><div class="section-head" style="margin:0"><h2 id="g-title">Geschichte. <span>{T[0]["year"]} bis heute.</span></h2>'
             f'<p>{len(T)} Meilensteine aus {span} Jahren Fassadenbau. Scrollen Sie, die Zeitachse läuft mit.</p></div>'
             f'<div class="tl-nav"><button type="button" class="tl-arrow" id="tl-prev" aria-label="Vorheriger Meilenstein">{ic("back")}</button>'
             f'<div class="tl-progress" aria-hidden="true"><span id="tl-year">{T[0]["year"]}</span><i><b></b></i><span>{T[-1]["year"]}</span></div>'
             f'<button type="button" class="tl-arrow tl-arrow-next" id="tl-next" aria-label="Nächster Meilenstein">{ic()}</button></div>'
             f'<p class="tl-hint" id="tl-hint" aria-hidden="true"><span class="tl-mouse"><i></i></span>Scrollen oder Pfeile nutzen <span class="tl-hint-arrow">→</span></p></div>'
             f'<div class="tl-viewport"><div class="tl-track"><div class="tl-axis" aria-hidden="true"><div class="tl-fill"></div><div class="tl-head"></div></div>'
             f'<ol class="tl-list">{"".join(items)}</ol></div></div></div></div>')
    pr = C["prinzipien"]["paras"]
    return (head(p, "Unternehmen", U["title"], [("Unternehmen", None)])
            + f'<section><div class="grid">'
            + f'<a class="tile t-about span-7 tall reveal" href="{p}prinzipien.html"><p class="eyebrow"><b>Prinzipien</b></p><h2 style="font-size:clamp(30px,3.2vw,48px);max-width:22ch">{E(pr[0])}</h2><span class="link-line">Unsere Prinzipien {ic()}</span></a>'
            + f'<a class="tile tile-dark span-5 tall reveal" style="--d:.08s" href="{p}presse.html"><div class="tile-top"><span class="eyebrow"><b>Presse</b></span>{arrow()}</div><div><h3>{len(C["presse"])} Fachberichte</h3><p>Projekte von Fahrni in der Fachpresse.</p></div></a>'
            + f'</div></section><section class="tl-section">' + years + '</section>' + dialogs)
page("unternehmen.html", "Unternehmen · FAHRNI", unternehmen, "unternehmen", U["title"])

def prinzipien(p):
    pr = C["prinzipien"]["paras"]; ims = [img(i, p) for i in C["prinzipien"]["imgs"]]
    blocks = []
    for k in range(0, len(pr), 2):
        pic = ims[k // 2] if k // 2 < len(ims) else None
        blocks.append(f'<div class="service"><article class="tile s-text reveal{" tile-grey" if k else ""}" data-light><div class="prose" style="grid-column:auto">{"".join(f'<p class=statement>{E(x)}</p>' for x in pr[k:k+2])}</div></article>'
                      + (f'<div class="tile s-img reveal" data-light><div class="media"><img src="{pic}" alt="" loading="lazy"></div></div>' if pic else "") + "</div>")
    return head(p, "Prinzipien", "", [("Unternehmen", "unternehmen.html"), ("Prinzipien", None)]) + "".join(blocks)
page("prinzipien.html", "Prinzipien · FAHRNI", prinzipien, "unternehmen")

def presse(p):
    t = "".join(f'<a class="tile reveal" style="--d:{(k%3)*.05}s" href="{p}presse/{pdf_slug(x)}.html"><div class="tile-top"><span class="pdf">PDF</span>{arrow()}</div><h3>{E(x["title"])}</h3></a>'
                for k, x in enumerate(C["presse"]))
    return head(p, "Presse", "Fahrni-Projekte in der Fachpresse. Jeder Bericht öffnet sich hier auf der Website.", [("Unternehmen", "unternehmen.html"), ("Presse", None)]) + f'<div class="list-tiles">{t}</div>'
page("presse.html", "Presse · FAHRNI", presse, "unternehmen")

for k, x in enumerate(C["presse"]):
    def pdf_page(p, x=x, k=k):
        pdf = f"{p}assets/{x['file']}"
        prev_x = C["presse"][k - 1] if k > 0 else None; next_x = C["presse"][k + 1] if k + 1 < len(C["presse"]) else None
        pager = '<nav class="pager" aria-label="Weitere Berichte">'
        pager += (f'<a class="tile tile-grey" href="{pdf_slug(prev_x)}.html"><span class="dir">← Vorheriger Bericht</span><h3>{E(prev_x["title"])}</h3></a>' if prev_x else "<span></span>")
        pager += (f'<a class="tile tile-grey next" href="{pdf_slug(next_x)}.html"><span class="dir">Nächster Bericht →</span><h3>{E(next_x["title"])}</h3></a>' if next_x else "<span></span>")
        pager += "</nav>"
        return (head(p, x["title"], f"Fachbericht{' · ' + str(x['year']) if x.get('year') else ''}", [("Unternehmen", "unternehmen.html"), ("Presse", "presse.html"), (x["title"], None)])
                + f'<div class="pdf-bar"><a class="btn btn-primary" href="{p}presse.html" data-back>← Zurück zur Übersicht</a>'
                + f'<a class="btn btn-ghost" href="{p}index.html">Startseite</a><a class="btn btn-ghost" href="{p}jobs.html">Karriere</a>'
                + f'<a class="btn btn-ghost pdf-dl" href="{pdf}" download>PDF herunterladen</a></div>'
                + f'<div class="pdf-frame"><iframe src="{pdf}#view=FitH" title="{E(x["title"])} (PDF)" loading="lazy"></iframe>'
                + f'<p class="pdf-fallback">Ihr Browser zeigt das PDF hier nicht an. <a href="{pdf}" download>PDF herunterladen</a></p></div>' + pager)
    page(f"presse/{pdf_slug(x)}.html", f"{x['title']} · Presse · FAHRNI", pdf_page, "unternehmen", f"Fachbericht {x['title']}")

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
        pager += (f'<a class="tile tile-grey" href="{prev_n["slug"]}.html"><span class="dir">← Neuer</span><h3>{E(prev_n["title"])}</h3></a>' if prev_n else "<span></span>")
        pager += (f'<a class="tile tile-grey next" href="{next_n["slug"]}.html"><span class="dir">Älter →</span><h3>{E(next_n["title"])}</h3></a>' if next_n else "<span></span>")
        pager += "</nav>"
        return (head(p, n["title"].replace("Neuer Auftrag ", ""), n["abstract"], [("News", "news.html"), ("Neuer Auftrag", None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light><p class="eyebrow"><b>Neuer Auftrag</b></p>{fix_html(n["html"], p)}</div>'
                + f'<div class="facts"><a class="tile tile-dark" href="{p}referenzen.html"><div class="tile-top"><span class="eyebrow">Weitere Projekte</span>{arrow()}</div><h3>Alle Referenzen</h3></a></div></div>'
                + f'<div class="gallery">{shots}</div>{pager}{lightbox()}')
    page(f"news/{n['slug']}.html", f"{n['title']} · News · FAHRNI", news_page, "news", n["abstract"])

# ---------------------------------------------------------------- Jobs
def jobs_list(p):
    t = "".join(f'<a class="tile span-6 tall reveal" style="--d:{(k%2)*.06}s" href="{p}jobs/{j["slug"]}.html"><div class="tile-top"><span class="num">Stelle 0{k+1}</span>{arrow()}</div><h3 style="font-size:clamp(28px,3vw,44px)">{E(j["title"])}</h3></a>'
                for k, j in enumerate(jobs))
    pic = img(teaser_img["/de/jobs/"], p)
    hero = f'<div class="grid" style="margin-bottom:var(--gap)"><div class="tile span-12 x-tall reveal" data-light style="justify-content:flex-end"><div class="media"><img src="{pic}" alt="" loading="lazy" class="pos-top"></div><h2 style="font-size:clamp(36px,5vw,72px)">Bauen Sie mit Fahrni.</h2></div></div>'
    return head(p, "Karriere", "Offene Stellen bei der Fahrni Fassadensysteme AG.", [("Karriere", None)]) + hero + f'<div class="grid">{t}</div>'
page("jobs.html", "Karriere · FAHRNI", jobs_list, "jobs")
for jk, j in enumerate(jobs):
    prev_j, next_j = jobs[jk - 1] if jk > 0 else None, jobs[jk + 1] if jk + 1 < len(jobs) else None
    def job_page(p, j=j, prev_j=prev_j, next_j=next_j):
        pager = '<nav class="pager" aria-label="Weitere Stellen">'
        pager += (f'<a class="tile tile-grey" href="{prev_j["slug"]}.html"><span class="dir">← Vorherige Stelle</span><h3>{E(prev_j["title"])}</h3></a>' if prev_j else "<span></span>")
        pager += (f'<a class="tile tile-grey next" href="{next_j["slug"]}.html"><span class="dir">Nächste Stelle →</span><h3>{E(next_j["title"])}</h3></a>' if next_j else "<span></span>")
        pager += "</nav>"
        return (head(p, j["title"], "", [("Karriere", "jobs.html"), ("Stelle", None)])
                + f'<div class="detail"><div class="prose prose-card tile" data-light>{fix_html(j["html"], p)}</div>'
                + f'<div class="facts"><div class="tile tile-blue" data-light><span class="eyebrow">Bewerbung</span><h3>Fahrni Fassadensysteme AG</h3><p>Bernstrasse 84, 3250 Lyss<br><a href="{TEL}">Tel. 032 387 25 25</a></p></div>'
                + f'<a class="tile tile-dark" href="{p}jobs.html"><div class="tile-top"><span class="eyebrow">Weitere Stellen</span>{arrow()}</div><h3>Alle offenen Stellen</h3></a></div></div>' + pager)
    page(f"jobs/{j['slug']}.html", f"{j['title']} · Karriere · FAHRNI", job_page, "jobs", j["title"])


# ---------------------------------------------------------------- Social Media
def words(text):
    return " ".join(f'<span class="w" style="--i:{k}">{E(w)}</span>' for k, w in enumerate(text.split()))

def social(p):
    posts = S["posts"]; n = len(posts)
    slides = []
    for k, post in enumerate(posts):
        ch = CH[post["channel"]]; src, poster = post_video(post, p)
        orig = (f'<a class="btn btn-ghost" href="{E(post["url"])}" target="_blank" rel="noopener">Auf {E(ch["name"])} ansehen {ic("out")}</a>' if post["url"]
                else f'<span class="sl-todo">Link zum Originalbeitrag auf {E(ch["name"])} ergänzen</span>')
        slides.append(
            f'<article class="sl-slide" id="post-{post["id"]}" role="group" aria-roledescription="Beitrag" aria-label="{k+1} von {n}: {E(post["title"])}" data-i="{k}">'
            f'<div class="sl-phone tile" data-light><video muted playsinline preload="metadata" poster="{poster}" aria-label="Video zum Beitrag {E(post["title"])}"><source src="{src}" type="video/mp4"></video>'
            f'<div class="sl-bar" aria-hidden="true"><b></b></div><span class="sl-chan">{E(ch["name"])}</span></div>'
            f'<div class="sl-copy"><p class="eyebrow"><b>{E(ch["name"])}</b> · {E(post["date"])}</p>'
            f'<h2 class="sl-title">{E(post["title"])}</h2><p class="sl-text">{words(post["text"])}</p>'
            f'<ul class="sl-tags">{"".join(f"<li>#{E(t)}</li>" for t in post["tags"])}</ul>'
            f'<div class="sl-actions"><a class="btn btn-primary" href="{p}{post["page"]}">Mehr dazu {ic()}</a>{orig}</div></div></article>')
    dots = "".join(f'<button class="sl-dot" type="button" data-go="{k}" aria-label="Beitrag {k+1}: {E(post["title"])}"><i></i></button>' for k, post in enumerate(posts))
    thumbs = "".join(f'<button class="sl-thumb tile" type="button" data-go="{k}" aria-label="Beitrag {k+1} zeigen: {E(post["title"])}">'
                     f'<div class="media"><img src="{post_video(post, p)[1]}" alt="" loading="lazy"></div>'
                     f'<span class="sl-thumb-ch">{E(CH[post["channel"]]["name"])}</span><span class="sl-thumb-t">{E(post["title"])}</span></button>' for k, post in enumerate(posts))
    chans = "".join(
        f'<{"a" if c["url"] else "div"} class="tile tile-dark span-4 reveal" style="--d:{k*.06}s"' + (f' href="{E(c["url"])}" target="_blank" rel="noopener"' if c["url"] else " data-light") + '>'
        f'<div class="tile-top"><span class="eyebrow"><b>{E(c["name"])}</b></span>{arrow("out") if c["url"] else ""}</div>'
        f'<div><h3>{E(c["handle"])}</h3><p>' + ("Folgen" if c["url"] else '<span class="sl-todo">Link zum Kanal ergänzen</span>') + f'</p></div></{"a" if c["url"] else "div"}>'
        for k, c in enumerate(S["channels"]))
    return (head(p, "Social Media", "Neue Aufträge, Referenzen und Einblicke in Werkstatt und Montage, direkt aus unseren Kanälen.", [("Social Media", None)])
            + f'<section class="sl" id="slider" aria-roledescription="Karussell" aria-label="Social-Media-Beiträge" tabindex="-1">'
            + f'<div class="sl-track" id="sl-track">{"".join(slides)}</div>'
            + f'<div class="sl-controls"><button class="sl-btn" type="button" id="sl-prev" aria-label="Vorheriger Beitrag">{ic("back")}</button>'
            + f'<button class="sl-btn" type="button" id="sl-play" aria-label="Automatisches Weiterschalten pausieren">{ic("pause")}</button>'
            + f'<div class="sl-dots">{dots}</div><span class="sl-count" id="sl-count" aria-live="polite">01 / {n:02d}</span>'
            + f'<button class="sl-btn" type="button" id="sl-next" aria-label="Nächster Beitrag">{ic()}</button></div></section>'
            + f'<section aria-labelledby="all-title"><div class="section-head reveal"><h2 id="all-title">Alle Beiträge.</h2></div><div class="sl-thumbs">{thumbs}</div></section>'
            + f'<section aria-labelledby="ch-title"><div class="section-head reveal"><h2 id="ch-title">Folgen Sie uns.</h2><p>{E(S["channels"][0]["name"])}, {E(S["channels"][1]["name"])} und {E(S["channels"][2]["name"])}.</p></div><div class="grid">{chans}</div></section>'
            + '<p class="sl-note">Hinweis zum Entwurf: Die Beiträge sind Beispiele aus vorhandenen Inhalten von fahrni.com und werden durch echte Posts ersetzt.</p>')
page("social.html", "Social Media · FAHRNI", social, "social", "Beiträge der Fahrni Fassadensysteme AG aus LinkedIn, Instagram und YouTube.")

# ---------------------------------------------------------------- Kontakt
def kontakt(p):
    pic = img(teaser_img["/de/kontakt/"], p)
    return (head(p, "Kontakt", lead(C["kontakt"]["lead"]), [("Kontakt", None)])
            + f'<div class="grid" style="margin-bottom:var(--gap)"><a class="tile span-8 tall reveal" href="{TEL}" style="justify-content:flex-end"><div class="media"><img src="{pic}" alt="" loading="lazy"></div>'
            + f'<div style="display:grid;gap:10px"><span class="eyebrow"><b>Hauptsitz</b> · Lyss</span><span style="font-family:var(--head);font-size:clamp(40px,5vw,76px);line-height:1">{PHONE}</span><p>Fahrni Fassadensysteme AG, Bernstrasse 84, CH-3250 Lyss</p></div></a>'
            + f'<div class="tile tile-blue span-4 tall reveal" style="--d:.08s" data-light><span class="eyebrow">E-Mail</span><div><h3>Schreiben Sie uns</h3><p><span class="email-todo">[E-Mail-Adresse ergänzen]</span></p></div></div></div>'
            + f'<div class="grid">{sites(p)}</div>')
page("kontakt.html", "Kontakt · FAHRNI", kontakt, "kontakt", lead(C["kontakt"]["lead"]))

# ---------------------------------------------------------------- Assets
fonts = {k: "data:font/otf;base64," + base64.b64encode((HERE / "assets/fonts" / f).read_bytes()).decode()
         for k, f in (("@@FONT_HEAD@@", "FrutigerNext-Condensed-Bold.otf"), ("@@FONT_BODY@@", "Frutiger-45-Light.otf"))}
css = (HERE / "src/site.css").read_text(encoding="utf-8")
for k, v in fonts.items(): css = css.replace(k, v)
# Ein Stylesheet: Grundgestaltung + helles Farbschema
(HERE / "assets/site.css").write_text(css + "\n" + (HERE / "src/theme-hell.css").read_text(encoding="utf-8"), encoding="utf-8")
(HERE / "assets/site.js").write_text((HERE / "src/site.js").read_text(encoding="utf-8"), encoding="utf-8")
n = len(list(OUT.glob("*.html"))) + len(list(OUT.glob("*/*.html")))
print(f"Variante {VARIANTS[VARIANT][1]}: {n} Seiten erzeugt")

