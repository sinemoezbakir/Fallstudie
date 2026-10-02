# Kanban-Board

Das Board ist gleichzeitig unsere **Dokumentation des Werdegangs**. Jede Teilaufgabe ist eine
Markdown-Datei (**Karte**), der **Ordner** zeigt den Status. Verschoben wird mit `git mv`. So
steht jeder Statuswechsel mit Person und Datum in der Git-Historie und ist auf GitHub nachvollziehbar.

| Ordner | Bedeutung |
|---|---|
| `0-backlog/` | gesammelt, noch nicht eingeplant (inkl. eigener Ideen) |
| `1-todo/` | für die aktuelle Phase eingeplant, kann gestartet werden |
| `2-in-arbeit/` | jemand arbeitet gerade daran (**max. 2 Karten pro Person**) |
| `3-review/` | fertig, wartet auf Review |
| `4-fertig/` | reviewt und in `main` |

Übersicht im Terminal: `powershell -File kanban/board.ps1` · Neue Karte: [`_vorlage.md`](_vorlage.md) kopieren.

## Team

| Person | Rolle | Verantwortlich für |
|---|---|---|
| **Ardita** | Projektleitung & Dokumentation | Projektplan, Meetings & Protokolle, Fortschritt & Risiken, Sprechstunden, Zwischenbericht, Präsentation, Ergebnisdokumentation (Epic 7) |
| **Sinem** | Entwicklung | Datenbank, Login/Rollen, Berechnung, Forecast, Tests |
| **Natalia** | Entwicklung | Design, Layout, Owner-Dashboard, Diagramme, Feinschliff |

Sinem und Natalia machen beide Oberfläche **und** Java-Logik und reviewen sich gegenseitig.
Ardita reviewt die Doku-Karten (Zuarbeit MB-054) und lässt ihre Abgaben von beiden gegenlesen.

## Termine

Abgaben immer bis **23:59 UTC** in Moodle (= 00:59 Uhr deutscher Winterzeit bzw. 01:59 Uhr Sommerzeit am Folgetag – nicht darauf verlassen, lieber am Vortag abgeben).

| Datum | Termin | Karte |
|---|---|---|
| So 04.10.2026 | **Abgabe Projektplan** | MB-070 |
| Mo 05.10.2026 | Projektarbeitszeit (selbstständig) | – |
| Fr 23.10.2026 | Sprechstunde: Stand vorstellen & Fragen | MB-073 |
| So 25.10.2026 | **Abgabe Zwischenbericht** | MB-074 |
| Mo 26.10.2026 | Projektarbeitszeit (selbstständig) | – |
| Fr 06.11.2026 | **Präsentation + Abgabe Präsentation** | MB-075 |
| So 22.11.2026 | **Abgabe Ergebnisdokumentation** | MB-076 |

## Zeitplan und Meilensteine

Ardita hält die Spalte **Status** aktuell (MB-072).

| Phase | Zeitraum | Meilenstein (Ende der Phase) | Sinem | Natalia | Ardita | Status |
|---|---|---|---|---|---|---|
| 0 – Planung | bis So 04.10. | Projektplan abgegeben | MB-001, 002 | MB-001, 002 | MB-070, 071 | in Arbeit |
| 1 – Fundament | 05.10. – 11.10. | App läuft, DB steht, Login klappt, Design steht | MB-003, 004, 005, 020, 021 | MB-010, 011, 012 | MB-071, 072 | offen |
| 2 – Grundsystem | 12.10. – 22.10. | Alle Pflichtfunktionen aus Aufgabe II, Demo für Sprechstunde | MB-022, 030, 031, 033 | MB-032, 034, 035 | MB-073, 074 | offen |
| 3 – Owner-Sicht | 23.10. – 01.11. | Ausprägung komplett | MB-042, 044, 045 | MB-040, 041, 043 | MB-074 | offen |
| 4 – Abschluss | 02.11. – 05.11. | **Feature-Freeze Mi 04.11.**, Generalprobe Do 05.11. | MB-050, 053, 055 | MB-051, 052, 055 | MB-075 | offen |
| 5 – Ergebnisdoku | 06.11. – 22.11. | Ergebnisdokumentation abgegeben | MB-054, nur Bugfixes | MB-054, nur Bugfixes | MB-076 | offen |

Eigene Ideen (MB-06x) nur, wenn eine Phase früher fertig ist – spätestens bis zum Feature-Freeze.

## Was wird wann dokumentiert?

| Zeitpunkt | Wer | Was | Wo |
|---|---|---|---|
| Karte starten | Bearbeiter:in | „Zuständig“ und „Gestartet“ ausfüllen, Verlauf-Zeile, Datei nach `2-in-arbeit/` | Karte |
| Während der Arbeit | Bearbeiter:in | Stichpunkte unter **Umsetzung** und **Probleme & Lösungen** | Karte |
| Oberfläche fertig | Bearbeiter:in | Screenshot nach `docs/screenshots/MB-xxx-name.png`, in der Karte verlinken | Karte |
| Karte fertig | Bearbeiter:in | „Aufwand tatsächlich“, Verlauf-Zeile, Datei nach `3-review/` | Karte |
| Review | Reviewer:in | „Reviewt von“, Ergebnis in den Verlauf, Datei nach `4-fertig/` | Karte |
| Ende jeder Sitzung | alle | eine Zeile pro Person | [Arbeitsprotokoll](../docs/arbeitsprotokoll.md) |
| Wöchentliches Meeting | Ardita | Protokoll | [docs/meetings/](../docs/meetings/) |
| Ende jeder Woche | Ardita mit Team | Wochenrückblick, Meilenstein-Status, Risiken | Arbeitsprotokoll, diese Datei, [Risiken](../docs/risiken.md) |
| Wichtige Entscheidung | wer sie trifft | neuer Eintrag | [Entscheidungslog](../docs/entscheidungen.md) |
| Abgabe | Ardita | PDF + Moodle-Bestätigung | [docs/abgaben/](../docs/abgaben/) |

Lieber kurze Stichpunkte sofort als lange Texte am Ende – daraus schreibt Ardita Zwischenbericht und Ergebnisdokumentation.

## Definition of Done

Eine Karte darf nach `4-fertig/`, wenn:

- [ ] alle Akzeptanzkriterien erfüllt sind (bei Code: funktioniert im Browser)
- [ ] eine andere Person reviewt hat (Name in „Reviewt von“)
- [ ] „Umsetzung“, „Aufwand tatsächlich“ und Verlauf ausgefüllt sind
- [ ] bei Oberflächen: Maliá-Design, am Handy brauchbar, Screenshot vorhanden
- [ ] Commits tragen die Karten-ID

Aufwand: **S** ≈ bis 3 h · **M** ≈ 4–6 h · **L** ≈ 1–3 Tage
