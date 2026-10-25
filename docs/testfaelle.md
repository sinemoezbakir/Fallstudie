# Manuelle Testfälle (Systemtest)

Voraussetzung: Anwendung läuft, Seed importiert (Logins siehe [README](../README.md)).
Spalte „OK“ beim Testen abhaken.

## Login und Session
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 1 | Mit richtigen Daten anmelden | Weiterleitung zur Startseite der Rolle | ☐ |
| 2 | Falsches Passwort / unbekannter Benutzer | Gleiche allgemeine Fehlermeldung | ☐ |
| 3 | Leeres Formular absenden | Wird abgelehnt | ☐ |
| 4 | „Abmelden“, dann Zurück-Taste oder Neuladen | Geschützte Seiten führen zum Login | ☐ |
| 5 | Session-Cookie in den Entwicklertools ansehen | `HttpOnly` und `SameSite` gesetzt | ☐ |
| 6 | `/` ohne Login aufrufen | Weiterleitung zum Login | ☐ |

## Admin
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 7 | Benutzer mit gültigen Daten anlegen | In der Liste, Login funktioniert | ☐ |
| 8 | Doppelter Benutzername, Passwort < 8 oder > 72 Zeichen, Sonderzeichen im Namen | Fehlermeldung, nichts gespeichert | ☐ |
| 9 | Rolle eines anderen Benutzers ändern | Gilt beim nächsten Laden dieses Benutzers | ☐ |
| 10 | Eigene Rolle ändern | Wird verhindert | ☐ |
| 11 | `/finance/overview.php` und `/owner/index.php` aufrufen | 403-Seite, keine Budgetdaten | ☐ |
| 12 | Benutzerliste ansehen | Kein Passwort-Hash sichtbar | ☐ |

## Finance
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 13 | Budget mit gültigen Daten anlegen | Erscheint in der Tabelle | ☐ |
| 14 | Soll ≤ 0, `1e2`, 1.000,50, Ende ≤ Beginn, leerer Name | Fehlermeldung | ☐ |
| 15 | Budget einem anderen Owner zuweisen | Neuer Owner sieht es, alter nicht mehr | ☐ |
| 16 | Soll-Ist-Vergleich | Soll, Ist, Differenz, Auslastung stimmen mit Handrechnung | ☐ |
| 17 | Owner-Auswahl ansehen | Nur Benutzer mit Rolle OWNER | ☐ |
| 18 | `/admin/users.php` und `/owner/index.php` aufrufen | 403-Seite | ☐ |

## Owner: Ausgaben, Korrektur, Forecast
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 19 | Ausgabe erfassen (auch `49,90`) | Gespeichert, Ist, Rest und Auslastung ändern sich | ☐ |
| 20 | Betrag 0, negativ, Text, `1e2`, leere Beschreibung, ungültiges Datum | Fehlermeldung | ☐ |
| 21 | Datum vor Beginn oder nach Ende der Gültigkeit | Fehlermeldung | ☐ |
| 22 | Ausgabe korrigieren (neuer Betrag + Grund) | Betrag geändert; Zeile in `ist_wert_korrekturen` mit altem Betrag, Datum, Grund, Benutzer, Zeitpunkt | ☐ |
| 23 | Korrektur ohne Grund / mit gleichem Betrag | Fehlermeldung | ☐ |
| 24 | Forecast erfassen | Gespeichert, Prognose (Ist + Forecast) ändert sich | ☐ |

## Status, Diagramme, Export
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 25 | Seed-Budgets ansehen | Im Rahmen grün, Warnung gelb, Überschritten rot, Abgelaufen grau | ☐ |
| 26 | Ist auf genau 80 % bzw. 100 % bringen; dann 1 Cent mehr | 80 % und 100 %: Warnung; 100 % + 1 Cent: Überschritten | ☐ |
| 27 | Detailseite öffnen | Zwei Diagramme, Achse in Euro, Farben passen zum Status | ☐ |
| 28 | Daten erfassen, Seite neu laden | Kennzahlen, Tabelle und Diagramme sind konsistent | ☐ |
| 29 | Ohne Internet öffnen | Diagramme fehlen (CDN), Rest funktioniert | ☐ |
| 30 | „Als CSV exportieren“, Datei in Excel öffnen | Umlaute korrekt, Spalten getrennt, Beträge als Zahlen | ☐ |
| 31 | Beschreibung `=1+1` erfassen, exportieren | In Excel als Text, nicht als Formel | ☐ |

## Sicherheit und Robustheit
| # | Aktion | Erwartung | OK |
|---|---|---|---|
| 32 | Als Owner `owner/budget.php?id=` mit fremder Budget-ID | 403-Seite, keine fremden Daten | ☐ |
| 33 | Ebenso `owner/export.php?id=` mit fremder ID | 403-Seite, kein CSV | ☐ |
| 34 | Als Owner `/admin/users.php` und `/finance/budgets.php` | 403-Seite | ☐ |
| 35 | Ohne Login geschützte Seiten aufrufen | Weiterleitung zum Login | ☐ |
| 36 | `<script>alert(1)</script>` als Name oder Beschreibung | Wird als Text angezeigt, auch im Diagramm, kein Skript läuft | ☐ |
| 37 | Formular ohne oder mit manipuliertem CSRF-Token (Entwicklertools) | „Ungültige Anfrage“ | ☐ |
| 38 | `ist_wert_id` im Korrekturformular auf eine fremde ID ändern | „Ausgabe nicht gefunden“, nichts geändert | ☐ |
| 39 | Zwei Browser, zwei Owner gleichzeitig | Jeder sieht nur seine Daten | ☐ |
| 40 | SQL-Injection im Login (`' OR '1'='1`, `admin'--`) | Anmeldung schlägt fehl | ☐ |
| 41 | MariaDB beenden, Seite laden | Allgemeine 500-Seite ohne Pfade oder Zugangsdaten | ☐ |
| 42 | Demo-Modus aus (`demo_rollenwechsel` = false), `/wechsel.php` aufrufen | 404-Seite, kein Dropdown in der Navigation | ☐ |
