-- Testdaten (erst NACH schema.sql importieren).
-- Passwörter sind nur als bcrypt-Hash gespeichert (erzeugt mit password_hash()).
-- Die Demo-Zugänge (nur für Entwicklung) stehen in der README.md.

USE budget_malia;

-- Vorhandene Testdaten entfernen, damit der Import wiederholbar ist
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ist_wert_korrekturen;
TRUNCATE TABLE forecasts;
TRUNCATE TABLE ist_werte;
TRUNCATE TABLE budgets;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Benutzer (ids 1-5)
INSERT INTO users (id, username, password_hash, role) VALUES
  (1, 'admin',     '$2y$10$bg6ze/cMdlfvE8eFecFLH.HE66giov0zHv9BstZEZkLchb.7CwZ1C', 'ADMIN'),
  (2, 'finance',   '$2y$10$BKmHUfhzs5YpUv/d.9c0L.ObNV9li.vur/kJaSuyOfFqbv1or8Ek6', 'FINANCE'),
  (3, 'cafe',      '$2y$10$tWKA91f47b5Ut5pi8RI3Ie7rByGhdGYyR5aUZ1H46iI46t96C5Txm', 'OWNER'),
  (4, 'marketing', '$2y$10$LiYU8PNPxYTkPNEjzT.BPe16c0j7E.JSLy.jxwzODbqzXxdXk33wq',   'OWNER'),
    (5, 'studio', '$2y$10$wQ2172KWMxEWOnUrQlmqHuBp.SQBIN3AkpWWtKJ7k/W4C7XT2xob6', 'OWNER');

  -- Budgets, angelegt von finance (id 2).
-- Gedacht für verschiedene Status (Stand Herbst 2026):
--   Wareneinkauf: im Rahmen | Café-Ausstattung: Warnung (>80 %)
--   Social Media: überschritten | Messe 2025: abgelaufen
--   Studio: Gerätebeschaffung im Rahmen (49 %) | Wartung und Reparatur: Warnung (84 %)
--           Weiterbildung Trainerinnen 2025: abgelaufen
INSERT INTO budgets (id, name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id, erstellt_von) VALUES
  (1, 'Wareneinkauf Café 2026',    'Café',      5000.00, '2026-01-01', '2026-12-31', 3, 2),
  (2, 'Café-Ausstattung 2026',     'Café',      2000.00, '2026-01-01', '2026-12-31', 3, 2),
  (3, 'Social-Media-Werbung 2026', 'Marketing', 1500.00, '2026-01-01', '2026-12-31', 4, 2),
  (4, 'Messe 2025',                'Marketing', 3000.00, '2025-01-01', '2025-12-31', 4, 2),
  (5, 'Gerätebeschaffung 2026',          'Studio', 8000.00, '2026-01-01', '2026-12-31', 5, 2),
  (6, 'Wartung und Reparatur 2026',      'Studio', 3000.00, '2026-01-01', '2026-12-31', 5, 2),
  (7, 'Weiterbildung Trainerinnen 2025', 'Studio', 4000.00, '2025-01-01', '2025-12-31', 5, 2);

-- Ist-Werte (Ausgaben)
INSERT INTO ist_werte (id, budget_id, betrag, beschreibung, datum, erfasst_von) VALUES
  (1,  1, 1200.00, 'Kaffeebohnen und Milch Q1',   '2026-03-15', 3),
  (2,  1,  950.50, 'Backwaren und Obst Q2',       '2026-06-20', 3),
  (3,  2, 1100.00, 'Espressomaschine',            '2026-02-10', 3),
  (4,  2,  620.00, 'Tische und Stühle Terrasse',  '2026-05-05', 3),
  (5,  3,  900.00, 'Instagram-Kampagne Frühling', '2026-04-01', 4),
  (6,  3,  780.00, 'Influencer-Kooperation',      '2026-07-12', 4),
  (7,  4, 2400.00, 'Standmiete Gesundheitsmesse', '2025-09-10', 4),
  -- Studio: Gerätebeschaffung (Summe 3.900,00 = 49 %)
  (8,  5, 1800.00, 'Pilates-Reformer (2 Stück)',  '2026-02-03', 5),
  (9,  5,  950.00, 'Cadillac-Zubehör',            '2026-04-18', 5),
  (10, 5,  680.00, 'Matten und Kleingeräte',      '2026-06-09', 5),
  (11, 5,  470.00, 'Spiegel für Kursraum',        '2026-08-25', 5),
  -- Studio: Wartung und Reparatur (Summe 2.520,00 = 84 %)
  (12, 6,  480.00, 'Jahreswartung Reformer',      '2026-01-20', 5),
  (13, 6,  350.00, 'Reparatur Klimaanlage',       '2026-03-14', 5),
  (14, 6,  620.50, 'Austausch Federn und Seile',  '2026-05-22', 5),
  (15, 6,  490.00, 'Bodenreparatur Kursraum',     '2026-07-30', 5),
  (16, 6,  579.50, 'Wartung Lüftung',             '2026-09-16', 5),
  -- Studio: Weiterbildung Trainerinnen 2025, abgelaufen (Summe 3.600,00 = 90 %)
  (17, 7, 1200.00, 'Ausbildung Reformer Level 2', '2025-03-12', 5),
  (18, 7,  850.00, 'Workshop Rückengesundheit',   '2025-05-27', 5),
  (19, 7,  950.00, 'Pre- und Postnatal-Kurs',     '2025-08-19', 5),
  (20, 7,  600.00, 'Erste-Hilfe-Auffrischung',    '2025-10-08', 5);

-- Beispiel einer Korrektur: Ist-Wert 3 war zuerst 1.010,00 EUR am 08.02.
-- Der Altwert wird mit Datum und Grund gespeichert, ist_werte enthält den neuen Wert.
INSERT INTO ist_wert_korrekturen (ist_wert_id, alter_betrag, altes_datum, grund, korrigiert_von, korrigiert_am) VALUES
  (3, 1010.00, '2026-02-08', 'Rechnung enthielt zusätzliche Lieferkosten', 3, '2026-02-12 09:30:00');

-- Forecasts (erwartete Ausgaben)
INSERT INTO forecasts (id, budget_id, betrag, beschreibung, datum, erfasst_von) VALUES
  (1, 1, 1500.00, 'Wareneinkauf Q4',             '2026-11-15', 3),
  (2, 2,  400.00, 'Neue Kaffeemühle',            '2026-11-30', 3),
  (3, 3,  500.00, 'Weihnachtskampagne',          '2026-11-20', 4),
  (4, 3,  300.00, 'Flyer-Druck Jahresabschluss', '2026-12-05', 4),
  (5, 5, 2500.00, 'Zusätzliche Reformer im Herbst', '2026-11-10', 5),
  (6, 6,  300.00, 'Wartung Beleuchtung',            '2026-11-25', 5),
  (7, 7,  400.00, 'Fortbildung Schwangerenyoga',    '2025-12-10', 5);
