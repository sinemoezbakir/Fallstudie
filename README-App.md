# Studio Maliá – Budgetverwaltung

Webbasiertes Budgetverwaltungssystem für das fiktive „Studio Maliá – Pilates & Café“
(DHBW Wirtschaftsinformatik). Umfang: Grundsystem + Owner-Sicht.

**Technik:** PHP 8.x ohne Framework, MariaDB (XAMPP), PDO mit Prepared Statements,
Bootstrap 5 und Chart.js (beide per CDN, Internet nötig), PHPUnit.

## Rollen

| Rolle | Wer | Darf | Darf nicht |
|---|---|---|---|
| `FINANCE` | Inhaberin | Budgets mit Verfallszeit anlegen, Ownern zuweisen, Soll-Ist-Vergleich ansehen | Ausgaben erfassen, Benutzer verwalten |
| `OWNER` | Bereichsleitungen (Studio, Café, Marketing) | Eigene Budgets sehen, Ausgaben und Forecasts erfassen, Ausgaben korrigieren (Altwert, Datum, Grund werden gespeichert), CSV-Export | Fremde Budgets sehen oder ändern, Finance-/Admin-Seiten |
| `ADMIN` | Verwaltung | Benutzer anlegen, Rollen vergeben | Budgetdaten sehen |

Die Rollen sind als Konstanten in `src/Models/User.php` festgelegt.
Alle Prüfungen laufen auf dem Server (`Auth::requireRole`, Owner-Filter in den SQL-Abfragen).
Ein Zugriff auf ein fremdes oder nicht vorhandenes Budget ergibt bewusst dieselbe Antwort (403).

## Einrichtung

