-- Maliá Budget – Datenbankschema
-- MariaDB (XAMPP), Datenbank "malia_budget" (siehe Entscheidungslog E-003)
--
-- Import: in phpMyAdmin die Datenbank "malia_budget" anlegen, dann diese
-- Datei im Tab "Importieren" einspielen. Bei Schemaänderungen: diese Datei
-- anpassen UND der anderen Entwicklerin Bescheid geben (git-workflow.md).

CREATE DATABASE IF NOT EXISTS malia_budget
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE malia_budget;

-- Reihenfolge wegen Fremdschlüsseln: erst BENUTZER, dann BUDGET, dann die
-- beiden Tabellen, die auf BUDGET verweisen.

DROP TABLE IF EXISTS forecast;
DROP TABLE IF EXISTS ist_buchung;
DROP TABLE IF EXISTS budget;
DROP TABLE IF EXISTS benutzer;

-- Benutzer werden nicht gelöscht, da Ausgaben auf sie verweisen
-- (konzept.md Abschnitt 3) -- daher kein ON DELETE CASCADE auf benutzer_id.

CREATE TABLE benutzer (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    passwort_hash  VARCHAR(255) NOT NULL,
    anzeigename    VARCHAR(100) NOT NULL,
    rolle          ENUM('ADMIN', 'FINANCE', 'OWNER') NOT NULL
) ENGINE = InnoDB;

CREATE TABLE budget (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150)   NOT NULL,
    bereich       ENUM('STUDIO', 'CAFE', 'MARKETING') NOT NULL,
    soll_betrag   DECIMAL(10, 2) NOT NULL,
    gueltig_von   DATE           NOT NULL,
    gueltig_bis   DATE           NOT NULL,
    owner_id      INT UNSIGNED   NOT NULL,

    CONSTRAINT fk_budget_owner
        FOREIGN KEY (owner_id) REFERENCES benutzer (id),

    CONSTRAINT chk_budget_zeitraum
        CHECK (gueltig_von <= gueltig_bis)
) ENGINE = InnoDB;

CREATE TABLE ist_buchung (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id               INT UNSIGNED   NOT NULL,
    betrag                  DECIMAL(10, 2) NOT NULL,
    datum                   DATE           NOT NULL,
    beschreibung            VARCHAR(255)   NOT NULL,
    erfasst_von             INT UNSIGNED   NOT NULL,
    erfasst_am              DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- nur bei Korrektur gefüllt (konzept.md Abschnitt 3 + E-006)
    urspruenglicher_betrag  DECIMAL(10, 2) NULL,
    korrigiert_am           DATETIME       NULL,
    korrektur_grund         VARCHAR(255)   NULL,

    CONSTRAINT fk_buchung_budget
        FOREIGN KEY (budget_id) REFERENCES budget (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_buchung_erfasst_von
        FOREIGN KEY (erfasst_von) REFERENCES benutzer (id)
) ENGINE = InnoDB;

CREATE TABLE forecast (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id   INT UNSIGNED   NOT NULL,
    monat       DATE           NOT NULL COMMENT 'immer der 1. des Monats',
    betrag      DECIMAL(10, 2) NOT NULL,

    CONSTRAINT fk_forecast_budget
        FOREIGN KEY (budget_id) REFERENCES budget (id)
        ON DELETE CASCADE,

    -- pro (budget_id, monat) genau ein Eintrag; neu eintragen überschreibt
    -- (konzept.md Abschnitt 3)
    CONSTRAINT uq_forecast_budget_monat
        UNIQUE (budget_id, monat)
) ENGINE = InnoDB;

CREATE INDEX idx_budget_owner ON budget (owner_id);
CREATE INDEX idx_buchung_budget ON ist_buchung (budget_id);
CREATE INDEX idx_forecast_budget ON forecast (budget_id);
