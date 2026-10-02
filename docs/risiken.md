# Risikoliste

Wird von Ardita wöchentlich geprüft (MB-072). Wahrscheinlichkeit (W) und Auswirkung (A): niedrig / mittel / hoch.

| Nr. | Risiko | W | A | Gegenmaßnahme | Verantwortlich | Status |
|---|---|---|---|---|---|---|
| R-01 | Zeitverzug durch Prüfungen/andere Fächer | mittel | hoch | Pflicht vor Kür: Kann-Karten (MB-06x) erst nach Phase 3; Feature-Freeze 04.11. | Ardita | beobachten |
| R-02 | Spring Boot ist für das Team neu | mittel | mittel | Einfach halten (E-009), Setup gemeinsam machen (MB-003), gegenseitige Reviews | Sinem, Natalia | beobachten |
| R-03 | Unterschiedliche Datenbank-Stände bei Sinem und Natalia | mittel | mittel | Tabellen nur über `database/schema.sql`, Änderungen sofort ansagen | Sinem | beobachten |
| R-04 | Merge-Konflikte durch paralleles Arbeiten | mittel | niedrig | kleine Karten, täglich `git pull`, kurze Branches | Sinem, Natalia | beobachten |
| R-05 | Live-Demo funktioniert bei der Präsentation nicht | niedrig | hoch | Generalprobe 05.11., Screenshots/Video als Backup (MB-055) | Sinem, Natalia | beobachten |
| R-06 | Abgabefrist verpasst (23:59 **UTC**) | niedrig | hoch | Abgaben am Vortag fertig, Ardita lädt hoch, Bestätigung in `docs/abgaben/` | Ardita | beobachten |
| R-07 | Dokumentation wird erst am Ende geschrieben und ist lückenhaft | mittel | hoch | Karten laufend ausfüllen (Definition of Done), Ardita prüft wöchentlich | Ardita | beobachten |
| R-08 | Ausfall einer Person (Krankheit) | niedrig | mittel | Karten gut dokumentiert, jede Person kennt alle Bereiche durch Reviews | alle | beobachten |
