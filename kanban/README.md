# Kanban-Board

**Board: https://github.com/users/sinemoezbakir/projects/3**

Das Board setzt den **Projektplan** (Abgabe 04.10.2026) um und ist gleichzeitig unsere **Dokumentation des
Werdegangs**. Jede Teilaufgabe ist ein **GitHub-Issue** (Karte `MB-xxx`) und gehört zu genau einem
**Arbeitspaket** aus Abschnitt 6.2 des Plans. GitHub protokolliert automatisch, wer eine Karte wann
verschoben, kommentiert oder geschlossen hat.

| Spalte | Bedeutung |
|---|---|
| Backlog | gesammelt, noch nicht eingeplant (inkl. eigener Ideen) |
| Todo | für die aktuelle Phase eingeplant, kann gestartet werden |
| In Arbeit | jemand arbeitet gerade daran (**max. 2 Karten pro Person**) |
| Review | fertig, wartet auf Review durch eine zweite Person |
| Fertig | reviewt und in `main` |

| Feld | Wofür |
|---|---|
| **Arbeitspaket** | AP1–AP7 aus dem Projektplan – in der Tabelle danach gruppieren |
| **Soll (h)** | geplante Stunden; Summe je AP = Tabelle 6.2 im Projektplan |
| **Ist (h)** | tatsächliche Stunden, trägt die bearbeitende Person beim Abschluss ein |
| **Fällig** | Datum für die Zeitplan-Ansicht |
| **Milestone** | Phase 0–5, endet jeweils mit Meilenstein M1–M6 |

| Ansicht | Wofür |
|---|---|
| **Kanban** | tägliche Arbeit: Karten per Drag & Drop verschieben |
| **Tabelle** | Controlling: nach *Arbeitspaket* gruppieren → Soll- und Ist-Stunden je AP |
| **Zeitplan** | Karten auf der Zeitachse nach Fälligkeit (für Berichte) |

- **Labels:** Epic (`Epic 0` … `Epic 7`), Person (`Ardita`, `Sinem`, `Natalia`), Priorität nach MoSCoW (`Muss`, `Soll`, `Kann`)
- **Soll- und Kann-Karten** haben keine Soll-Stunden: Sie sind nicht in den 135 h eingeplant und kommen nur dran, wenn Zeit übrig ist.
- **Neue Karte:** *Issues → New issue → Karte (Aufgabe)*, danach im Board einsortieren und Arbeitspaket setzen
- Sobald Natalia und Ardita Collaborators sind: zusätzlich als **Assignee** eintragen

## Team

Wie im Projektplan (3.2 Rollenverteilung, 3.3 RACI). Jede Person hat 45 h, davon 9 h für Absprachen.

| Person | Rolle | Verantwortlich für |
|---|---|---|
| **Ardita** | Projektleitung & Teamsprecherin | AP1 Projektmanagement, AP3 Einrichtung, Datenbank und Backend im Grundsystem (Login, Rollen, Datentrennung, Berechnung, Verfallszeit) |
| **Sinem** | Entwicklung | Frontend: Design, Layout, Formulare, Finance-Übersicht, Owner-Dashboard, Diagramm; Code-Reviews; Demo |
| **Natalia** | Qualität & Dokumentation | Forecast und Forecast-Vergleich, Eingabeprüfung, AP6 Testen, Zwischenbericht, Präsentation, Ergebnisdoku |

Alle drei programmieren mit, damit nicht nur eine Person den Code versteht (Risiko R1).
Jede Code-Karte wird von einer anderen Person reviewt (Risiko R2).

## Arbeitspakete im Board

| AP | Ardita | Sinem | Natalia | Soll | Karten |
|---|--:|--:|--:|--:|---|
| AP1 Projektmanagement | 12 | – | – | 12 | MB-070, 071, 072, 073, 077 |
| AP2 Analyse und Konzept | 4 | 3 | 3 | 10 | MB-002 |
| AP3 Einrichtung | 6 | – | – | 6 | MB-001, 003, 004 |
| AP4 Grundsystem | 14 | 14 | 2 | 30 | Ardita: MB-005, 020, 021, 022, 030, 033, 045 · Sinem: MB-010, 011, 012, 031, 032, 034, 035 · Natalia: MB-051 |
| AP5 Owner-Sicht | – | 10 | 8 | 18 | Sinem: MB-040, 041, 043 · Natalia: MB-042, 044 |
| AP6 Testen | – | 4 | 12 | 16 | MB-050, 056, 057, 058 |
| AP7 Dokumentation | – | 5 | 11 | 16 | Sinem: MB-053, 054, 055 · Natalia: MB-074, 075, 076 |
| Absprachen (20 %) | 9 | 9 | 9 | 27 | Jour fixe, keine Karte |
| **Summe** | **45** | **45** | **45** | **135** | |

## Termine

Abgaben immer bis **23:59 UTC** in Moodle (= 00:59 Uhr deutscher Winterzeit bzw. 01:59 Uhr Sommerzeit am Folgetag – nicht darauf verlassen, lieber am Vortag abgeben).

