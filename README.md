# Maliá Budget – Budgetverwaltung für Maliá Pilates & Café

Fallstudie: Budget-Verwaltungssystem (Grundsystem + Ausprägung **Owner-Sicht**) für das
fiktive Studio *Maliá – Pilates & Café*. Das Design orientiert sich an der Maliá-Website
(Projekt `WebProgrammierung-neuaufbau`).

## Team

| Person | Rolle |
|---|---|
| Ardita | Projektleitung & Teamsprecherin · Datenbank, Backend |
| Sinem | Entwicklung · Frontend, Übersichtsseiten, Formulare |
| Natalia | Qualität & Dokumentation · Forecast, Reporting, Tests, Berichte |

Grundlage ist der Projektplan (Abgabe 04.10.2026, PDF in [docs/abgaben/](docs/abgaben/)). Jedes Arbeitspaket
AP1–AP7 aus dem Plan ist im Kanban-Board in Karten zerlegt; die Soll-Stunden der Karten ergeben die Stunden aus dem Plan.

## Termine

| Datum | Termin |
|---|---|
| So 04.10.2026 | Abgabe Projektplan |
| Fr 23.10.2026 | Sprechstunde |
| So 25.10.2026 | Abgabe Zwischenbericht |
| Fr 06.11.2026 | Präsentation |
| So 22.11.2026 | Abgabe Ergebnisdokumentation |

Abgaben jeweils bis 23:59 UTC in Moodle. Zeitplan und Meilensteine: [kanban/README.md](kanban/README.md#zeitplan-und-meilensteine)

## Worum geht's?

| Rolle | Wer im Studio? | Darf |
|---|---|---|
| **Finance** | Inhaberin | Budgets anlegen, Ownern zuweisen, Soll-Ist aller Budgets sehen |
| **Owner** | Studio-Leitung, Café-Leitung, Marketing | eigene Budgets sehen, Ausgaben erfassen/korrigieren, Forecast eintragen, Soll-Ist- und Forecast-Vergleich |
| **Admin** | Verwaltung | Benutzer anlegen, Rollen zuweisen |

## Tech-Stack

- **PHP 8** ohne Framework, Datenbankzugriff mit **PDO** (Prepared Statements)
- **MariaDB aus XAMPP**, Tabellen und Demo-Daten als SQL-Dateien in `database/`
- Oberfläche: **Bootstrap 5** im Maliá-Design, Diagramme mit **Chart.js**
- Tests mit **PHPUnit**

Begründungen: [docs/entscheidungen.md](docs/entscheidungen.md)

## Projektorganisation

| Was | Wo |
|---|---|
| **Kanban-Board** | https://github.com/users/sinemoezbakir/projects/3 |
| Board-Regeln, Team, Zeitplan, Doku-Regeln | [kanban/README.md](kanban/README.md) |
| Wer hat wann was gemacht | Issue-Timeline, Pull Requests, [docs/arbeitsprotokoll.md](docs/arbeitsprotokoll.md), `git log` |
| Technische Entscheidungen | [docs/entscheidungen.md](docs/entscheidungen.md) |
| Fachkonzept, Datenmodell, Regeln | [docs/konzept.md](docs/konzept.md) |
| Git-Regeln | [docs/git-workflow.md](docs/git-workflow.md) |
| Screenshots | [docs/screenshots/](docs/screenshots/) |

## Setup

Folgt mit MB-003 (Projekt-Setup) und MB-004 (Datenbank).
