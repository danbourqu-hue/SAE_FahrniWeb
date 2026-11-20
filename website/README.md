# FAHRNI Website-Entwurf

Mehrseitiger Entwurf im neuen FAHRNI Corporate Design mit dem Inhalt von fahrni.com (Stand November 2026).

## Anschauen

`index.html` per Doppelklick im Browser öffnen. Es braucht keinen Webserver: Schriften sind in `assets/site.css` eingebettet, alle Bilder liegen lokal in `assets/fotos/`. Alle internen Links bleiben im neuen Design. Nur die Presse-PDFs öffnen sich von fahrni.com.

## Aufbau

| Pfad | Inhalt |
| --- | --- |
| `index.html`, `leistungen.html`, `referenzen.html`, `unternehmen.html`, `prinzipien.html`, `presse.html`, `news.html`, `jobs.html`, `kontakt.html`, `datenschutz.html`, `beschaffungsbedingungen.html` | Hauptseiten |
| `referenzen/`, `news/`, `jobs/` | Detailseiten (115 Referenzen, 9 News, 4 Stellen) |
| `src/site.css`, `src/site.js` | Gestaltung und Interaktion (Quelle) |
| `content/content.json` | Inhalt von fahrni.com, strukturiert |
| `content/images.json` | Zuordnung Original-Bild → lokale Datei |
| `content/crawl.py`, `extract.py`, `images.py` | Skripte, mit denen der Inhalt geholt wurde |
| `content/social.json` | Social-Media-Beiträge und Kanäle |
| `content/make_social_videos.py` | Erzeugt die Hochformat-Videos der Beispielbeiträge |
| `hell/` | Helle Variante (wird mit erzeugt) |
| `build.py` | Erzeugt alle Seiten neu (dunkel und hell) |

## Ändern

Texte in `content/content.json` oder Gestaltung in `src/` anpassen, dann:

```bash
python3 build.py
```

Die Lichtstärke des Leuchtkegels lässt sich oben in `src/site.css` über `--light-face` und `--light-halo` einstellen, die Randfarbe beim Hovern über `--edge-hover`.

## Social Media

Beiträge stehen in `content/social.json`: Kanal, Titel, Text, Hashtags, Video, Link zur passenden Seite und `url` zum Originalbeitrag.
Ein Beitrag mit eigenem Video bekommt `"video": "video/social/<datei>.mp4"` und `"poster": "…jpg"`; ohne Video erzeugt
`python3 content/make_social_videos.py` aus den angegebenen Bildern einen Clip im Reel-Format. Danach `python3 build.py`.

## Offen

- Social Media: Die sechs Beiträge sind Beispiele aus vorhandenen Inhalten. Links zu den Kanälen und Originalbeiträgen fehlen noch (gelb markiert).

- E-Mail-Adressen sind auf fahrni.com durch einen Spamschutz verborgen und fehlen hier. Sie sind gelb als «[E-Mail-Adresse ergänzen]» markiert.
- Frutiger-Schriften brauchen für eine öffentliche Website eine Web-Lizenz.
