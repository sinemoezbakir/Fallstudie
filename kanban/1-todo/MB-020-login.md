# MB-020 · Login mit Username und Passwort

| Feld | Wert |
|---|---|
| Epic | 2 – Login & Rollen |
| Rolle | alle |
| Priorität | Muss |
| Zuständig | – (Vorschlag: Sinem) |
| Fällig | So 11.10.2026 (Phase 1) |
| Abhängig von | MB-004, MB-010 |
| Aufwand geschätzt | M (≈ 4 h) |
| Aufwand tatsächlich | – |
| Gestartet / Fertig | – / – |
| Reviewt von | – |

## Ziel
Nur angemeldete Benutzer kommen in die Anwendung.

## Aufgaben
- [ ] Spring Security: eigene Login-Seite `/login` im Maliá-Design, Abmelden
- [ ] Benutzer aus der Datenbank laden, Passwörter mit BCrypt prüfen
- [ ] Nach dem Login je Rolle weiterleiten: Admin → `/admin`, Finance → `/finance`, Owner → `/owner`

## Akzeptanzkriterien
- [ ] Falsches Passwort → freundliche Meldung
- [ ] Ohne Anmeldung ist keine Seite außer `/login` erreichbar

## Umsetzung

_Beim Arbeiten ausfüllen: Was wurde gebaut? Welche Dateien? Warum so und nicht anders?_

## Probleme & Lösungen

_Was hat nicht geklappt, wie wurde es gelöst? (Gold wert für die Doku und die Präsentation)_

## Screenshots

_Bei Oberflächen-Karten: `docs/screenshots/MB-020-<name>.png` ablegen und hier verlinken._

## Verlauf

| Datum | Wer | Was |
|---|---|---|
| 2026-10-02 | Claude (für Sinem) | Karte angelegt in `1-todo` |
