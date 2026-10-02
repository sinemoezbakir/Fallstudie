# Kanban-Board

Das Board ist gleichzeitig unsere **Dokumentation des Werdegangs**. Jede Teilaufgabe ist eine
Markdown-Datei (**Karte**), der **Ordner** zeigt den Status. Verschoben wird mit `git mv`. So
steht jeder Statuswechsel mit Person und Datum in der Git-Historie und ist auf GitHub nachvollziehbar.

| Ordner | Bedeutung |
|---|---|
| `0-backlog/` | gesammelt, noch nicht eingeplant (inkl. eigener Ideen) |
| `1-todo/` | für die aktuelle Phase eingeplant, kann gestartet werden |
| `2-in-arbeit/` | jemand arbeitet gerade daran (**max. 2 Karten pro Person**) |
| `3-review/` | fertig, wartet auf Review der **anderen** Person |
| `4-fertig/` | reviewt und in `main` |

Übersicht im Terminal: `powershell -File kanban/board.ps1` · Neue Karte: [`_vorlage.md`](_vorlage.md) kopieren.

## Team

| Kürzel | Person | Schwerpunkt (Vorschlag) |
|---|---|---|
| **A** | Sinem | Datenbank, Login/Rollen, Berechnung, Forecast |
| **B** | _bitte eintragen_ | Design, Layout, Owner-Dashboard, Diagramme |

Beide machen Oberfläche **und** Java-Logik, damit beide alles erklären können.

## Was wird wann dokumentiert?

| Zeitpunkt | Was | Wo |
|---|---|---|
| Karte starten | „Zuständig“ und „Gestartet“ ausfüllen, Verlauf-Zeile, Datei nach `2-in-arbeit/` | Karte |
| Während der Arbeit | Stichpunkte unter **Umsetzung** und **Probleme & Lösungen** | Karte |
| Oberfläche fertig | Screenshot nach `docs/screenshots/MB-xxx-name.png`, in der Karte verlinken | Karte |
| Karte fertig | „Aufwand tatsächlich“, Verlauf-Zeile, Datei nach `3-review/` | Karte |
| Review | „Reviewt von“, Ergebnis in den Verlauf, Datei nach `4-fertig/` | Karte |
| Ende jeder Sitzung | eine Zeile pro Person | [Arbeitsprotokoll](../docs/arbeitsprotokoll.md) |
| Ende jeder Woche | kurzer Rückblick: Was lief gut, was nicht, was ändern wir? | [Arbeitsprotokoll](../docs/arbeitsprotokoll.md) |
| Wichtige Entscheidung | neuer Eintrag | [Entscheidungslog](../docs/entscheidungen.md) |

Lieber kurze Stichpunkte sofort als lange Texte am Ende. Aus diesen Notizen entsteht später
die Projektdokumentation (MB-054).

## Definition of Done

Eine Karte darf nach `4-fertig/`, wenn:

- [ ] alle Akzeptanzkriterien erfüllt sind und es im Browser funktioniert
- [ ] die andere Person reviewt hat (Name in „Reviewt von“)
- [ ] „Umsetzung“, „Aufwand tatsächlich“ und Verlauf ausgefüllt sind
- [ ] bei Oberflächen: Maliá-Design, am Handy brauchbar, Screenshot vorhanden
- [ ] Commits tragen die Karten-ID

## Phasenplan

| Phase | Ziel | Karten A | Karten B |
|---|---|---|---|
| 1 – Fundament | Projekt läuft, Datenbank steht, Login klappt, Design steht | MB-003, 004, 005, 020, 021 | MB-010, 011, 012 |
| 2 – Grundsystem | alle Pflichtfunktionen aus Aufgabe II | MB-022, 030, 031, 033 | MB-032, 034, 035 |
| 3 – Owner-Sicht | Ausprägung komplett | MB-042, 044, 045 | MB-040, 041, 043 |
| 4 – Abschluss | Tests, Feinschliff, Doku, Präsentation | MB-050, 053 | MB-051, 052 |
| gemeinsam | | MB-001, 002, 054, 055 | |
| optional | eigene Ideen, wenn Zeit bleibt | MB-06x | |

Aufwand: **S** ≈ bis 3 h · **M** ≈ 4–5 h · **L** ≈ 1–2 Tage
