import re, sys, time, json, pathlib, urllib.parse, urllib.request
from html.parser import HTMLParser

OUT = pathlib.Path(sys.argv[1]); OUT.mkdir(parents=True, exist_ok=True)
START = "https://www.fahrni.com/de/"
UA = {"User-Agent": "Mozilla/5.0 (site-redesign content inventory)"}

class Links(HTMLParser):
    def __init__(s): super().__init__(); s.links = []
    def handle_starttag(s, tag, a):
        a = dict(a)
        if tag == "a" and a.get("href"): s.links.append(a["href"])

seen, queue, pages = set(), [START], {}
while queue and len(pages) < 250:
    url = queue.pop(0)
    if url in seen: continue
    seen.add(url)
    try:
        req = urllib.request.Request(url, headers=UA)
        with urllib.request.urlopen(req, timeout=20) as r:
            if "text/html" not in r.headers.get("Content-Type", ""): continue
            html = r.read().decode("utf-8", "replace")
    except Exception as e:
        print("FAIL", url, e); continue
    name = re.sub(r"[^a-z0-9]+", "_", urllib.parse.urlparse(url).path.lower()).strip("_") or "root"
    (OUT / f"{name}.html").write_text(html)
    pages[url] = name
    p = Links(); p.feed(html)
    for h in p.links:
        u = urllib.parse.urljoin(url, h).split("#")[0]
        pu = urllib.parse.urlparse(u)
        if pu.netloc == "www.fahrni.com" and pu.path.startswith("/de/") and not re.search(r"\.(pdf|jpe?g|png|zip|docx?)$", pu.path, re.I) and u not in seen:
            queue.append(u)
    time.sleep(0.3)
(OUT / "pages.json").write_text(json.dumps(pages, indent=1))
print(len(pages), "pages")
