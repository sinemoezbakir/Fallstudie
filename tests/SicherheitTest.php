<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/demo.php';

/**
 * CSRF-Token (src/csrf.php) und Kontowechsel im Demo-Modus (src/demo.php, E-021).
 */
final class SicherheitTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
        $_SESSION = [];
    }

    public function testCsrfTokenIstStabilUndLang(): void
    {
        $token = csrfToken();

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, csrfToken());
        $this->assertTrue(csrfGueltig($token));
    }

    public function testFalschesOderFehlendesTokenWirdAbgelehnt(): void
    {
        csrfToken();

        $this->assertFalse(csrfGueltig(null));
        $this->assertFalse(csrfGueltig(''));
        $this->assertFalse(csrfGueltig(str_repeat('a', 64)));
    }

    public function testOhneSessionTokenIstNichtsGueltig(): void
    {
        $this->assertFalse(csrfGueltig(''));
    }

    public function testKontowechselSetztDenselbenZustandWieLogin(): void
    {
        $id = (int) $this->pdo->query("SELECT id FROM benutzer WHERE username = 'cafe'")->fetchColumn();

        $konto = wechsleZuKonto($id, true);

        $this->assertNotNull($konto);
        $this->assertSame($id, $_SESSION['user_id']);
        $this->assertSame('OWNER', $_SESSION['rolle']);
        $this->assertSame('cafe', $_SESSION['username']);
    }

    public function testKontowechselNurImDemoModus(): void
    {
        $id = (int) $this->pdo->query("SELECT id FROM benutzer WHERE username = 'admin'")->fetchColumn();

        $this->assertNull(wechsleZuKonto($id, false));
        $this->assertArrayNotHasKey('rolle', $_SESSION);
    }

    public function testKontowechselZuUnbekanntemKontoScheitert(): void
    {
        $this->assertNull(wechsleZuKonto(999999, true));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testNachWechselGiltDatentrennungFuerNeuesKonto(): void
    {
        $studio = (int) $this->pdo->query("SELECT id FROM benutzer WHERE username = 'studio'")->fetchColumn();
        $cafe = (int) $this->pdo->query("SELECT id FROM benutzer WHERE username = 'cafe'")->fetchColumn();
        $studioBudget = (int) $this->pdo->query("SELECT id FROM budget WHERE owner_id = {$studio} LIMIT 1")->fetchColumn();

        wechsleZuKonto($cafe, true);

        $this->assertNull(findeBudgetFuerOwner($studioBudget, $_SESSION['user_id']));
    }

    public function testSqlDateiWirdKorrektZerlegt(): void
    {
        $anweisungen = zerlegeSql("-- Kommentar\nINSERT INTO x VALUES ('a, b');\n\nSET @y = 1;\n");

        $this->assertSame(["INSERT INTO x VALUES ('a, b')", 'SET @y = 1'], $anweisungen);
    }
}
