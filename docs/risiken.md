# Risikoliste

Gleiche Risiken wie im Projektplan (Kapitel 8). Ardita prüft sie an jedem Meilenstein (MB-072): Ist ein Risiko
eingetreten? Hat die Maßnahme gewirkt? Gibt es neue oder Folgerisiken?
Wahrscheinlichkeit (W) und Auswirkung (A): niedrig / mittel / hoch. **Kritisch** = fünf der zehn Risiken.

| Nr. | Risiko | W | A | Strategie und Maßnahme | Betrifft | Karten | Verantwortlich | Status |
|---|---|---|---|---|---|---|---|---|
| R1 | Ein Teammitglied fällt aus (**kritisch**) | mittel | hoch | Vermindern: alle programmieren mit, Wissen in Karten dokumentieren, MoSCoW-Liste | AP4, AP5 | MB-058 | Ardita | beobachten |
| R2 | Die KI baut Fehler in den Code (**kritisch**) | hoch | mittel | Vermindern: Pull Requests, jede Funktion wird von einer zweiten Person geprüft | AP6 | MB-058 | Sinem, Natalia | beobachten |
| R3 | Owner sehen fremde Daten (**kritisch**) | mittel | hoch | Vermindern: Tests mit zwei Owner-Konten | AP4, AP6 | MB-022, 050, 056 | Natalia | beobachten |
| R4 | Umsetzung dauert wegen wenig Erfahrung mit PHP und Datenbanken länger (**kritisch**) | hoch | mittel | Vermindern: Einarbeitung in AP3, kein Framework | AP3, AP4 | MB-003, 004 | Ardita | beobachten |
| R5 | Anforderungen sind unklar | mittel | mittel | Vermeiden: Annahmen bis M2 per E-Mail mit dem Auftraggeber klären | AP2 | MB-077 | Ardita | beobachten |
| R6 | Wir wollen zu viel einbauen | mittel | mittel | Akzeptieren: MoSCoW-Liste, Kann-Karten erst ganz am Ende | AP5 | MB-06x | Ardita | beobachten |
| R7 | Zeitmangel durch Klausuren und Praxisphase (**kritisch**) | hoch | mittel | Vermindern: Termine früh eintragen und einplanen; Feature-Freeze 04.11. | Risikopuffer | MB-071 | Ardita | beobachten |
| R8 | Restrisiko: Auftraggeber verlangt doch Java statt PHP | niedrig | hoch | Akzeptieren: PHP ist mit dem Betreuer abgestimmt, Bestätigung bis M2 per E-Mail | AP3, AP4 | MB-077 | Ardita | beobachten |
| R9 | Änderungen gehen bei Git verloren oder es entstehen Merge-Konflikte | mittel | mittel | Vermindern: Feature-Branch je Karte, kleine Commits, gemeinsame Git-Einführung in AP3 | AP3, AP4 | MB-001 | Sinem | beobachten |
| R10 | KI-Tools fallen aus oder das Nutzungslimit ist erreicht | mittel | mittel | Vermindern: Claude und ChatGPT als Alternative, Arbeit nicht auf den letzten Tag legen | AP4, AP5 | MB-003 | alle | beobachten |

Wenn ein Risiko Mehraufwand verursacht, nutzen wir zuerst den Risikopuffer (1.060 €, 15 %) und streichen danach
Kann- und Soll-Karten der Owner-Sicht.
