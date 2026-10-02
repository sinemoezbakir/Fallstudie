# Git-Workflow

Ziel: Aus Board und Git-Historie lässt sich jederzeit ablesen, **wer wann was** gemacht hat.
Board: https://github.com/users/sinemoezbakir/projects/3

## Einmalig einrichten

```bash
git config user.name  "Vorname Nachname"   # echter Name, damit git log lesbar ist
git config user.email "name@example.com"
```

## Ablauf pro Karte (Beispiel MB-034 = Issue #6)

1. Im Board die Karte aus **Todo** nach **In Arbeit** ziehen, sich als Assignee eintragen, „Gestartet“ im Issue ausfüllen.
2. Branch anlegen: `git switch -c feature/MB-034-ist-erfassen`
3. Klein und oft committen, **jede Commit-Nachricht beginnt mit der Karten-ID und endet mit der Issue-Nummer**:
   `MB-034: Formular für Ist-Werte mit Validierung (#6)`
   → GitHub zeigt den Commit dann automatisch im Issue an.
4. Branch pushen (`git push -u origin feature/MB-034-ist-erfassen`) und auf GitHub einen **Pull Request** öffnen.
   In die Beschreibung `Closes #6` schreiben, dazu kurz: was wurde gemacht, wie testet man es, Screenshot.
5. Karte nach **Review** ziehen, der anderen Person Bescheid geben.
6. Die **andere Person** reviewt im Pull Request (Code lesen, lokal testen, Akzeptanzkriterien abhaken) und klickt *Approve*.
7. Pull Request mergen → das Issue wird durch `Closes #6` automatisch geschlossen. Karte nach **Fertig** ziehen.

Der Pull Request ist der Nachweis für das Review: Wer hat geprüft, welche Anmerkungen gab es, wann wurde gemergt.

## Regeln

- Nie direkt auf `main` programmieren – nur Doku-Änderungen dürfen direkt auf `main` (z. B. Ardita: Protokolle, Risiken, Abgaben).
- Vor dem Arbeiten: `git pull` auf `main`, danach den eigenen Branch aktualisieren (`git merge main`).
- Keine Passwörter committen.
- Änderung an Tabellen: `database/schema.sql` anpassen und der anderen Person Bescheid geben (sie muss neu importieren).

## Nützliche Befehle und Ansichten für die Doku

```bash
git log --oneline --author="Sinem"     # alles von einer Person
git log --oneline --grep="MB-034"      # alle Commits zu einer Karte
git shortlog -sn                       # Commits pro Person
```

- **Issue öffnen** → Timeline zeigt jeden Statuswechsel, Commit, Kommentar und Pull Request mit Datum und Person
- **Issues → Milestones** → Fortschritt je Phase in Prozent
- **Insights → Contributors** → Beiträge pro Person als Diagramm
- Board-Ansicht **Zeitplan** → Screenshot für Projektplan und Berichte
