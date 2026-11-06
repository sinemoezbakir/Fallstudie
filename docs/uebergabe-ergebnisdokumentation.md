# Übergabe: Ergebnisdokumentation (Abgabe 22.11.2026)

Diese Datei fasst zusammen, was für die Ergebnisdokumentation (M6) gebraucht wird und was nicht im
restlichen Repo steht. Sie ist die Arbeitsgrundlage für das Schreiben der Ergebnisdokumentation,
auch mit KI-Unterstützung. Stand: 06.11.2026, Tag der Präsentation.

## 1. Vorgaben des Dozenten

- Umfang grob **10–15 Seiten**, Abgabe als **PDF** in Moodle bis **22.11.2026, 23:59 UTC**
- Gewicht: **40 von 100 Punkten** (Projektplan 25, Zwischenbericht 25, Präsentation 10)
- Inhalte laut Folien „Ergebnisdokumentation I + II“:
  - Organisation des Projekts (finale Orga zeigen)
  - Zusammenfassung (Abstract, kurzer Rückblick)
  - Ausgangssituation und ursprüngliche Ziele
  - Projektverlauf (Phasen und Meilensteine, Änderungen im Plan)
  - Ergebnisse (Konzepte, Prototypen, Ergebnis)
  - Probleme und gefundene Lösungswege
  - Ressourcen- und Budgeteinsatz (Soll-Ist-Vergleich: Soll-Ist-Rechnung, Ist-Aufwände)
  - Review: Was lief gut? Wo sehen wir noch Potential? Was haben wir gelernt?
  - Alle offenen Liefergegenstände aus dem Projektplan
- Hinweis des Dozenten: Es geht um die Methode, nicht um das perfekte Dokument. Der Scope darf
  in Rücksprache mit dem Auftraggeber angepasst werden.

## 2. Quellen

| Quelle | Wo |
|---|---|
| Projektplan (abgegeben 04.10., Note 1) | nicht im Repo, PDF beim Team – **im Chat hochladen** |
| Zwischenbericht (abgegeben 25.10.) | nicht im Repo, PDF beim Team – **im Chat hochladen** |
| Abschlusspräsentation (06.11.) | nicht im Repo, PDF beim Team – **im Chat hochladen** |
| Aufgabenstellung des Dozenten | nicht im Repo, PDF beim Team – bei Bedarf hochladen |
| Entscheidungen E-001 bis E-029 | [entscheidungen.md](entscheidungen.md) |
| Fachkonzept, Statusregeln | [konzept.md](konzept.md) |
| Risiken | [risiken.md](risiken.md) |
| Arbeitsprotokoll | [arbeitsprotokoll.md](arbeitsprotokoll.md) |
| Stand 22.10. | [stand-22-10.md](stand-22-10.md) |
| Kanban, Arbeitspakete, Soll-Stunden | [../kanban/README.md](../kanban/README.md) und GitHub Project |
| Screenshots | [screenshots/](screenshots/) |
| Code, Tests | `src/`, `public/`, `tests/` (PHPUnit + Systemtest `tests/rundgang.php`), Git-Historie |

## 3. Was seit dem Zwischenbericht passiert ist

- **M4 (01.11.)**: Owner-Sicht fertig; Merge-Stand siehe Git-Historie (Branch `ardita.new.app`).
- **Feature-Freeze 04.11.**; danach bis 05.11. noch inhaltliche Widersprüche behoben, ohne neue
  Funktion (E-023 bis E-028): Owner und Inhaberin sahen für dasselbe Budget verschiedene
  Risikozahlen. Ergebnis: eine zentrale Risikoregel „Risiko = erwartete Überschreitung je Budget
  laut Hochrechnung, nie mit Puffern anderer Budgets verrechnet“ (E-028).
- **Generalprobe 05.11.** Die Demo wurde am 05.11. abends automatisiert gegen die laufende App
  geprüft; alle Zahlen stimmten (siehe 4).
- **E-029 (05.11.)**: Begründung von E-022 korrigiert. Der abgegebene Projektplan nennt die
  Bereiche Studio (Kurse, Geräte, Wartung, Weiterbildung), Café und Marketing. Umgesetzt sind
  Kurse, Geräte, Marketing; das Café ist nicht umgesetzt und wurde nicht mit dem Auftraggeber
  abgestimmt. In der Ergebnisdokumentation offen als Abweichung vom Plan nennen.
