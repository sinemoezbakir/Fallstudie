-- Maliá Budget – Demo-Daten
-- Setzt database/schema.sql voraus (frische, leere Tabellen).
-- Im Demo-Modus spielt "Demo-Daten zurücksetzen" (Kontomenü) genau diese
-- Datei neu ein (src/demo.php).
--
-- Demo-Passwort für ALLE Benutzer: "demo1234"
-- (Hash erzeugt mit password_hash('demo1234', PASSWORD_DEFAULT) auf PHP 8.2 -
-- nur für lokale Demo/Entwicklung, kein echtes Sicherheitsrisiko.)
--
-- Wichtig für den Demo-Reset: jede Anweisung endet mit ";" am Zeilenende,
-- und in Texten kommt kein ";" vor.

USE malia_budget;

INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle) VALUES
    ('admin',     '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Admin',             'ADMIN'),
    ('finance',   '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Inhaberin',         'FINANCE'),
    ('studio',    '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Studio-Leitung',    'OWNER'),
    ('cafe',      '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Café-Leitung',      'OWNER'),
    ('marketing', '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Marketing-Leitung', 'OWNER');

SET @studio    = (SELECT id FROM benutzer WHERE username = 'studio');
SET @cafe      = (SELECT id FROM benutzer WHERE username = 'cafe');
SET @marketing = (SELECT id FROM benutzer WHERE username = 'marketing');

-- Budgets gemäß konzept.md Abschnitt 5 ("Demo-Daten (Maliá)").
-- Zeiträume, Ist-Buchungen und Forecasts sind so gewählt, dass jeder Status
-- mindestens einmal vorkommt. Bezugsdatum: Ende Oktober / Anfang November 2026
-- (Sprechstunde 23.10., Präsentation 06.11.). Alle Buchungen liegen vor dem 23.10.

INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id) VALUES
    ('Reformer-Wartung 2026',        'STUDIO',     4800.00, '2026-01-01', '2026-12-31', @studio),
    ('Kursmaterial & Kleingeräte',   'STUDIO',     2500.00, '2026-01-01', '2026-12-31', @studio),
    ('Trainer-Fortbildung',          'STUDIO',     3000.00, '2026-01-01', '2026-12-31', @studio),
    ('Herbst-Workshop-Reihe',        'STUDIO',     1800.00, '2026-09-01', '2026-11-15', @studio),
    ('Café-Wareneinkauf Q4',         'CAFE',      12000.00, '2026-10-01', '2026-12-31', @cafe),
    ('Café-Ausstattung Siebträger',  'CAFE',       6500.00, '2026-01-01', '2026-12-31', @cafe),
    ('Social Media & Fotoshooting',  'MARKETING',  2000.00, '2026-01-01', '2026-12-31', @marketing),
    ('Frühlings-Event',              'MARKETING',  1500.00, '2026-03-01', '2026-05-31', @marketing);

SET @reformer   = (SELECT id FROM budget WHERE name = 'Reformer-Wartung 2026');
SET @kurs       = (SELECT id FROM budget WHERE name = 'Kursmaterial & Kleingeräte');
SET @trainer    = (SELECT id FROM budget WHERE name = 'Trainer-Fortbildung');
SET @workshop   = (SELECT id FROM budget WHERE name = 'Herbst-Workshop-Reihe');
SET @waren      = (SELECT id FROM budget WHERE name = 'Café-Wareneinkauf Q4');
SET @siebtraeger = (SELECT id FROM budget WHERE name = 'Café-Ausstattung Siebträger');
SET @social     = (SELECT id FROM budget WHERE name = 'Social Media & Fotoshooting');
SET @fruehling  = (SELECT id FROM budget WHERE name = 'Frühlings-Event');

INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von, erfasst_am) VALUES
    -- Reformer-Wartung: 2.730 / 4.800 = 56,9 %, Hochrechnung 3.730 -> Im Rahmen
    (@reformer,  450.00, '2026-02-12', 'Wartung Reformer 1–4 (Federn, Seile)',        @studio, '2026-02-12 17:20:00'),
    (@reformer,  380.00, '2026-04-08', 'Ersatz-Schlaufen und Polster',                @studio, '2026-04-08 09:05:00'),
    (@reformer, 1170.00, '2026-06-15', 'Wartung Reformer-Geräte Q2 inkl. Anfahrt',    @studio, '2026-06-15 18:40:00'),
    (@reformer,  420.00, '2026-08-20', 'Rollen und Gleitschienen getauscht',          @studio, '2026-08-21 08:15:00'),
    (@reformer,  310.00, '2026-10-06', 'Sicherheitscheck Herbst',                     @studio, '2026-10-06 12:30:00'),

    -- Kursmaterial: 2.100 / 2.500 = 84 % -> Warnung (Verbrauch >= 80 %)
    (@kurs,  180.00, '2026-01-19', 'Pilates-Bälle und Ringe',                         @studio, '2026-01-19 10:00:00'),
    (@kurs,  140.00, '2026-03-10', 'Therabänder (20 Stück)',                          @studio, '2026-03-10 16:45:00'),
    (@kurs, 1780.00, '2026-09-01', 'Neue Matten und Kleingeräte',                     @studio, '2026-09-01 11:10:00'),

    -- Trainer-Fortbildung: nur 850 Ist (28 %), aber Forecast treibt die
    -- Hochrechnung auf 3.250 > 3.000 Soll -> Warnung
    (@trainer, 500.00, '2026-04-10', 'Fortbildung Pilates-Trainerin (Matwork)',       @studio, '2026-04-10 14:00:00'),
    (@trainer, 350.00, '2026-07-03', 'Workshop Faszientraining',                      @studio, '2026-07-03 09:30:00'),

    -- Herbst-Workshop-Reihe: 900 / 1.800 = 50 % -> Im Rahmen, läuft aber
    -- am 15.11. ab (Hinweis "läuft bald ab" im Dashboard)
    (@workshop, 520.00, '2026-09-12', 'Gast-Trainerin Workshop „Rücken & Haltung“',   @studio, '2026-09-12 19:00:00'),
    (@workshop, 380.00, '2026-10-10', 'Raummiete und Material Workshop 2',            @studio, '2026-10-10 13:25:00'),

    -- Café-Wareneinkauf: 13.000 > 12.000 Soll -> Überschritten
    (@waren, 3200.00, '2026-10-01', 'Kaffeebohnen Rösterei (Röstung Oktober)',        @cafe, '2026-10-01 08:30:00'),
    (@waren, 2450.00, '2026-10-05', 'Milch und Hafermilch',                           @cafe, '2026-10-05 07:50:00'),
    (@waren, 2800.00, '2026-10-09', 'Kuchen und Backwaren Konditorei',                @cafe, '2026-10-09 15:10:00'),
    (@waren, 1650.00, '2026-10-14', 'Tee, Sirup und Matcha',                          @cafe, '2026-10-14 10:20:00'),
    (@waren, 2900.00, '2026-10-20', 'Kaffeebohnen Nachbestellung (Herbstaktion)',     @cafe, '2026-10-20 09:00:00'),

    -- Café-Ausstattung: 2.270 / 6.500 = 34,9 %, Hochrechnung 4.770 -> Im Rahmen
    (@siebtraeger, 1000.00, '2026-02-20', 'Anzahlung Siebträgermaschine',             @cafe, '2026-02-20 11:00:00'),
    (@siebtraeger,  850.00, '2026-05-12', 'Kaffeemühle',                              @cafe, '2026-05-12 16:30:00'),
    (@siebtraeger,  420.00, '2026-08-28', 'Barista-Zubehör (Tamper, Kannen)',         @cafe, '2026-08-28 12:45:00'),

    -- Social Media: 950 / 2.000 = 47,5 %, Hochrechnung 1.500 -> Im Rahmen
    (@social, 300.00, '2026-03-05', 'Fotoshooting Frühlingskampagne',                 @marketing, '2026-03-05 18:00:00'),
    (@social, 180.00, '2026-05-14', 'Instagram-Anzeigen Mai',                         @marketing, '2026-05-14 09:40:00'),
    (@social, 220.00, '2026-07-09', 'Instagram-Anzeigen Sommer',                      @marketing, '2026-07-09 10:10:00'),
    (@social, 250.00, '2026-09-22', 'Reels-Produktion Herbstkurse',                   @marketing, '2026-09-22 15:35:00'),

    -- Frühlings-Event: Status "Abgelaufen" (Verfallsdatum sticht alle
    -- anderen Regeln, siehe konzept.md Abschnitt 4)
    (@fruehling,  220.00, '2026-03-18', 'Flyer und Plakate',                          @marketing, '2026-03-18 11:15:00'),
    (@fruehling, 1180.00, '2026-04-20', 'Deko und Give-aways Frühlings-Event',        @marketing, '2026-04-20 17:05:00');

-- Beispiel für eine korrigierte Ist-Buchung (konzept.md Abschnitt 3 / E-006):
-- ursprünglicher Betrag, Datum und Grund bleiben nachvollziehbar.
UPDATE ist_buchung
SET urspruenglicher_betrag = 1980.00,
    korrigiert_am = '2026-09-03 10:15:00',
    korrektur_grund = 'Rechnung korrigiert: 10 % Mengenrabatt nachträglich gewährt'
WHERE budget_id = @kurs AND beschreibung = 'Neue Matten und Kleingeräte';

-- Forecasts (pro Budget und Monat genau ein Wert, konzept.md Abschnitt 3)
INSERT INTO forecast (budget_id, monat, betrag) VALUES
    (@reformer,    '2026-11-01',  400.00),
    (@reformer,    '2026-12-01',  600.00),
    (@trainer,     '2026-11-01', 1500.00),
    (@trainer,     '2026-12-01',  900.00),
    (@workshop,    '2026-11-01',  300.00),
    (@waren,       '2026-11-01', 3500.00),
    (@waren,       '2026-12-01', 4000.00),
    (@siebtraeger, '2026-12-01', 2500.00),
    (@social,      '2026-11-01',  300.00),
    (@social,      '2026-12-01',  250.00);
