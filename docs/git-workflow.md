# Git-Workflow

Ziel: Aus der Git-Historie lässt sich jederzeit ablesen, **wer wann was** gemacht hat.

## Einmalig einrichten

```bash
git config user.name  "Vorname Nachname"   # echter Name, damit git log lesbar ist
git config user.email "name@example.com"
```

## Ablauf pro Karte

1. Karte aus `kanban/1-todo/` nehmen, im Kopf **Zuständig** und **Gestartet** eintragen, Zeile im **Verlauf** ergänzen.
2. Karte verschieben und committen:
   ```bash
   git mv kanban/1-todo/MB-034-ist-erfassen.md kanban/2-in-arbeit/
   git commit -m "MB-034: Karte gestartet"
   ```
3. Branch anlegen: `git switch -c feature/MB-034-ist-erfassen`
4. Klein und oft committen, **jede Commit-Nachricht beginnt mit der Karten-ID**:
   `MB-034: Formular für Ist-Werte mit Validierung`
5. Fertig → Karte nach `kanban/3-review/` verschieben, die andere Person Bescheid geben.
6. Die **andere Person** reviewt (Code lesen, lokal testen, Akzeptanzkriterien abhaken) und trägt sich im Verlauf ein.
7. Merge in `main` (`git switch main && git merge feature/…`), Karte nach `kanban/4-fertig/`, pushen.

## Regeln

- Nie direkt auf `main` programmieren – nur Kanban-/Doku-Änderungen dürfen direkt auf `main`.
- Vor dem Arbeiten: `git pull` auf `main`, danach den eigenen Branch aktualisieren (`git merge main`).
- Keine Passwörter committen.
- Änderung an Tabellen: `database/schema.sql` anpassen und der anderen Person Bescheid geben (sie muss neu importieren).

## Nützliche Befehle für die Doku

```bash
git log --oneline --author="Sinem"                  # alles von einer Person
git log --oneline --grep="MB-034"                   # alles zu einer Karte
git log --follow --format="%ad %an %s" --date=short -- "kanban/*/MB-034*"   # Statuswechsel einer Karte
git shortlog -sn                                    # Commits pro Person
```
