# FAHRNI Website-Entwurf

Mehrseitiger Entwurf im neuen, hellen FAHRNI Corporate Design mit dem gesamten deutschen Inhalt von fahrni.com (Stand November 2026).

## Anschauen

`index.html` öffnen. Die Kopfleiste zeigt das Menüband und zusätzlich den Button «Menü», der das grosse Menü mit Bildern öffnet. Auf dem Handy bleibt nur der Button.

Für die Handy-Ansicht `handy-vorschau.html` öffnen: Die Website läuft dort in einem Handy- oder Tablet-Rahmen.

Per Doppelklick im Browser öffnen, es braucht keinen Webserver. Schriften sind in `assets/site.css` eingebettet, Bilder, Videos und Presse-PDFs liegen lokal in `assets/`. Alle Links bleiben auf der Website. Presse-PDFs öffnen sich auf einer eigenen Seite im Website-Rahmen mit «Zurück»-Button. Am Ende jeder Unterseite führen feste Wege zu Startseite, Karriere, Referenzen und Kontakt. 

## Aufbau

| Pfad | Inhalt |
| --- | --- |
| `*.html` (Hauptordner) | Startseite, Leistungen, Referenzen, Unternehmen, Prinzipien, Presse, News, Karriere (`jobs.html`), Social, Kontakt, Datenschutz, Beschaffungsbedingungen |
| `referenzen/`, `news/`, `jobs/`, `presse/` | Detailseiten: 115 Referenzen, 9 News, 4 Stellen, 26 Presseberichte |
| `handy-vorschau.html` | Vorschau in Handy- und Tablet-Grösse |
| `src/site.css`, `src/theme-hell.css`, `src/site.js` | Gestaltung und Interaktion (Quelle) |
| `content/content.json` | Inhalt von fahrni.com, strukturiert |
| `content/geschichte.json` | Zuordnung der Inhalte zu den Meilensteinen der Geschichte |
| `content/social.json` | Social-Media-Beiträge und Kanäle |
| `content/images.json` | Zuordnung Original-Bild → lokale Datei |
| `content/crawl.py`, `extract.py`, `images.py`, `presse_pdfs.py`, `make_social_videos.py` | Skripte für Abruf, Bilder, PDFs und Videos |
| `build.py` | Erzeugt alle Seiten neu |

## Ändern

Texte in `content/*.json` oder Gestaltung in `src/` anpassen, dann:

```bash
python3 build.py
```

Die Lichtstärke des Leuchtkegels lässt sich oben in `src/site.css` über `--light-face` und `--light-halo` einstellen, die Randfarbe beim Hovern über `--edge-hover`.

## Geschichte

Die 14 Meilensteine haben Jahr und Text 1:1 von fahrni.com. «Mehr erfahren» öffnet pro Meilenstein eine Detailansicht mit passenden Inhalten anderer Seiten (Leistungen, Prinzipien, Standorte, Referenzen nach Ort, Presse nach Erscheinungsjahr). Die Zuordnung steht in `content/geschichte.json`. Bei 7 Presseberichten ist das Jahr das Upload-Jahr auf fahrni.com, nicht das Erscheinungsjahr.

Die Zeitachse läuft beim Scrollen von links nach rechts. Pfeil-Buttons und die Pfeiltasten springen von Meilenstein zu Meilenstein, ein Hinweis zeigt beim ersten Besuch, wie es geht.

## Social Media

Beiträge stehen in `content/social.json`: Kanal, Titel, Text, Hashtags, Video, Link zur passenden Seite und `url` zum Originalbeitrag. Ohne eigenes Video erzeugt `python3 content/make_social_videos.py` aus den angegebenen Bildern einen Clip im Reel-Format. Danach `python3 build.py`.

## Offen

- Social Media: Die sechs Beiträge sind Beispiele aus vorhandenen Inhalten. Links zu den Kanälen und Originalbeiträgen fehlen noch (gelb markiert).
- E-Mail-Adressen sind auf fahrni.com durch einen Spamschutz verborgen und fehlen hier. Sie sind gelb als «[E-Mail-Adresse ergänzen]» markiert.
- Französische Fassung von fahrni.com ist nicht übernommen.
- Frutiger-Schriften brauchen für eine öffentliche Website eine Web-Lizenz.
