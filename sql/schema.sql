-- Schema: Budgetverwaltung "Studio Maliá – Pilates & Café" (MariaDB)
-- Kann beliebig oft importiert werden: vorhandene Tabellen werden vorher gelöscht.

CREATE DATABASE IF NOT EXISTS budget_malia
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE budget_malia;

-- Alte Tabellen löschen (zuerst die, die auf andere verweisen)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ist_wert_korrekturen;
DROP TABLE IF EXISTS forecasts;
DROP TABLE IF EXISTS ist_werte;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS expense_corrections;
DROP TABLE IF EXISTS expenses;
SET FOREIGN_KEY_CHECKS = 1;

-- Benutzer. Die Rollen sind fest (im PHP-Code zusätzlich als Konstanten).
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,            -- nur der Hash, nie das Passwort
  role          ENUM('FINANCE','OWNER','ADMIN') NOT NULL
);

-- Budgets: von Finance angelegt, einem Owner zugewiesen.
-- gueltig_bis ist die Verfallszeit: danach gilt das Budget als "abgelaufen".
CREATE TABLE budgets (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100)  NOT NULL,
  bereich      VARCHAR(50)   NOT NULL,            -- z. B. Studio, Café, Marketing
  soll_betrag  DECIMAL(10,2) NOT NULL,
  gueltig_von  DATE NOT NULL,
  gueltig_bis  DATE NOT NULL,
  owner_id     INT NOT NULL,                      -- zuständiger Owner
  erstellt_von INT NOT NULL,                      -- Finance-Benutzer
  CONSTRAINT fk_budgets_owner   FOREIGN KEY (owner_id)     REFERENCES users(id),
  CONSTRAINT fk_budgets_ersteller FOREIGN KEY (erstellt_von) REFERENCES users(id),
  CONSTRAINT chk_budgets_soll   CHECK (soll_betrag > 0),
  CONSTRAINT chk_budgets_zeit   CHECK (gueltig_bis >= gueltig_von)
);

-- Ist-Werte: tatsächliche Ausgaben, vom Owner erfasst
CREATE TABLE ist_werte (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  budget_id    INT NOT NULL,
  betrag       DECIMAL(10,2) NOT NULL,
  beschreibung VARCHAR(255)  NOT NULL,
  datum        DATE NOT NULL,
  erfasst_von  INT NOT NULL,
  CONSTRAINT fk_ist_budget   FOREIGN KEY (budget_id)   REFERENCES budgets(id),
  CONSTRAINT fk_ist_erfasser FOREIGN KEY (erfasst_von) REFERENCES users(id),
  CONSTRAINT chk_ist_betrag  CHECK (betrag > 0)
);

-- Korrekturen: speichert den ALTEN Stand eines Ist-Werts mit Grund.
-- Der aktuelle Wert steht weiterhin in ist_werte.
CREATE TABLE ist_wert_korrekturen (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  ist_wert_id   INT NOT NULL,
  alter_betrag  DECIMAL(10,2) NOT NULL,
  altes_datum   DATE NOT NULL,
  grund         VARCHAR(255) NOT NULL,
  korrigiert_von INT NOT NULL,
  korrigiert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_korr_ist  FOREIGN KEY (ist_wert_id)    REFERENCES ist_werte(id),
  CONSTRAINT fk_korr_user FOREIGN KEY (korrigiert_von) REFERENCES users(id),
  CONSTRAINT chk_korr_betrag CHECK (alter_betrag > 0)
);

-- Forecasts: erwartete künftige Ausgaben, vom Owner erfasst
CREATE TABLE forecasts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  budget_id    INT NOT NULL,
  betrag       DECIMAL(10,2) NOT NULL,
  beschreibung VARCHAR(255)  NOT NULL,
  datum        DATE NOT NULL,
  erfasst_von  INT NOT NULL,
  CONSTRAINT fk_fc_budget   FOREIGN KEY (budget_id)   REFERENCES budgets(id),
  CONSTRAINT fk_fc_erfasser FOREIGN KEY (erfasst_von) REFERENCES users(id),
  CONSTRAINT chk_fc_betrag  CHECK (betrag > 0)
);
