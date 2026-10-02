# MB-033 · Kennzahlen und Status berechnen

| Feld | Wert |
|---|---|
| Epic | 3 – Grundsystem |
| Rolle | alle |
| Priorität | Muss |
| Zuständig | – (Vorschlag: A) |
| Abhängig von | MB-004 |
| Aufwand geschätzt | M (≈ 4 h) |
| Aufwand tatsächlich | – |
| Gestartet / Fertig | – / – |
| Reviewt von | – |

## Ziel
Soll, Ist, Verfügbar, Forecast und Status werden an **einer** Stelle berechnet (`docs/konzept.md`, Abschnitt 4).

## Aufgaben
- [ ] Klasse `BudgetKennzahlen` mit allen Werten aus dem Konzept
- [ ] `KennzahlenService.berechne(budget, heute)` – Datum als Parameter, damit testbar
- [ ] Schwelle 80 % als Konstante
- [ ] Unit-Tests: je Status ein Test + Randfälle (genau 80 %, genau 100 %, keine Ausgaben)

## Akzeptanzkriterien
- [ ] Keine Seite rechnet selbst, alle nutzen den Service
- [ ] Tests grün

## Umsetzung

_Beim Arbeiten ausfüllen: Was wurde gebaut? Welche Dateien? Warum so und nicht anders?_

## Probleme & Lösungen

_Was hat nicht geklappt, wie wurde es gelöst? (Gold wert für die Doku und die Präsentation)_

## Screenshots

_Bei Oberflächen-Karten: `docs/screenshots/MB-033-<name>.png` ablegen und hier verlinken._

## Verlauf

| Datum | Wer | Was |
|---|---|---|
| 2026-10-02 | Claude (für Sinem) | Karte angelegt in `0-backlog` |
