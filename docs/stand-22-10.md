# Aktueller Stand – 22.10.2026

One-Pager zum aktuellen Funktionsumfang der Maliá-Budget-App. Ersetzt nicht den
Zwischenbericht (siehe Vorgaben), sondern dient als schneller Überblick für
Team und Repo.

## Status

Grundsystem und Owner-Sicht sind **funktional vollständig umgesetzt und
getestet** (alle Must-Anforderungen aus Projektplan 2.2). Visuell bewusst
noch einfach gehalten (Feinschliff = MB-052, Soll-Karte, noch offen).

## Funktionsumfang

### Login & Rollen
- Anmeldung mit Benutzername/Passwort, 3 feste Rollen (Admin, Finance, Owner)
- Jede Rolle sieht nur ihren eigenen Bereich, Zugriff auf fremde Bereiche → 403

### Admin
- Benutzer anlegen (Anzeigename, Benutzername, Rolle, Passwort)
- Liste aller Benutzer

### Finance
- Übersicht aller Budgets mit Owner, Soll, Ist, Status
- Filter nach Bereich (Studio/Café/Marketing)
- Neues Budget anlegen und einem Owner zuweisen

### Owner
- Dashboard: alle eigenen Budgets, Status-Badge, Verbrauchsbalken, „noch X Tage"
- Budget-Detailseite: Kennzahlen (Soll/Ist/Verfügbar), zwei Chart.js-Diagramme
  (Soll-Ist-Vergleich, Forecast-Ist-Soll-Vergleich)
- Ausgabe erfassen
- Ausgabe korrigieren (Originalbetrag + Grund bleiben nachvollziehbar sichtbar)
- Forecast pro Monat eintragen (überschreibt bestehenden Wert für denselben Monat)

### Berechnungslogik
- 4 Status automatisch: Im Rahmen / Warnung / Überschritten / Abgelaufen
- Warnung wird sowohl durch hohen Verbrauch als auch durch die
  Forecast-Hochrechnung ausgelöst
- Verfallszeit sperrt Erfassen/Korrigieren nach Ablauf

### Qualitätssicherung
- 14 PHPUnit-Tests (Berechnung inkl. Grenzfälle, Login, Datentrennung) – alle grün
- Systematischer Sicherheitstest: zwei Owner-Konten können sich gegenseitig
  nicht in die Daten schauen (Risiko R3 nicht eingetreten)

## Technischer Stand

- PHP 8, PDO/Prepared Statements, MariaDB (XAMPP), Bootstrap 5, Chart.js
- 18 Commits auf Branch `ardita-try-fallstudie`, noch nicht in `main` gemerged
  (PR steht noch aus)
- Schritt-für-Schritt-Nachvollziehbarkeit: [docs/bauanleitung.md](bauanleitung.md)

## Screenshots

Noch keine im Repo abgelegt. Platz dafür: [docs/screenshots/](screenshots/).