- **M5 (06.11.)**: Präsentation und Abnahme. **Ergebnis der Abnahme vom Team nachtragen.**

## 4. Ergebnis: das Produkt in einem Satz

Maliá Budget ist ein Frühwarnsystem für Budgetrisiken: Es zeigt heute, welches Budget am Ende
überzogen sein wird, solange die Owner es noch ändern können (Hochrechnung = Ist + offener Forecast).

Demo-Fall aus den Demodaten (am 05.11. in der laufenden App bestätigt):

| | Wert |
|---|---|
| Budget Großgeräte-Beschaffung Q4 | 12.000 € |
| Ist (Oktober) | 13.000 €, also 1.000 € überschritten |
| Forecast November / Dezember | 3.500 € / 4.000 € |
| Hochrechnung / Risiko | 20.500 € / 8.500 € |
| Nach Verschieben des Dezember-Kaufs | Risiko 4.500 € (4.000 € verhindert) |
| Gesamtrisiko Inhaberin vorher / nachher | 8.750 € / 4.750 € (inkl. 250 € Trainer-Fortbildung) |

Alle Must-Funktionen aus Projektplan 2.2 sind umgesetzt (Login, drei Rollen, Datentrennung,
Benutzerverwaltung, Budgets mit Verfallszeit anlegen und zuweisen, Ist-Werte erfassen und
korrigieren, Soll-Ist-Vergleich, Owner-Dashboard, Detailseite, Forecast, Soll-Ist-Diagramm,
Forecast-Ist-Soll-Vergleich, Eingabeprüfung). Should umgesetzt: CSV-Export. Could umgesetzt:
Filter/Suche, Passwort-vergessen.

Bewusste Grenzen: keine Benachrichtigung per E-Mail (Warnung erscheint beim Öffnen der Seite),
keine Buchhaltungsanbindung, Frühwarnung nur so gut wie die Planung der Owner, keine
automatische Verrechnung zwischen Budgets.

## 5. Präsentation am 06.11. (für Abschnitt Projektverlauf)

10 Folien, ca. 13 Minuten, als Verkaufsgespräch an den Inhaber aufgebaut, roter Faden
„1.000 € sind passiert. 7.500 € sind noch Plan. Und Pläne kann man ändern.“ Live-Demo aus
Owner-Sicht (Forecast ändern, Risiko sinkt), danach drei Klicks zu den Grundfunktionen,
Business Case aus Projektplan 1.3 (rund 15 h und netto ca. 340 € pro Monat gespart,
Amortisation nach ca. 2 Jahren), Architektur als Problem → Entscheidung → Nutzen.

## 6. Vom Team noch zu liefern (nicht erfinden)

- [ ] **Finale Ist-Stunden je Person** (Ardita, Sinem, Natalia) bis Projektende und damit der
      finale Soll-Ist-Vergleich bei Stunden und Budget (Plan: 135 h / 8.125 €; Prognose im
      Zwischenbericht: 140 h, Budget eingehalten, 250 € Puffer genutzt)
- [ ] **Ergebnis der Abnahme** am 06.11. und Rückmeldung des Dozenten
- [ ] **Abschließende Retrospektive** (Start-Stop-Continue) und Lessons Learned
- [ ] **KI-Nutzung** ehrlich beschreiben: wofür (Code, Texte, Präsentation, Prüfung) und wie
      geprüft. Projektplan und Zwischenbericht enthalten dazu bereits Hinweise; Commits ab
      05.11. tragen „Co-Authored-By: Claude“.
- [ ] Aktualisierte Risikomatrix und Stakeholderanalyse zum Projektende

## 7. Regeln für das Schreiben

- Zahlen, Termine und Bewertungen nur aus den Quellen oder vom Team, nie schätzen.
- Abweichungen vom Plan offen nennen (M2/M3 verfehlt, Café, Ist-Stunden nachträglich rekonstruiert).
- Am Ende einen Hinweis zur KI-Nutzung, wie in Projektplan und Zwischenbericht.
