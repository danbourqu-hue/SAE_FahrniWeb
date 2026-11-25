# FAHRNI WordPress-Theme

Theme im neuen, hellen FAHRNI Corporate Design, gebaut aus dem Website-Entwurf (`website/`). Gleiche Gestaltung, gleiche Animationen; die Inhalte pflegt die Redaktion im WordPress-Admin über Formulare.

## Installation (einmalig, ca. 30 Minuten)

Voraussetzung: WordPress 6.4 oder neuer, PHP 8.1 oder neuer.

1. **Theme hochladen:** Design → Themes → Theme hinzufügen → Theme hochladen → `fahrni-theme.zip` → Installieren → Aktivieren.
2. **Inhaltspaket hochladen:** `fahrni-inhalte.zip` entpacken und den Ordner per FTP/SFTP nach `wp-content/uploads/fahrni-import/` kopieren. Darin liegen `content/` und `assets/`.
3. **Import starten:** Werkzeuge → FAHRNI-Import → «Import starten». Übernimmt Standorte, Leistungen, 115 Referenzen mit Bildergalerien, News, Stellen, 26 Presseberichte (PDF), Social-Beiträge (Video), die Geschichte und alle Seiten. Dauer je nach Server 5 bis 15 Minuten. Mehrfach ausführbar, ohne Doppel.
4. **Permalinks bestätigen:** Einstellungen → Permalinks → einmal «Änderungen speichern».
5. **Angaben ergänzen:** Design → Anpassen → FAHRNI: E-Mail-Adresse, Social-Media-Adressen, Navigation. Danach den Ordner `fahrni-import` wieder löschen.

## Wo was gepflegt wird

| Inhalt | Admin-Menü | Wichtige Felder |
| --- | --- | --- |
| News (neue Aufträge) | News | Titel, Textauszug (Kurztext), Text, Beitragsbild, Ort |
| Referenzen | Referenzen | Titel, Text (Leistungen), Ort, Bauherr, Architekt, Generalunternehmer, Bildergalerie, Jahr (rechts) |
| Stellen | Stellen | Titel, Text, Arbeitsort, Pensum, **Ablaufdatum**, Kontakt |
| Presse | Presse | Titel, PDF-Datei, Jahr |
| Social Media | Social Media | Titel, Text, Kanal, Video (MP4 Hochformat), Beitragsbild = Vorschau, Hashtags, Links |
| Geschichte | Geschichte | Titel = Thema, Jahr, Ereignisse (eine Zeile je Ereignis), Text, Bild, verknüpfte Leistungen und Standorte, Referenzen nach Ort |
| Leistungen | Leistungen | Titel, Text, Beitragsbild, Reihenfolge, Sprungmarke |
| Standorte | Standorte | Ort, Bezeichnung, Adresse, Telefon, E-Mail, Hauptsitz |
| Texte der Startseite, Telefon, Adresse, Social-Adressen | Design → Anpassen → FAHRNI | |
| Bilder und Kurztexte im grossen Menü | Design → Anpassen → FAHRNI: Menü | |
| Menüpunkte (optional) | Design → Menüs → Position «Hauptnavigation» | ohne Menü gelten die Standardpunkte |

## Veraltete Inhalte

- **Archivieren statt löschen:** Status «Archiviert» wählen (im Bearbeiten-Fenster oder in der Liste per Schnellbearbeitung). Der Inhalt ist offline, bleibt aber im System und lässt sich wieder veröffentlichen.
- **Stellen** gehen am Tag nach ihrem Ablaufdatum automatisch ins Archiv.
- **News** gehen nach 2 Jahren automatisch ins Archiv (einstellbar unter Einstellungen → Lesen, 0 = nie).
- Wer eine archivierte oder alte Adresse aufruft (auch alte fahrni.com-Adressen wie `/de/referenzen/…`), wird automatisch auf die passende Seite weitergeleitet.

## Seitenvorlagen

| Seite | Vorlage (Seite bearbeiten → Vorlage) |
| --- | --- |
| Leistungen | Leistungen |
| Unternehmen (mit Zeitachse) | Unternehmen |
| Kontakt | Kontakt |
| Social Media | Social Media |
| Prinzipien, Datenschutz, Beschaffungsbedingungen | Standard |

Startseite und News sind unter Einstellungen → Lesen zugewiesen (macht der Import).

## Empfohlene Erweiterungen (kostenlos)

- **Yoast SEO** oder **Rank Math**: Seitentitel, Beschreibungen, Sitemap.
- **UpdraftPlus**: Backups, falls der Hoster keine täglichen Backups macht.
- **Two Factor**: Zwei-Faktor-Anmeldung.
- Mehrsprachigkeit bei Bedarf: **Polylang**.

Das Theme selbst braucht keine Erweiterung.

## Aufbau des Themes

| Datei | Inhalt |
| --- | --- |
| `fahrni/functions.php` | lädt die Bausteine aus `inc/` |
| `fahrni/inc/setup.php` | Assets, Customizer (Navigation, Kontakt, Texte, Menübilder) |
| `fahrni/inc/content-types.php` | Inhaltstypen, Status «Archiviert» |
| `fahrni/inc/fields.php` | Formularfelder inkl. Bildergalerie, Datei-Auswahl |
| `fahrni/inc/lifecycle.php` | automatisches Archivieren, Weiterleitungen |
| `fahrni/inc/template-tags.php` | Bausteine der Seiten |
| `fahrni/inc/import.php` | Import aus dem Website-Entwurf |
| `fahrni/*.php`, `fahrni/page-templates/` | Seitenvorlagen |
| `fahrni/assets/` | Stylesheet (mit eingebetteten Schriften), Skripte, Logos |

Gestaltung ändern: im Entwurf `website/src/` anpassen, `python3 website/build.py` ausführen und `website/assets/site.css` sowie `site.js` nach `fahrni/assets/` kopieren (oder `python3 wordpress/package.py`, siehe unten).

## Pakete erstellen

```bash
python3 wordpress/package.py
```

Erzeugt `fahrni-theme.zip` (Theme) und `fahrni-inhalte.zip` (Inhaltspaket für den Import).
