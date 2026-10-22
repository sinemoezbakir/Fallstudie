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
| **Ardita** | Projektleitung, Teamsprecherin & Dokumentation | AP1 Projektmanagement, Risiko- und Projektcontrolling, Testkoordination (System- und Abnahmetest), Zwischenbericht, Präsentation, Ergebnisdokumentation — **programmiert nicht mit** |
| **Sinem** | Entwicklung Backend | Datenbank, Login und Rollen, Datentrennung, Berechnungen, Forecast und Hochrechnung, Verfallszeit; Code-Reviews |
| **Natalia** | Entwicklung Frontend | Design, Layout, Formulare, Finance-Übersicht, Owner-Dashboard, Diagramme; Code-Reviews; Demo |

Sinem und Natalia arbeiten beide an Grundsystem und Owner-Sicht mit und reviewen sich gegenseitig, damit nicht nur
eine Person den Code versteht (Risiko R1). Jede Code-Karte wird von einer anderen Person reviewt (Risiko R2).
Die Projektleitung programmiert nicht mit und steuert so Planung, Tests und Dokumentation unabhängig vom Code.

## Arbeitspakete im Board

| AP | Ardita | Sinem | Natalia | Soll | Karten |
|---|--:|--:|--:|--:|---|
| AP1 Projektmanagement | 12 | – | – | 12 | MB-070, 071, 072, 073, 077 |
| AP2 Analyse und Konzept | 4 | 3 | 3 | 10 | MB-002 |
| AP3 Einrichtung | 2 | 3 | 1 | 6 | Ardita: MB-001 · Sinem: MB-003, 004 · Natalia: Abstimmung/Review |
| AP4 Grundsystem | – | 11 | 10 | 21 | Sinem: MB-005, 020, 021, 022, 033, 045 · Natalia: MB-010, 011, 012, 030, 031, 032, 034, 035, 051 |
| AP5 Owner-Sicht | – | 5 | 7 | 12 | Sinem: MB-042, 044 · Natalia: MB-040, 041, 043 |
| AP6 Testen | 5 | 8 | 8 | 21 | Ardita: MB-057 · Sinem: MB-050, 058 · Natalia: MB-056, 058 |
| AP7 Dokumentation | 13 | 6 | 7 | 26 | Ardita: MB-074, 075, 076 · Sinem: MB-053 · Natalia: MB-054, 055 |
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
| 0 – Planung | 07.09. – 04.10. | **M1** Projektplan abgegeben | MB-070, 001, 002 | MB-002 | MB-002 | in Arbeit |
| 1 – Fundament | 05.10. – 11.10. | **M2** App läuft, DB mit Demodaten, Design-Grundlage, Annahmen bestätigt | MB-001, 077 | MB-003, 004, 005 | MB-010, 011, 012 | offen |
| 2 – Grundsystem | 12.10. – 22.10. | **M3** Grundsystem fertig und getestet, Demo in der Sprechstunde | MB-073 | MB-020, 021, 022, 033, 045 | MB-030, 031, 032, 034, 035, 051 | offen |
| 3 – Owner-Sicht | 23.10. – 01.11. | **M4** Owner-Sicht komplett, Zwischenbericht abgegeben | MB-074 | MB-042, 044 | MB-040, 041, 043 | offen |
| 4 – Abschluss | 02.11. – 06.11. | **M5** Präsentation (Feature-Freeze 04.11.) | MB-057, 075 | MB-052 (Soll), 053 | MB-054, 055, 056 | offen |
| 5 – Ergebnisdoku | 07.11. – 22.11. | **M6** Ergebnisdokumentation abgegeben | MB-076 | nur Bugfixes | nur Bugfixes | offen |

Laufend: Ardita MB-071, MB-072 (Testkoordination auch MB-057) · Sinem und Natalia MB-058 (Code-Reviews).
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
