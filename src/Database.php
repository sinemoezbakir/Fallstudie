<?php
declare(strict_types=1);

namespace Malia;

use PDO;

/**
 * Baut die Datenbankverbindung per PDO auf.
 * Alle Repositories bekommen später dieses PDO-Objekt und nutzen
 * ausschließlich Prepared Statements (Schutz vor SQL-Injection).
 */
class Database
{
    /**
     * @param array $config der Bereich 'db' aus config/config.php
     */
    public static function connect(array $config): PDO
    {
        // DSN = Data Source Name: wohin und mit welchem Zeichensatz verbinden
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        return new PDO($dsn, $config['user'], $config['password'], [
            // Fehler als Exceptions werfen statt sie still zu ignorieren
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Ergebnisse als assoziative Arrays (Spaltenname => Wert)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Echte Prepared Statements auf dem Datenbankserver
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
