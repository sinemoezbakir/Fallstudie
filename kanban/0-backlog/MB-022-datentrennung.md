# MB-022 · Datentrennung: Owner sieht nur eigene Budgets

| Feld | Wert |
|---|---|
| Epic | 2 – Login & Rollen |
| Rolle | Owner |
| Priorität | Muss |
| Zuständig | – (Vorschlag: A) |
| Abhängig von | MB-021, MB-031 |
| Aufwand geschätzt | S (≈ 3 h) |
| Aufwand tatsächlich | – |
| Gestartet / Fertig | – / – |
| Reviewt von | – |

## Ziel
User A hat keinen Zugriff auf die Daten von User B – auch nicht, wenn man die ID in der URL ändert.

## Aufgaben
- [ ] Eine zentrale Methode `ladeEigenesBudget(id, benutzer)`, die bei fremdem Budget einen 403-Fehler wirft
- [ ] Diese Methode in **allen** Owner-Seiten und -Formularen verwenden (Anzeigen, Buchen, Korrigieren, Forecast)

## Akzeptanzkriterien
- [ ] `cafe` öffnet `/owner/budgets/<ID eines Studio-Budgets>` → 403
- [ ] Test dazu in MB-050

## Umsetzung

_Beim Arbeiten ausfüllen: Was wurde gebaut? Welche Dateien? Warum so und nicht anders?_

## Probleme & Lösungen

_Was hat nicht geklappt, wie wurde es gelöst? (Gold wert für die Doku und die Präsentation)_

## Screenshots

_Bei Oberflächen-Karten: `docs/screenshots/MB-022-<name>.png` ablegen und hier verlinken._

## Verlauf

| Datum | Wer | Was |
|---|---|---|
| 2026-10-02 | Claude (für Sinem) | Karte angelegt in `0-backlog` |
