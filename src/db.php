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
const DB_NAME = 'malia_budget';
const DB_USER = 'root';
const DB_PASS = '';

function verbindeDatenbank(): PDO
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

    $optionen = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $optionen);
}

$pdo = verbindeDatenbank();
