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
| `build.py` | Erzeugt alle Seiten neu |

## Ändern

Texte in `content/content.json` oder Gestaltung in `src/` anpassen, dann:

```bash
python3 build.py
```

Die Lichtstärke des Leuchtkegels lässt sich oben in `src/site.css` über `--light-face`, `--light-edge` und `--light-halo` einstellen.

## Offen

- E-Mail-Adressen sind auf fahrni.com durch einen Spamschutz verborgen und fehlen hier. Sie sind gelb als «[E-Mail-Adresse ergänzen]» markiert.
- Frutiger-Schriften brauchen für eine öffentliche Website eine Web-Lizenz.
