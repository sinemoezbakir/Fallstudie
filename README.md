# Maliá Budget – Budgetverwaltung für Maliá Pilates & Café

Fallstudie: Budget-Verwaltungssystem (Grundsystem + Ausprägung **Owner-Sicht**) für das
fiktive Studio *Maliá – Pilates & Café*. Das Design orientiert sich an der Maliá-Website
(Projekt `WebProgrammierung-neuaufbau`).

## Team

| Person | Rolle |
|---|---|
| Ardita | Projektleitung, Teamsprecherin & Dokumentation · Koordination, Risiko- und Projektcontrolling, Testkoordination (programmiert nicht mit) |
| Sinem | Entwicklung Backend · Datenbank, Login & Rollen, Datentrennung, Berechnungen, Forecast & Hochrechnung, Verfallszeit |
| Natalia | Entwicklung Frontend · Design, Layout, Formulare, Finance-Übersicht, Owner-Dashboard, Diagramme |

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

## Schnellstart

Voraussetzung: [XAMPP](https://www.apachefriends.org/) unter `C:\xampp` (PHP 8.2 + MariaDB).

1. Repo klonen
2. **`start-demo.bat` doppelklicken** – startet MariaDB, legt beim ersten Mal die Datenbank
   `malia_budget` mit Demo-Daten an, startet die App und öffnet **http://localhost:8000**
3. Anmelden mit `finance` / `demo1234` – danach oben in der dunklen Leiste die Ansicht wechseln

<details>
<summary>Manuell statt per Skript</summary>

```bash
# MariaDB im XAMPP Control Panel starten, dann:
C:\xampp\mysql\bin\mysql.exe -uroot --default-character-set=utf8mb4 < database\schema.sql
C:\xampp\mysql\bin\mysql.exe -uroot --default-character-set=utf8mb4 malia_budget < database\demodaten.sql
C:\xampp\php\php.exe -S localhost:8000 -t public
```

Wichtig: Die App muss mit `public/` als Wurzel laufen (alle Links beginnen mit `/`).
Ein Aufruf über `http://localhost/malia-budget/public/` mit dem XAMPP-Apache funktioniert deshalb nicht.
</details>

### Demo-Konten (Passwort überall `demo1234`)

| Login | Name | Rolle | Was man zeigen kann |
|---|---|---|---|
| `admin` | Admin | Admin | Benutzer anlegen, Passwort zurücksetzen |
| `finance` | Inhaberin | Finance | alle Budgets, Filter, Diagramm je Bereich, Budget anlegen/bearbeiten, CSV |
| `studio` | Studio-Leitung | Owner | Warnung durch Verbrauch, Warnung durch Forecast, Budget läuft bald ab |
| `cafe` | Café-Leitung | Owner | überschrittenes Budget |
| `marketing` | Marketing-Leitung | Owner | abgelaufenes Budget (gesperrt) |

**Demo-Modus (E-021):** Nach dem Login wechselt man über die Leiste unter der Navigation mit einem Klick in
jedes Konto – Rollen und Datentrennung gelten dabei unverändert. Im Kontomenü setzt
„Demo-Daten zurücksetzen“ alles auf den Ausgangsstand. Abschalten: Umgebungsvariable `MALIA_DEMO=0`.

## Funktionen

| Bereich | Funktion |
|---|---|
| Alle | Login, Rollenwechsel (Demo), Passwort ändern, Abmelden, Handy-tauglich |
| Owner | Dashboard mit Kennzahlen und „Braucht Aufmerksamkeit“, Budget-Detail mit Verlaufsdiagramm (Ist, Forecast, Soll) und Soll-Ist-Ring, Ausgabe erfassen mit Live-Vorschau, Korrektur mit Nachweis, Forecast als Monatsraster mit Hochrechnung, CSV-Export |
| Finance | Übersicht mit Kennzahlen, Status-/Bereichsfilter, Suche, Sortierung, Diagramm je Bereich, Budget anlegen/bearbeiten/verlängern, Detailansicht, CSV-Export |
| Admin | Zugänge anlegen, Passwort zurücksetzen |
| Regeln | 4 Status (konzept.md Abschnitt 4), Verfallszeit sperrt Buchungen, Owner sieht nur eigene Budgets |
| Sicherheit | Prepared Statements, `password_hash`, CSRF-Token für jedes Formular, Session-Cookie HttpOnly/SameSite, Session-Timeout, Security-Header (CSP u. a.), Ausgabe-Escaping, CSV-Injection-Schutz |

## Tests

```bash
# Unit- und Integrationstests (PHPUnit 10, phpunit.phar einmalig laden):
curl -L -o phpunit.phar https://phar.phpunit.de/phpunit-10.5.phar
C:\xampp\php\php.exe phpunit.phar

# Systemtest: klickt sich durch die laufende App (setzt danach die Demo-Daten zurück)
C:\xampp\php\php.exe tests\rundgang.php
```

## Projektstruktur

```
public/        alles, was der Browser aufruft (Seiten je Rolle, CSS, JS)
src/           Logik: app.php (Einstieg), budget.php (Kennzahlen), validierung.php, csrf.php, demo.php, ...
templates/     Kopf, Fuß, Fehlerseiten, Budget-Detail, Formulare
database/      schema.sql, demodaten.sql
tests/         PHPUnit-Tests + rundgang.php
```

Wie die Anwendung Schritt für Schritt entstanden ist: [docs/bauanleitung.md](docs/bauanleitung.md).
