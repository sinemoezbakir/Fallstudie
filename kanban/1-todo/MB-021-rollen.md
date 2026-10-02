# MB-021 · Rollen Finance / Owner / Admin

| Feld | Wert |
|---|---|
| Epic | 2 – Login & Rollen |
| Rolle | alle |
| Priorität | Muss |
| Zuständig | – (Vorschlag: Sinem) |
| Fällig | So 11.10.2026 (Phase 1) |
| Abhängig von | MB-020 |
| Aufwand geschätzt | S (≈ 2 h) |
| Aufwand tatsächlich | – |
| Gestartet / Fertig | – / – |
| Reviewt von | – |

## Ziel
Die drei Rollen sind fest im Code hinterlegt und begrenzen, welche Bereiche erreichbar sind.

## Aufgaben
- [ ] `enum Rolle { ADMIN, FINANCE, OWNER }`
- [ ] Zugriffsregeln: `/admin/**` nur Admin, `/finance/**` nur Finance, `/owner/**` nur Owner

## Akzeptanzkriterien
- [ ] Owner ruft `/finance` auf → 403-Seite

## Umsetzung

_Beim Arbeiten ausfüllen: Was wurde gebaut? Welche Dateien? Warum so und nicht anders?_

## Probleme & Lösungen

_Was hat nicht geklappt, wie wurde es gelöst? (Gold wert für die Doku und die Präsentation)_

## Screenshots

_Bei Oberflächen-Karten: `docs/screenshots/MB-021-<name>.png` ablegen und hier verlinken._

## Verlauf

| Datum | Wer | Was |
|---|---|---|
| 2026-10-02 | Claude (für Sinem) | Karte angelegt in `1-todo` |
