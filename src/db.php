<?php
/**
 * Datenbankverbindung (PDO) für Maliá Budget.
 *
 * Verbindet zur MariaDB-Datenbank "malia_budget" aus XAMPP.
 * Standard-Zugangsdaten gemäß Entscheidungslog E-003: Benutzer "root" ohne
 * Passwort (XAMPP-Standardinstallation).
 *
 * Nutzung: require_once __DIR__ . '/db.php'; danach steht $pdo zur Verfügung.
 */

const DB_HOST = 'localhost';
// Standard-Port von MariaDB/XAMPP ist 3306. Läuft auf einem Rechner eine
// weitere MySQL-Instanz auf 3306 (z. B. ein anderes Tool), lässt sich der
// Port ohne Code-Änderung per Umgebungsvariable DB_PORT überschreiben.
const DB_PORT = 3306;
const DB_NAME = 'malia_budget';
const DB_USER = 'root';
const DB_PASS = '';

function verbindeDatenbank(): PDO
{
    $port = getenv('DB_PORT') ?: DB_PORT;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, $port, DB_NAME);

    $optionen = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $optionen);
}

// Bewusst über $GLOBALS statt nur "$pdo = ...": Wird diese Datei (über eine
// Kette von require_once) innerhalb einer Funktion eingebunden - genau das
// macht PHPUnits Bootstrap-Mechanismus -, würde eine einfache Zuweisung nur
// eine lokale Variable in diesem Funktions-Scope erzeugen, nicht die echte
// PHP-Globale. $GLOBALS['pdo'] trifft in jedem Fall den globalen Scope.
$GLOBALS['pdo'] = verbindeDatenbank();
