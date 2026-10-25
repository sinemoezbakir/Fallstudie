<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

use Malia\Repositories\UserRepository;

/**
 * SQL-Injection im Login. Dank Prepared Statements wird jede Eingabe als reiner
 * Text behandelt; ein Angriff darf weder anmelden noch die Datenbank verändern.
 */
class LoginSqlInjectionTest extends IntegrationTestCase
{
    public static function angriffe(): array
    {
        return [
            'OR 1=1 im Benutzernamen'    => ["' OR '1'='1", 'irgendwas'],
            'OR 1=1 in beiden Feldern'   => ["' OR '1'='1", "' OR '1'='1"],
            'Kommentar nach admin'       => ["admin'--", 'x'],
            'Kommentar mit #'            => ["admin' #", 'x'],
            'Passwort-Feld mit OR 1=1'   => ['admin', "' OR '1'='1"],
            'UNION SELECT'               => ["x' UNION SELECT 1,'admin','x','ADMIN' -- ", 'x'],
            'Stapelanweisung DROP TABLE' => ["x'; DROP TABLE users; --", 'x'],
            'doppelte Anführungszeichen' => ['" OR ""="', '" OR ""="'],
            'Backslash-Escape'           => ["admin\\", "\\' OR 1=1 -- "],
        ];
    }

    /** @dataProvider angriffe */
    public function testInjectionMeldetNichtAn(string $username, string $password): void
    {
        $users  = $this->zeilen('users');
        $client = new HttpClient($this->baseUrl);

        $r = $client->login($username, $password);

        // Kein Login: Seite wird mit Fehlermeldung erneut angezeigt (keine Weiterleitung)
        $this->assertSame(200, $r['status']);
        $this->assertStringContainsString('alert', $r['body']);

        // Auch danach ist keine geschützte Seite erreichbar
        $this->assertSame(302, $client->get('owner/index.php')['status']);
        $this->assertSame(302, $client->get('admin/users.php')['status']);
        $this->assertSame($users, $this->zeilen('users'), 'Tabelle users wurde verändert');
    }

    /** Nur Fälle, in denen der Benutzername selbst der Angriff ist (kein echtes Konto). */
    public static function angriffeImBenutzernamen(): array
    {
        return array_filter(self::angriffe(), fn(array $fall) => $fall[0] !== 'admin');
    }

    /** @dataProvider angriffeImBenutzernamen */
    public function testRepositoryBehandeltInjectionAlsText(string $username): void
    {
        $this->assertNull((new UserRepository($this->pdo))->findByUsername($username));
    }

    public function testFehlermeldungVerraetNichtWasFalschWar(): void
    {
        $falschesPasswort = (new HttpClient($this->baseUrl))->login('cafe', 'falsch');
        $unbekannterUser  = (new HttpClient($this->baseUrl))->login('gibtesnicht', 'falsch');

        $this->assertSame(200, $falschesPasswort['status']);
        $this->assertNotSame('', $this->alertText($falschesPasswort['body']));
        $this->assertSame($this->alertText($falschesPasswort['body']), $this->alertText($unbekannterUser['body']));
    }

    public function testLoginOhneCsrfTokenWirdAbgelehnt(): void
    {
        $r = (new HttpClient($this->baseUrl))->post('login.php', [
            'username' => 'cafe', 'password' => self::PASSWORDS['cafe'],
        ]);
        $this->assertSame(200, $r['status']);   // keine Weiterleitung = nicht angemeldet
    }

    public function testRichtigeAnmeldungFunktioniertWeiterhin(): void
    {
        // Gegenprobe: Die Eingabe-Behandlung darf normale Anmeldungen nicht blockieren
        $this->assertSame(302, (new HttpClient($this->baseUrl))->login('cafe', self::PASSWORDS['cafe'])['status']);
    }

    /** Text des Alert-Kastens, damit zwei Fehlermeldungen verglichen werden können. */
    private function alertText(string $html): string
    {
        preg_match('/<div class="alert[^>]*>(.*?)<\/div>/s', $html, $m);
        return trim(strip_tags($m[1] ?? ''));
    }
}
