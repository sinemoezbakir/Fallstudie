# Maliá Budget – Budgetverwaltung für Maliá Pilates & Café

Fallstudie: Budget-Verwaltungssystem (Grundsystem + Ausprägung **Owner-Sicht**) für das
fiktive Studio *Maliá – Pilates & Café*. Das Design orientiert sich an der Maliá-Website
(Projekt `WebProgrammierung-neuaufbau`).

## Worum geht's?

| Rolle | Wer im Studio? | Darf |
|---|---|---|
| **Finance** | Buchhaltung | Budgets anlegen, Ownern zuweisen, Soll-Ist aller Budgets sehen |
| **Owner** | Studio-Leitung, Café-Leitung, Marketing | eigene Budgets sehen, Ausgaben erfassen/korrigieren, Forecast eintragen, Soll-Ist- und Forecast-Vergleich |
| **Admin** | IT | Benutzer anlegen, Rollen zuweisen |

## Tech-Stack

- **Java 21+** mit **Spring Boot** (Web, Thymeleaf, Security, Data JPA, Validation)
- **MariaDB aus XAMPP**, Tabellen und Demo-Daten als SQL-Dateien in `database/`
- Oberfläche: **Thymeleaf**-Seiten im Maliá-Design, Diagramme mit **Chart.js**

Begründungen: [docs/entscheidungen.md](docs/entscheidungen.md)

## Projektorganisation

| Was | Wo |
|---|---|
| Kanban-Board (alle Teilaufgaben + Werdegang) | [kanban/](kanban/README.md) |
| Wer hat wann was gemacht | Karten-Verlauf, [docs/arbeitsprotokoll.md](docs/arbeitsprotokoll.md), `git log` |
| Technische Entscheidungen | [docs/entscheidungen.md](docs/entscheidungen.md) |
| Fachkonzept, Datenmodell, Regeln | [docs/konzept.md](docs/konzept.md) |
| Git-Regeln | [docs/git-workflow.md](docs/git-workflow.md) |
| Screenshots | [docs/screenshots/](docs/screenshots/) |

## Setup

Folgt mit MB-003 (Projekt-Setup) und MB-004 (Datenbank).
