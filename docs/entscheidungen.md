fest (Projektplan 6.1) |||||markieren.

| Nr. | Datum | Entscheidung | Begründung | Wer | Status |
|---|---|---|---|---|---|
| E-001 | 2026-10-02 | Ausprägung: **Owner-Sicht** | Team-Entscheidung | Team | fest |
| E-002 | 2026-10-02 | Java mit **Spring Boot**, Build per Maven Wrapper | Vorgabe Java; Spring Boot liefert Webserver, Login/Rollen und Datenbankzugriff fertig mit, so bleibt Zeit für Funktionen und Oberfläche | Vorschlag | ersetzt durch E-013 |
| E-003 | 2026-10-02 | Datenbank: **MariaDB aus XAMPP**, Datenbank `malia_budget`, Benutzer `root` ohne Passwort (XAMPP-Standard) | bereits installiert, aus dem Web-Projekt bekannt | Team | fest |
| E-004 | 2026-10-02 | Grafische Web-Oberfläche mit **Thymeleaf** im Maliá-Design, Diagramme mit **Chart.js** | Frontend ist wichtig; Farben/Schriften aus der Maliá-Website übernehmbar; alles in einem Java-Projekt | Vorschlag | ersetzt durch E-014 |
| E-005 | 2026-10-02 | Tabellen und Demo-Daten als **SQL-Dateien** in `database/`, Import über phpMyAdmin | einfach und aus dem Web-Projekt bekannt; Änderungen am Schema immer in der Datei nachziehen und im Team ansagen | Vorschlag | offen – bestätigen |
| E-006 | 2026-10-02 | Korrektur einer Ausgabe: Betrag wird geändert, **Originalbetrag, Datum und Grund werden gespeichert** | nachvollziehbar, aber ohne aufwendige Versionierung | Vorschlag | offen – bestätigen |
| E-007 | 2026-10-02 | Geldbeträge als `DECIMAL(10,2)` in der Datenbank und `BigDecimal` in Java | `double` rechnet bei Geld ungenau | Vorschlag | ersetzt durch E-015 |
| E-008 | 2026-10-02 | Kanban-Board als Ordner im Repo, Karten-ID `MB-xxx` in Branch- und Commit-Namen | Werdegang ist über Karten und `git log` lückenlos dokumentiert | Vorschlag | ersetzt durch E-012 |
| E-009 | 2026-10-02 | **Bewusst einfach halten:** keine Migrations-Tools, keine REST-API, vier Status statt fünf, Tests nur für Berechnung und Datentrennung | realistischer Umfang für zwei Entwicklerinnen; Zeit fließt in Oberfläche, Funktionen und Dokumentation | Sinem | fest |
| E-010 | 2026-10-02 | Team: **Ardita** Projektleitung & Dokumentation, **Sinem** und **Natalia** Entwicklung | klare Verantwortung; Doku läuft parallel zur Entwicklung statt am Ende | Team | ersetzt durch E-016 |
| E-011 | 2026-10-02 | Feature-Freeze am **Mi 04.11.2026**, danach nur noch Bugfixes | Generalprobe 05.11. und Präsentation 06.11. absichern | Vorschlag | fest (Projektplan 6.1) |
| E-012 | 2026-10-02 | Kanban-Board als **GitHub Project** mit Issues als Karten, Phasen als Milestones, Review über Pull Requests | echtes Board mit Drag & Drop; GitHub protokolliert Statuswechsel, Commits und Reviews automatisch | Sinem | fest |
| E-013 | 2026-10-02 | **PHP 8 ohne Framework**, Datenbankzugriff mit PDO | mit dem Betreuer abgestimmt; wird nicht kompiliert, Änderungen sofort testbar; keine Einarbeitung in ein Framework nötig (Projektplan 7.1); Bestätigung per E-Mail bis M2 (MB-077) | Team | fest |
| E-014 | 2026-10-02 | Oberfläche mit **PHP-Templates und Bootstrap 5** im Maliá-Design, Diagramme mit **Chart.js** | Formulare und Tabellen sehen ohne eigenes Design ordentlich aus, Maliá-Farben über CSS-Variablen | Team | fest |
| E-015 | 2026-10-02 | Geldbeträge als `DECIMAL(10,2)` in der Datenbank; Summen rechnet die Datenbank (`SUM`), in PHP immer auf 2 Stellen runden | Gleitkommazahlen rechnen bei Geld ungenau | Team | fest |
| E-016 | 2026-10-02 | Rollen im Team wie im Projektplan: **Ardita** Projektleitung + DB/Backend, **Sinem** Frontend, **Natalia** Qualität, Forecast und Doku; je 45 h | alle programmieren mit (Risiko R1), gleichmäßige Last, passt zu RACI und Arbeitspaketen | Team | fest |
| E-017 | 2026-10-02 | Studio mit drei Budget-Bereichen: **Studio, Café, Marketing**; Finance = Inhaberin, Admin = Verwaltung | entspricht dem Projektplan (1.1, 1.2) und den Demodaten | Team | fest |
| E-018 | 2026-10-02 | Board-Felder **Arbeitspaket**, **Soll (h)** und **Ist (h)** statt eigener Excel-Tabelle | Projektcontrolling direkt aus dem Board: Soll-Ist je AP, Burnup (Projektplan 7.4) | Sinem | fest |
| E-019 | 2026-10-02 | Soll-Ist-Diagramm und Forecast-Ist-Soll-Vergleich sind **Must** | beide werden in der Aufgabe für die Owner-Sicht verlangt (MoSCoW im Projektplan 2.2 angepasst) | Team | fest |