Voraussetzungen: XAMPP (Apache + MariaDB), PHP 8.1 oder neuer, [Composer](https://getcomposer.org/).

1. **Projekt ablegen** und im Projektordner die Abhängigkeiten installieren:
   ```powershell
   composer install
   ```
2. **XAMPP starten:** Im XAMPP Control Panel *MySQL* starten (Apache nur nötig, wenn nicht der
   PHP-Entwicklungsserver aus Schritt 5 genutzt wird).
   Belegt ein anderer Dienst Port 3306 (z. B. ein installiertes MySQL), in
   `xampp\mysql\bin\my.ini` beide `port=` auf 3307 setzen und in
   `xampp\phpMyAdmin\config.inc.php` `$cfg['Servers'][$i]['port'] = '3307';` ergänzen.
3. **Datenbank importieren** (phpMyAdmin, `http://localhost/phpmyadmin`):
   - Reiter **Importieren** → `sql/schema.sql` wählen → **Importieren** (legt die Datenbank
     `budget_malia` und alle Tabellen an).
   - Danach ebenso `sql/seed.sql` importieren (Testdaten).
   - Beide Dateien sind wiederholbar. Achtung: `seed.sql` setzt die Testdaten zurück.
4. **Zugangsdaten eintragen:** `config/config.example.php` nach `config/config.php` kopieren und
   Host, Port, Datenbankname, Benutzer und Passwort anpassen.
   `config/config.php` steht in der `.gitignore` und darf **nie** ins Git.
5. **Starten:**
   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t public
   ```
   Dann `http://localhost:8000` öffnen. Alternativ den Ordner in `htdocs` legen und über Apache
   aufrufen (Adresse dann `http://localhost/<Ordner>/public/`).

### Demo-Modus (Rollenwechsel)

In `config/config.php` schaltet `'demo_rollenwechsel' => true` ein Dropdown in der Navigation ein,
mit dem man ohne erneutes Login zwischen den Demo-Konten wechselt (`public/wechsel.php`).
**Nur für Entwicklung und Präsentation.** Im Echtbetrieb auf `false` setzen, dann ist die Funktion
komplett aus (Standard in `config.example.php`).

Bei eingeschaltetem Demo-Modus zeigt die Login-Seite außerdem **Schnellanmeldung-Buttons** je Demo-Konto
(ohne Passwort). Auch das gilt nur für Entwicklung und Präsentation.

## KI-Einschätzung (optional)

Auf der Budget-Detailseite gibt es den Button **„KI-Einschätzung holen“**. Eine KI (Google Gemini) schätzt
anhand der Ausgaben und Forecasts, ob das Budget bis zum Ende reicht, und gibt eine Empfehlung.

Einrichtung (kostenlos):
1. Auf <https://aistudio.google.com/apikey> einen API-Key erzeugen.
2. In `config/config.php` im Abschnitt `ki` den Key bei `api_key` eintragen und `'aktiv' => true` setzen
   (oder die Umgebungsvariable `GEMINI_API_KEY` setzen). Ohne Key bleibt die Funktion aus, der Rest läuft normal.
3. Bei einem anderen Modell `modell` anpassen.

Datenschutz: Es werden nur Beträge und Daten gesendet, keine Budgetnamen, Beschreibungen oder Benutzernamen.
Aufruf nur auf Knopfdruck, nur für eigene Budgets (serverseitig geprüft), mit CSRF-Schutz und 10 Sekunden
Wartezeit. Der Key steht nie im Code und `config.php` gehört nicht ins Git. Hinweis: Beim kostenlosen Gemini-Kontingent
kann Google die Eingaben zur Verbesserung seiner Dienste verwenden. Für echte Firmendaten daher ein bezahltes Konto
oder einen anderen Anbieter nutzen. Die KI-Antwort ist eine Schätzung, keine Garantie.

## Testlogins (nur Entwicklung)

| Benutzer | Passwort | Rolle | Startseite |
|---|---|---|---|
| `admin` | `Admin2025!` | ADMIN | Benutzerverwaltung |
| `finance` | `Finance2025!` | FINANCE | Soll-Ist-Vergleich |
| `cafe` | `Cafe2025!` | OWNER | Meine Budgets (Café) |
| `marketing` | `Marketing2025!` | OWNER | Meine Budgets (Marketing) |
| `studio` | `Studio2025!` | OWNER | Meine Budgets (Studio) |

Diese Passwörter sind öffentlich dokumentiert und dürfen in keiner echten Umgebung verwendet werden.
In der Datenbank liegen ausschließlich bcrypt-Hashes (`password_hash`).

Seed-Budgets: *Wareneinkauf Café 2026* (im Rahmen) und *Café-Ausstattung 2026* (Warnung) für `cafe`;
*Social-Media-Werbung 2026* (überschritten) und *Messe 2025* (abgelaufen) für `marketing`.

## Status der Budgets

Auslastung = Ist / Soll. Die Schwelle 80 % ist die Konstante `WARNUNG_SCHWELLE_PROZENT`
in `src/Services/BudgetService.php`.

| Status | Bedingung |
|---|---|
| Im Rahmen (blaugrün, ✔) | unter 80 % |
| Warnung (orange, ⚠) | ab 80 % bis einschließlich 100 % |
| Überschritten (vermillion, ✖) | über 100 % |
| Abgelaufen (grau, ⏱) | Verfallszeit vorbei (hat Vorrang) |

„Echtzeit“ bedeutet: Der aktuelle Stand wird beim Neuladen der Seite aus der Datenbank gelesen.

## Barrierefreiheit (Farbsehschwäche, WCAG AA)

- **Farbe ist nie die einzige Information:** Jeder Status zeigt Text **und** Symbol
  (✔ im Rahmen, ⚠ Warnung, ✖ überschritten, ⏱ abgelaufen), erzeugt in `statusBadge()` (`src/helpers.php`).
  Negative Beträge tragen ein ✖.
- **Palette:** Okabe-Ito (Blau `#0072B2`, Orange `#E69F00`, Vermillion `#D55E00`, Blaugrün `#009E73`,
  Grau `#999999`), kein Rot-Grün-Paar.
- **Alle Farben** stehen als CSS-Variablen am Anfang von `public/css/style.css`.
- **Kontrast:** Text mindestens 4,5:1 (Text auf Status-Badges ist schwarz, Hauptfarbe mit weißem Text 6,5:1),
  Rahmen und Grafiken mindestens 3:1. Neue Farben bitte mit einem Kontrastrechner prüfen.
- **Diagramme (Chart.js):** Reihen unterscheiden sich zusätzlich durch Muster (Punkte, Schraffur, einfarbig),
  haben Legende und Beschriftung der Werte; die Canvas-Elemente haben eine Textalternative (`aria-label`).
- **Balken:** Füllmuster je Status (einfarbig, Schrägstreifen, enge Streifen).
- **Bedienung:** deutlich sichtbarer Tastaturfokus, aktiver Menüpunkt zusätzlich mit Strich markiert.

## Projektstruktur

```
public/     Seiten und Einstiegspunkte (admin/, finance/, owner/, css/, login.php, ...)
src/        Auth, Database, Models, Repositories, Services (BudgetService), layout/, helpers.php
config/     config.php (lokal, nicht im Git), config.example.php
sql/        schema.sql, seed.sql
tests/      Unit-Tests (BudgetServiceTest) und Integrationstests (tests/Integration)
```

## Tests

PHPUnit wird mit `composer install` installiert.

```powershell
# alle Tests
C:\xampp\php\php.exe vendor\bin\phpunit

# nur reine Berechnungen (ohne Datenbank und Server)
C:\xampp\php\php.exe vendor\bin\phpunit --testsuite Unit

# nur Integrationstests
C:\xampp\php\php.exe vendor\bin\phpunit --testsuite Integration
```

- **Unit** (`BudgetServiceTest`): Berechnungen und Status inklusive Grenzfälle
  (genau 80 %, genau 100 %, Soll = 0, abgelaufen).
- **Integration** (`tests/Integration`): Sie rufen die echte Anwendung per HTTP auf und brauchen
  daher **MariaDB mit importiertem Seed und den laufenden Webserver** (Schritt 5).
  Fehlt etwas davon, werden die Tests übersprungen. Eine andere Adresse lässt sich mit der
  Umgebungsvariablen `MALIA_BASE_URL` setzen.
  Geprüft werden Datentrennung zwischen Ownern (lesen, ändern, korrigieren, per ID in der URL),
  Rollenzugriff auf Seiten, Weiterleitung zum Login, SQL-Injection im Login, CSV-Export,
  Fehlerseiten und Eingabevalidierung. Die Tests verändern keine Daten.

Eine Liste manueller Testfälle für den Systemtest steht in [docs/testfaelle.md](docs/testfaelle.md).

## Sicherheit (Überblick)

- Passwörter nur als Hash (`password_hash` / `password_verify`), Session-ID wird nach dem Login erneuert.
- Session-Cookie mit `HttpOnly` und `SameSite=Lax`.
- Alle Datenbankzugriffe mit Prepared Statements.
- CSRF-Token in allen Formularen, Logout nur per POST.
- Alle Ausgaben mit `htmlspecialchars` (Funktion `e()`); Daten für Diagramme als maskiertes JSON.
- Rechteprüfung serverseitig; Owner-Daten werden in der SQL-Abfrage nach `owner_id` gefiltert.
- Fehler werden nicht im Browser angezeigt, sondern ins PHP-Error-Log geschrieben (Fehlerseite 500).
- CSV-Export schützt vor Formel-Injection in Excel.

**Bekannte Grenzen** (Studienprojekt): keine Sperre nach vielen fehlgeschlagenen Logins,
kein HTTPS-Zwang (für den Echtbetrieb HTTPS aktivieren und das Cookie-Flag `secure` setzen),
keine Passwortänderung durch den Benutzer, Chart.js und Bootstrap kommen vom CDN.
Bei Apache ohne Konfiguration zeigt eine unbekannte URL die Standard-404-Seite des Servers.
