-- Maliá Budget – Demo-Daten
-- Setzt database/schema.sql voraus (frische, leere Tabellen).
--
-- Demo-Passwort für ALLE Benutzer: "demo1234"
-- (Hash erzeugt mit password_hash('demo1234', PASSWORD_DEFAULT) auf PHP 8.2 -
-- nur für lokale Demo/Entwicklung, kein echtes Sicherheitsrisiko.)

USE malia_budget;

INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle) VALUES
    ('admin',     '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Admin',                      'ADMIN'),
    ('finance',   '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Inhaberin',                  'FINANCE'),
    ('studio',    '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Studio-Leitung',             'OWNER'),
    ('cafe',      '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Café-Leitung',               'OWNER'),
    ('marketing', '$2y$10$1a6wpJtmvlyhAjrEJL4.LOwdwr13ehZ9K5YPN6b/e6kelaE42421S', 'Marketing-Leitung',          'OWNER');

-- Budgets gemäß konzept.md Abschnitt 5 ("Demo-Daten (Maliá)").
-- Zeiträume und Ist-Buchungen sind so gewählt, dass jeder Status mindestens
-- einmal vorkommt (konzept.md: "Die Daten so wählen, dass jeder Status
-- mindestens einmal vorkommt"). Bezugsdatum "heute": 22.10.2026.

INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id) VALUES
    -- Status "Im Rahmen" (Verbrauch deutlich < 80 %)
    ('Reformer-Wartung 2026',          'STUDIO',    4800.00, '2026-01-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Status "Warnung" über Verbrauch % (>= 80 %)
    ('Kursmaterial & Kleingeräte',     'STUDIO',    2500.00, '2026-01-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Status "Warnung" über Hochrechnung (Ist + offener Forecast > Soll),
    -- obwohl der bisherige Verbrauch allein niedrig ist
    ('Trainer-Fortbildung',            'STUDIO',    3000.00, '2026-01-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Status "Überschritten" (Ist > Soll)
    ('Café-Wareneinkauf Q4',           'CAFE',     12000.00, '2026-10-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'cafe')),

    -- Status "Im Rahmen"
    ('Café-Ausstattung Siebträger',    'CAFE',      6500.00, '2026-01-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'cafe')),

    -- Status "Im Rahmen"
    ('Social Media & Fotoshooting',    'MARKETING', 2000.00, '2026-01-01', '2026-12-31',
        (SELECT id FROM benutzer WHERE username = 'marketing')),

    -- Status "Abgelaufen" (gueltig_bis liegt vor "heute" 22.10.2026)
    ('Frühlings-Event',                'MARKETING', 1500.00, '2026-03-01', '2026-05-31',
        (SELECT id FROM benutzer WHERE username = 'marketing'));

INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von) VALUES
    -- Reformer-Wartung: 2.000 / 4.800 = 41,7 % -> Im Rahmen
    ((SELECT id FROM budget WHERE name = 'Reformer-Wartung 2026'),
        2000.00, '2026-06-15', 'Wartung Reformer-Geräte Q2',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Kursmaterial: 2.100 / 2.500 = 84 % -> Warnung
    ((SELECT id FROM budget WHERE name = 'Kursmaterial & Kleingeräte'),
        2100.00, '2026-09-01', 'Neue Matten und Kleingeräte',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Trainer-Fortbildung: nur 500 Ist bisher, Forecast treibt die Warnung
    ((SELECT id FROM budget WHERE name = 'Trainer-Fortbildung'),
        500.00, '2026-04-10', 'Fortbildung Pilates-Trainerin',
        (SELECT id FROM benutzer WHERE username = 'studio')),

    -- Café-Wareneinkauf: 13.000 > 12.000 Soll -> Überschritten
    ((SELECT id FROM budget WHERE name = 'Café-Wareneinkauf Q4'),
        13000.00, '2026-10-15', 'Kaffeebohnen und Milchprodukte Oktober',
        (SELECT id FROM benutzer WHERE username = 'cafe')),

    -- Café-Ausstattung: 1.000 / 6.500 = 15 % -> Im Rahmen
    ((SELECT id FROM budget WHERE name = 'Café-Ausstattung Siebträger'),
        1000.00, '2026-02-20', 'Anzahlung Siebträgermaschine',
        (SELECT id FROM benutzer WHERE username = 'cafe')),

    -- Social Media: 300 / 2.000 = 15 % -> Im Rahmen
    ((SELECT id FROM budget WHERE name = 'Social Media & Fotoshooting'),
        300.00, '2026-03-05', 'Fotoshooting Frühlingskampagne',
        (SELECT id FROM benutzer WHERE username = 'marketing')),

    -- Frühlings-Event: Buchung existiert, Status ist trotzdem "Abgelaufen"
    -- (Verfallsdatum sticht alle anderen Regeln, siehe konzept.md Abschnitt 4)
    ((SELECT id FROM budget WHERE name = 'Frühlings-Event'),
        1400.00, '2026-04-20', 'Deko und Give-aways Frühlings-Event',
        (SELECT id FROM benutzer WHERE username = 'marketing'));

-- Beispiel für eine korrigierte Ist-Buchung (konzept.md Abschnitt 3 / E-006):
-- ursprünglicher Betrag, Datum und Grund bleiben nachvollziehbar.
UPDATE ist_buchung
SET betrag = 2100.00,
    urspruenglicher_betrag = 1900.00,
    korrigiert_am = '2026-09-02 09:15:00',
    korrektur_grund = 'Rechnung nachträglich korrigiert, Lieferant hatte Rabatt vergessen'
WHERE budget_id = (SELECT id FROM budget WHERE name = 'Kursmaterial & Kleingeräte')
  AND beschreibung = 'Neue Matten und Kleingeräte';

-- Forecast für die Trainer-Fortbildung: Nov + Dez zusammen 2.700 €, zusammen
-- mit den bisherigen 500 € Ist ergibt das eine Hochrechnung von 3.200 € >
-- 3.000 € Soll -> Status "Warnung" trotz niedrigem bisherigen Verbrauch.
INSERT INTO forecast (budget_id, monat, betrag) VALUES
    ((SELECT id FROM budget WHERE name = 'Trainer-Fortbildung'), '2026-11-01', 1500.00),
    ((SELECT id FROM budget WHERE name = 'Trainer-Fortbildung'), '2026-12-01', 1200.00);