| Datum | Termin | Karte |
|---|---|---|
| So 04.10.2026 | **Abgabe Projektplan** (M1) | MB-070 |
| So 11.10.2026 | Annahmen vom Auftraggeber bestätigt (M2) | MB-077 |
| Fr 23.10.2026 | Sprechstunde: Grundsystem vorführen | MB-073 |
| So 25.10.2026 | **Abgabe Zwischenbericht** | MB-074 |
| Mi 04.11.2026 | Feature-Freeze | – |
| Do 05.11.2026 | Generalprobe | MB-055 |
| Fr 06.11.2026 | **Präsentation + Abnahme** (M5) | MB-075, MB-057 |
| So 22.11.2026 | **Abgabe Ergebnisdokumentation** (M6) | MB-076 |

## Zeitplan und Meilensteine

Gleich wie Abschnitt 6.1 im Projektplan. Ardita hält die Spalte **Status** aktuell (MB-072).

| Phase | Zeitraum | Meilenstein (Ende der Phase) | Ardita | Sinem | Natalia | Status |
|---|---|---|---|---|---|---|
| 0 – Planung | 07.09. – 04.10. | **M1** Projektplan abgegeben | MB-070, 001, 002 | MB-001, 002 | MB-001, 002 | in Arbeit |
| 1 – Fundament | 05.10. – 11.10. | **M2** App läuft, DB mit Demodaten, Design-Grundlage, Annahmen bestätigt | MB-003, 004, 005, 077 | MB-010, 011, 012 | – | offen |
| 2 – Grundsystem | 12.10. – 22.10. | **M3** Grundsystem fertig und getestet, Demo in der Sprechstunde | MB-020, 021, 022, 030, 033, 045, 073 | MB-031, 032, 034, 035 | MB-050, 051 | offen |
| 3 – Owner-Sicht | 23.10. – 01.11. | **M4** Owner-Sicht komplett, Zwischenbericht abgegeben | – | MB-040, 041, 043, 054 | MB-042, 044, 074 | offen |
| 4 – Abschluss | 02.11. – 06.11. | **M5** Präsentation (Feature-Freeze 04.11.) | – | MB-052 (Soll), 053, 055 | MB-056, 057, 075 | offen |
| 5 – Ergebnisdoku | 07.11. – 22.11. | **M6** Ergebnisdokumentation abgegeben | – | nur Bugfixes | MB-076 | offen |

Laufend: Ardita MB-071, MB-072 · Sinem und Natalia MB-058 (Code-Reviews).
Eigene Ideen (MB-06x, `Kann`) nur, wenn eine Phase früher fertig ist – spätestens bis zum Feature-Freeze.

## Was wird wann dokumentiert?

| Zeitpunkt | Wer | Was | Wo |
|---|---|---|---|
| Karte starten | Bearbeiter:in | Sich als Assignee eintragen, „Gestartet“ im Issue-Text, Karte nach **In Arbeit** | Issue |
| Während der Arbeit | Bearbeiter:in | Stichpunkte unter **Umsetzung** und **Probleme & Lösungen** (Issue-Text bearbeiten oder Kommentar) | Issue |
| Oberfläche fertig | Bearbeiter:in | Screenshot per Drag & Drop in einen Kommentar ziehen (GitHub speichert das Bild) | Issue |
| Karte fertig | Bearbeiter:in | **Ist (h)** im Board eintragen, Checkboxen abhaken, Karte nach **Review** | Board + Issue |
| Review | Reviewer:in | Kommentar „Review OK“ bzw. Änderungswünsche, „Reviewt von“ eintragen, Karte nach **Fertig** und Issue schließen | Issue |
| Ende jeder Sitzung | alle | eine Zeile pro Person | [Arbeitsprotokoll](../docs/arbeitsprotokoll.md) |
| Wöchentlicher Jour fixe (Teams) | Ardita | Protokoll, Board durchgehen, Hindernisse | [docs/meetings/](../docs/meetings/) |
| An jedem Meilenstein | Ardita mit Team | Soll-Ist der Stunden je AP (Tabellenansicht), Burnup, Risiken, Stakeholder, Retrospektive (Start-Stop-Continue) | Arbeitsprotokoll, diese Datei, [Risiken](../docs/risiken.md) |
| Wichtige Entscheidung | wer sie trifft | neuer Eintrag | [Entscheidungslog](../docs/entscheidungen.md) |
| Abgabe | Ardita | PDF + Moodle-Bestätigung | [docs/abgaben/](../docs/abgaben/) |

Lieber kurze Stichpunkte sofort als lange Texte am Ende – daraus entstehen Zwischenbericht und Ergebnisdokumentation.

## Definition of Done

Eine Karte darf nach **Fertig**, wenn:

- [ ] alle Akzeptanzkriterien erfüllt sind (bei Code: funktioniert im Browser und getestet)
- [ ] eine andere Person reviewt hat und den Code erklären kann (Name in „Reviewt von“)
- [ ] „Umsetzung“ und **Ist (h)** ausgefüllt sind
- [ ] bei Oberflächen: Maliá-Design, am Handy brauchbar, Screenshot vorhanden
- [ ] Commits tragen die Karten-ID
