<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

use Malia\Database;
use PDO;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/HttpClient.php';

/**
 * Basisklasse der Integrationstests. Sie braucht
 *   1. die Datenbank mit sql/schema.sql + sql/seed.sql und
 *   2. den laufenden Webserver (php -S localhost:8000 -t public).
 * Fehlt etwas davon, werden die Tests übersprungen statt fehlzuschlagen.
 * Eine andere Adresse kann über die Umgebungsvariable MALIA_BASE_URL gesetzt werden.
 */
abstract class IntegrationTestCase extends TestCase
{
    // Seed-Konten (siehe sql/seed.sql)
    protected const PASSWORDS = [
        'admin'     => 'Admin2025!',
        'finance'   => 'Finance2025!',
        'cafe'      => 'Cafe2025!',
        'marketing' => 'Marketing2025!',
    ];

    protected PDO $pdo;
    protected string $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('MALIA_BASE_URL') ?: 'http://localhost:8000';

        try {
            $config    = require __DIR__ . '/../../config/config.php';
            $this->pdo = Database::connect($config['db']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Datenbank nicht erreichbar: ' . $e->getMessage());
        }

        $probe = (new HttpClient($this->baseUrl))->get('login.php');
        if ($probe['status'] !== 200) {
            $this->markTestSkipped('Webserver nicht erreichbar unter ' . $this->baseUrl);
        }
    }

    /** Neuer "Browser", angemeldet als das Seed-Konto. */
    protected function loggedIn(string $username): HttpClient
    {
        $client   = new HttpClient($this->baseUrl);
        $response = $client->login($username, self::PASSWORDS[$username]);
        $this->assertSame(302, $response['status'], "Login als $username ist fehlgeschlagen");
        return $client;
    }

    protected function userId(string $username): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return (int) $stmt->fetchColumn();
    }

    /** Anzahl Zeilen einer Tabelle (nur feste Tabellennamen aus den Tests). */
    protected function zeilen(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    /** Fingerabdruck der Daten, die ein Angreifer nicht verändern darf. */
    protected function datenStand(): array
    {
        return [
            'budgets'     => $this->pdo->query('SELECT id, name, soll_betrag, owner_id FROM budgets ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
            'ist_werte'   => $this->pdo->query('SELECT id, budget_id, betrag, datum FROM ist_werte ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
            'forecasts'   => $this->pdo->query('SELECT id, budget_id, betrag FROM forecasts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
            'korrekturen' => $this->zeilen('ist_wert_korrekturen'),
            'users'       => $this->zeilen('users'),
        ];
    }
}
