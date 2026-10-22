# Bauanleitung – Maliá Budget Schritt für Schritt

Diese Anleitung führt durch den Aufbau der Anwendung in der Reihenfolge, in der die Karten
im Kanban-Board ohnehin aufeinander aufbauen (siehe [kanban/README.md](../kanban/README.md)).
Jeder Schritt entspricht einer Karte `MB-xxx` — beim Abschluss wie gewohnt Karte nach Review
ziehen, Code-Review durch die andere Entwicklerin, dann Fertig.

Grundlage: [docs/konzept.md](konzept.md) (Datenmodell, Formeln, Rollen) und
[docs/entscheidungen.md](entscheidungen.md) (Tech-Stack-Begründungen).

## Bevor es losgeht

- XAMPP installiert, Apache + MySQL laufen
- Projektordner liegt unter `htdocs/malia-budget` (oder Symlink dorthin)
- Editor mit PHP-Unterstützung
- Jede Karte: eigener Feature-Branch (`feature/MB-xxx-kurzname`), siehe [git-workflow.md](git-workflow.md)

## Ordnerstruktur (Ziel)

```
/
├── public/              # alles, was der Browser direkt aufruft
│   ├── index.php        # Startseite / Redirect auf Login
│   ├── login.php
│   ├── logout.php
│   ├── admin/
│   ├── finance/
│   └── owner/
├── src/                 # gemeinsame PHP-Logik, nicht direkt aufrufbar
│   ├── db.php            # PDO-Verbindung
│   ├── rollen.php        # Rollen-Konstanten + erfordereRolle()
│   ├── budget.php         # ladeEigenesBudget(), Kennzahlen-Berechnung
│   └── ...
├── templates/            # gemeinsames Layout (Header, Footer, Nav)
├── database/
│   ├── schema.sql
│   └── demodaten.sql
├── tests/                 # PHPUnit
└── docs/
```

Passt zur Regel aus [kanban/README.md](../kanban/README.md): „Ordner-Regeln: `/admin/`, `/finance/`,
`/owner/` prüfen am Seitenanfang mit `erfordereRolle()`" — jede Datei in diesen drei Ordnern
beginnt mit einem `require`, der die Rolle prüft, bevor irgendwas anderes passiert.

---

## Phase 1 – Setup (AP3)

### Schritt 1 · MB-003 — PHP-Projekt aufsetzen

- Ordnerstruktur oben anlegen
- `src/db.php`: PDO-Verbindung zu `malia_budget` auf `localhost`, Benutzer `root`, kein
  Passwort (XAMPP-Standard, siehe E-003), `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `public/index.php`: leitet eingeloggte Nutzer je Rolle weiter, sonst zu `login.php`

**Definition of Done:** `php -S localhost:8000 -t public` startet ohne Fehler, Seite lädt.

### Schritt 2 · MB-004 — Datenbank: Tabellen (Sinem)

`database/schema.sql` nach dem ER-Modell aus [konzept.md](konzept.md#3-datenmodell):

- `BENUTZER` (id, username UNIQUE, passwort_hash, anzeigename, rolle ENUM)
- `BUDGET` (id, name, bereich ENUM, soll_betrag DECIMAL(10,2), gueltig_von, gueltig_bis, owner_id FK)
- `IST_BUCHUNG` (id, budget_id FK, betrag DECIMAL(10,2), datum, beschreibung, erfasst_von FK,
  erfasst_am, urspruenglicher_betrag DECIMAL(10,2) NULL, korrigiert_am NULL, korrektur_grund NULL)
- `FORECAST` (id, budget_id FK, monat DATE, betrag DECIMAL(10,2), UNIQUE(budget_id, monat))

Wichtig: **`DECIMAL(10,2)`**, nie `FLOAT`/`DOUBLE` (E-015). Import über phpMyAdmin.

**Definition of Done:** Alle 4 Tabellen sichtbar in phpMyAdmin, Fremdschlüssel funktionieren.

### Schritt 3 · MB-005 — Maliá-Demodaten (Sinem)

`database/demodaten.sql`, `INSERT`-Statements gemäß [konzept.md](konzept.md#5-demo-daten-maliá):

- 5 Benutzer: `admin`, `finance`, `studio`, `cafe`, `marketing` — Passwörter mit
  `password_hash('passwort', PASSWORD_DEFAULT)` erzeugen, Hash einsetzen
- Budgets wie in der Tabelle (Reformer-Wartung 4.800€, Café-Wareneinkauf 12.000€, usw.)
- Ist-Buchungen und Forecasts so verteilen, dass **jeder Status mindestens einmal** vorkommt
  (Abgelaufen, Überschritten, Warnung, Im Rahmen)

**Definition of Done:** Mit allen 5 Demo-Logins einloggbar (sobald Login steht), Daten sichtbar.

### Schritt 4 · MB-010/011/012 — Design-Grundlage (Natalia)

- Bootstrap 5 einbinden (CDN oder lokal)
- CSS-Variablen aus [konzept.md](konzept.md#6-design-aus-der-maliá-website):
  `--paper #fffdfa`, `--cream #f3eee5`, `--ink #49443e`, `--muted #7c776f`,
  `--sage #8d9b7f` / `--sage-deep #728267`, `--rose #c99388`, `--line #e5dfd4`,
  Schriften Instrument Serif (Überschriften) + DM Sans (Text)
