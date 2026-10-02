# Entscheidungslog

Jede wichtige technische oder fachliche Entscheidung bekommt einen Eintrag. Nicht löschen –
wenn sich etwas ändert, neuen Eintrag anlegen und den alten als „ersetzt durch E-xxx“ markieren.

| Nr. | Datum | Entscheidung | Begründung | Wer | Status |
|---|---|---|---|---|---|
| E-001 | 2026-10-02 | Ausprägung: **Owner-Sicht** | Team-Entscheidung | Team | fest |
| E-002 | 2026-10-02 | Java mit **Spring Boot**, Build per Maven Wrapper | Vorgabe Java; Spring Boot liefert Webserver, Login/Rollen und Datenbankzugriff fertig mit, so bleibt Zeit für Funktionen und Oberfläche | Vorschlag | offen – bestätigen |
| E-003 | 2026-10-02 | Datenbank: **MariaDB aus XAMPP**, Datenbank `malia_budget`, Benutzer `root` ohne Passwort (XAMPP-Standard) | bereits installiert, aus dem Web-Projekt bekannt | Team | fest |
| E-004 | 2026-10-02 | Grafische Web-Oberfläche mit **Thymeleaf** im Maliá-Design, Diagramme mit **Chart.js** | Frontend ist wichtig; Farben/Schriften aus der Maliá-Website übernehmbar; alles in einem Java-Projekt | Vorschlag | offen – bestätigen |
| E-005 | 2026-10-02 | Tabellen und Demo-Daten als **SQL-Dateien** in `database/`, Import über phpMyAdmin | einfach und aus dem Web-Projekt bekannt; Änderungen am Schema immer in der Datei nachziehen und im Team ansagen | Vorschlag | offen – bestätigen |
| E-006 | 2026-10-02 | Korrektur einer Ausgabe: Betrag wird geändert, **Originalbetrag, Datum und Grund werden gespeichert** | nachvollziehbar, aber ohne aufwendige Versionierung | Vorschlag | offen – bestätigen |
| E-007 | 2026-10-02 | Geldbeträge als `DECIMAL(10,2)` in der Datenbank und `BigDecimal` in Java | `double` rechnet bei Geld ungenau | Vorschlag | offen – bestätigen |
| E-008 | 2026-10-02 | Kanban-Board als Ordner im Repo, Karten-ID `MB-xxx` in Branch- und Commit-Namen | Werdegang ist über Karten und `git log` lückenlos dokumentiert | Vorschlag | ersetzt durch E-012 |
| E-009 | 2026-10-02 | **Bewusst einfach halten:** keine Migrations-Tools, keine REST-API, vier Status statt fünf, Tests nur für Berechnung und Datentrennung | realistischer Umfang für zwei Entwicklerinnen; Zeit fließt in Oberfläche, Funktionen und Dokumentation | Sinem | fest |
| E-010 | 2026-10-02 | Team: **Ardita** Projektleitung & Dokumentation, **Sinem** und **Natalia** Entwicklung | klare Verantwortung; Doku läuft parallel zur Entwicklung statt am Ende | Team | fest |
| E-011 | 2026-10-02 | Feature-Freeze am **Mi 04.11.2026**, danach nur noch Bugfixes | Generalprobe 05.11. und Präsentation 06.11. absichern | Vorschlag | offen – bestätigen |
| E-012 | 2026-10-02 | Kanban-Board als **GitHub Project** mit Issues als Karten, Phasen als Milestones, Review über Pull Requests | echtes Board mit Drag & Drop; GitHub protokolliert Statuswechsel, Commits und Reviews automatisch | Sinem | fest |
