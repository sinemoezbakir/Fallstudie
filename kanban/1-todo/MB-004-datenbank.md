# MB-004 · XAMPP-Datenbank: Tabellen und Verbindung

| Feld | Wert |
|---|---|
| Epic | 0 – Setup & Organisation |
| Rolle | – |
| Priorität | Muss |
| Zuständig | – (Vorschlag: Sinem) |
| Fällig | So 11.10.2026 (Phase 1) |
| Abhängig von | MB-002, MB-003 |
| Aufwand geschätzt | M (≈ 4 h) |
| Aufwand tatsächlich | – |
| Gestartet / Fertig | – / – |
| Reviewt von | – |

## Ziel
Die Tabellen aus dem Konzept existieren in MariaDB (XAMPP) und die Anwendung kann sie lesen und schreiben.

## Aufgaben
- [ ] `database/schema.sql`: Datenbank `malia_budget`, Tabellen `benutzer`, `budget`, `ist_buchung`, `forecast`
- [ ] Einspielen über phpMyAdmin → „Importieren“ (Schritt im README notieren)
- [ ] `application.properties`: XAMPP-Standard (`root`, leeres Passwort), `ddl-auto=validate`
- [ ] Java-Klassen (Entities) + Enums `Rolle`, `Bereich` + Repositories
- [ ] Beträge als `DECIMAL(10,2)` / `BigDecimal`

## Akzeptanzkriterien
- [ ] App startet mit laufendem XAMPP-MySQL ohne Fehler
- [ ] Screenshot der Tabellen in phpMyAdmin für die Doku

## Umsetzung

_Beim Arbeiten ausfüllen: Was wurde gebaut? Welche Dateien? Warum so und nicht anders?_

## Probleme & Lösungen

_Was hat nicht geklappt, wie wurde es gelöst? (Gold wert für die Doku und die Präsentation)_

## Screenshots

_Bei Oberflächen-Karten: `docs/screenshots/MB-004-<name>.png` ablegen und hier verlinken._

## Verlauf

| Datum | Wer | Was |
|---|---|---|
| 2026-10-02 | Claude (für Sinem) | Karte angelegt in `1-todo` |