- `templates/header.php` + `templates/footer.php`: Navigation ändert sich je Rolle
  (`$_SESSION['rolle']`), Logout-Button, Dark-Mode optional (nicht gefordert)
- UI-Bausteine: Status-Badge (4 Farben: salbei/ocker/rosé/grau), Fortschrittsbalken für
  Verbrauch %, Flash-Message-Komponente (Erfolg/Fehler)
- Fehlerseiten: 403 (fremdes Budget), 404

**Definition of Done:** Layout lädt im Browser, am Handy nutzbar (responsive), Maliá-Farben sichtbar.

---

## Phase 2 – Grundsystem (AP4)

### Schritt 5 · MB-021 — Rollen (Sinem)

`src/rollen.php`:
```php
const ROLLE_ADMIN = 'ADMIN';
const ROLLE_FINANCE = 'FINANCE';
const ROLLE_OWNER = 'OWNER';

function erfordereRolle(string $rolle): void {
    if (($_SESSION['rolle'] ?? null) !== $rolle) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }
}
```
Jede Datei in `public/admin/`, `public/finance/`, `public/owner/` beginnt mit
`require_once '../../src/rollen.php'; erfordereRolle(ROLLE_...);`

### Schritt 6 · MB-020 — Login (Sinem)

- `public/login.php`: Formular (Username + Passwort), `password_verify()` gegen Hash aus DB
- Bei Erfolg: `$_SESSION['user_id']`, `$_SESSION['rolle']` setzen, Redirect je Rolle
- `public/logout.php`: `session_destroy()`

**Definition of Done:** Mit allen 5 Demo-Nutzern einloggbar, falscher Login → Fehlermeldung.

### Schritt 7 · MB-022 — Datentrennung (Sinem)

`src/budget.php`:
```php
function ladeEigenesBudget(int $id, array $benutzer): array {
    // SELECT ... WHERE id = :id AND owner_id = :owner_id
    // wenn kein Treffer: http_response_code(403); exit;
}
```
Jede Owner-Seite, die ein Budget per ID aus der URL lädt, nutzt **ausschließlich** diese
Funktion — nie ein direktes `SELECT ... WHERE id = :id` ohne Owner-Check (Risiko R3!).

**Definition of Done:** Zwei Owner-Testkonten anlegen, gegenseitig versuchen fremde Budget-IDs
in der URL aufzurufen → muss 403 geben (Vorgriff auf MB-056).

### Schritt 8 · MB-033 — Kennzahlen & Status berechnen (Sinem)

