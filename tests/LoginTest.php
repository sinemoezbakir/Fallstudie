<?php

use PHPUnit\Framework\TestCase;

/**
 * Integrationstest für den Login (MB-020, MB-050): richtiges und falsches
 * Passwort gegen echte password_hash()/password_verify()-Logik.
 */
final class LoginTest extends TestCase
{
    private PDO $pdo;
    private string $username;
    private string $klartextPasswort = 'geheim1234';

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();

        $this->username = 'test_login_' . uniqid();

        $stmt = $this->pdo->prepare(
            "INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle)
             VALUES (:username, :hash, 'Test Login', 'OWNER')"
        );
        $stmt->execute([
            'username' => $this->username,
            'hash' => password_hash($this->klartextPasswort, PASSWORD_DEFAULT),
        ]);
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function testRichtigesPasswortFunktioniert(): void
    {
        $benutzer = pruefeLogin($this->username, $this->klartextPasswort);

        $this->assertNotNull($benutzer);
        $this->assertSame('OWNER', $benutzer['rolle']);
    }

    public function testFalschesPasswortSchlaegtFehl(): void
    {
        $benutzer = pruefeLogin($this->username, 'falsches-passwort');

        $this->assertNull($benutzer);
    }

    public function testUnbekannterUsernameSchlaegtFehl(): void
    {
        $benutzer = pruefeLogin('es_gibt_mich_nicht_' . uniqid(), 'irgendwas');

        $this->assertNull($benutzer);
    }
}