In `src/budget.php`, Formeln aus [konzept.md](konzept.md#4-kennzahlen-und-status):
```
Ist           = SUM(ist_buchung.betrag) für das Budget
Verfügbar     = soll_betrag - Ist
Verbrauch %   = Ist / soll_betrag * 100
Offener Forecast = SUM(forecast.betrag) ab aktuellem Monat bis gueltig_bis
Hochrechnung  = Ist + Offener Forecast
```
Status-Reihenfolge (von oben nach unten prüfen, erste zutreffende gewinnt):
1. `heute > gueltig_bis` → **Abgelaufen** (grau)
2. `Ist > soll_betrag` → **Überschritten** (rosé)
3. `Verbrauch% >= 80` ODER `Hochrechnung > soll_betrag` → **Warnung** (ocker)
4. sonst → **Im Rahmen** (salbei)

Zusätzlich „noch X Tage" bis `gueltig_bis` berechnen. SQL-Summen immer per `SUM()` in der
Datenbank bilden, nicht in PHP aufaddieren (E-015), in PHP nur auf 2 Nachkommastellen runden.

### Schritt 9 · MB-045 — Verfallszeit sperren (Sinem)

Formulare zum Erfassen/Korrigieren von Ist-Werten prüfen vor dem Speichern: ist
`gueltig_bis` in der Vergangenheit? Wenn ja → Aktion blockieren, Fehlermeldung.

### Schritt 10 · MB-030 — Admin: Benutzer anlegen (Natalia)

`public/admin/`: Formular (Vorname, Nachname/Anzeigename, Username, Rolle-Dropdown,
Passwort) → `password_hash()` → `INSERT INTO BENUTZER`. Liste aller Benutzer darunter.

### Schritt 11 · MB-031 — Finance: Budget anlegen & zuweisen (Natalia)

`public/finance/`: Formular (Name, Bereich-Dropdown, Soll-Betrag, gültig von/bis,
Owner-Dropdown aus allen Nutzern mit Rolle OWNER) → `INSERT INTO BUDGET`.

### Schritt 12 · MB-032 — Finance: Übersicht aller Budgets (Natalia)

Tabelle: alle Budgets, Soll, Ist, Status-Badge (Funktion aus Schritt 8 wiederverwenden),
sortierbar/filterbar nach Bereich.

### Schritt 13 · MB-034 — Owner: Ist-Wert erfassen (Natalia)

`public/owner/`: Formular (Betrag, Datum, Beschreibung) auf der Budget-Detailseite,
nutzt `ladeEigenesBudget()` zur Prüfung, dann `INSERT INTO IST_BUCHUNG`, prüft Verfallszeit
(Schritt 9).

### Schritt 14 · MB-035 — Owner: Ist-Wert korrigieren (Natalia)

Bestehende Buchung bearbeiten: neuer Betrag wird gespeichert, **zusätzlich**
`urspruenglicher_betrag` (nur beim ersten Korrigieren setzen, nicht überschreiben),
`korrigiert_am = NOW()`, `korrektur_grund` (Pflichtfeld).

---

## Phase 3 – Owner-Sicht (AP5)

### Schritt 15 · MB-040 — Owner-Dashboard (Natalia)

Liste aller eigenen Budgets mit Status-Badge, Verbrauch %, „noch X Tage" — Übersicht über
alle Budgets des eingeloggten Owners.

### Schritt 16 · MB-041 — Budget-Detailseite (Natalia)

Pro Budget: Kennzahlen (Soll/Ist/Verfügbar/Status), Liste aller Ist-Buchungen (inkl.
Korrektur-Historie sichtbar), Links zu „Ist-Wert erfassen" / „Forecast eintragen".
„Echtzeit" heißt hier: Seite rechnet bei jedem Aufruf live aus der DB (keine Caches).

### Schritt 17 · MB-042 — Forecast eintragen & anzeigen (Sinem: Backend-Logik)

Formular pro Monat + Budget, `INSERT ... ON DUPLICATE KEY UPDATE` (wegen `UNIQUE(budget_id,
monat)` — neu eintragen überschreibt bestehenden Wert für denselben Monat).

### Schritt 18 · MB-043 — Soll-Ist-Diagramm (Natalia, Chart.js)

Balken- oder Liniendiagramm: Soll vs. Ist je Budget (oder über Zeit), Maliá-Farben nutzen.

### Schritt 19 · MB-044 — Forecast-Ist-Soll-Vergleich (Sinem Berechnung + Natalia Diagramm)

Hochrechnung-Formel aus Schritt 8 visualisieren: Ist (bisher) + offener Forecast vs. Soll,
als Diagramm (Must-Anforderung, siehe E-019).

---

## Phase 4 – Testen (AP6)

### Schritt 20 · MB-050 — PHPUnit: Berechnung, Login, Datentrennung

Unit-Tests für die Formeln aus Schritt 8 (Grenzfälle: genau 80 %, genau am `gueltig_bis`-Tag),
Integrationstest Login (richtiges/falsches Passwort), Test `ladeEigenesBudget()` mit fremder ID.

### Schritt 21 · MB-051 — Eingabeprüfung & Fehlermeldungen

Serverseitige Validierung aller Formulare (Beträge numerisch & positiv, Pflichtfelder,
Datumslogik gültig_von < gültig_bis), verständliche deutsche Fehlermeldungen.

### Schritt 22 · MB-056 — Systemtest mit zwei Owner-Konten

Manueller Test: zwei Owner-Logins, gegenseitig versuchen auf fremde Budgets zuzugreifen
(URL manipulieren) → muss überall 403 geben. Ergebnis in der Karte dokumentieren (Risiko R3).

### Schritt 23 · MB-057 — Abnahmetest mit dem Auftraggeber

Alle Must-Funktionen aus Projektplan 2.2 durchgehen, mit Herrn Spörl/Vertretung abnehmen lassen.

---

## Faustregeln beim Coden

- Jede Geldberechnung: `DECIMAL`, Summen per SQL `SUM()`, PHP nur runden
- Jede Owner-Seite mit Budget-ID aus URL: **immer** `ladeEigenesBudget()`, nie direktes `SELECT ... WHERE id = :id`
- Jede Datenbankabfrage: Prepared Statements (PDO), nie String-Verkettung in SQL
- Commit-Nachricht beginnt mit Karten-ID, endet mit Issue-Nummer (siehe git-workflow.md)
- Vor dem Mergen: andere Entwicklerin reviewt, kann den Code erklären (Definition of Done)
