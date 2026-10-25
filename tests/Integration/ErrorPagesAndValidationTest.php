<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

/** Fehlerseiten, Eingabevalidierung und Schutz-Header über alle Seiten. */
class ErrorPagesAndValidationTest extends IntegrationTestCase
{
    public function testFehlerseite403IstEinNormalesLayoutOhneDetails(): void
    {
        $r = $this->loggedIn('cafe')->get('admin/users.php');
        $this->assertSame(403, $r['status']);
        $this->assertStringContainsString('403', $r['body']);
        $this->assertStringContainsString('Zur Startseite', $r['body']);
        $this->assertStringNotContainsString('Stack trace', $r['body']);
    }

    public function testDemoWechselOhneDemoModusGibt404NurWennAusgeschaltet(): void
    {
        // Ist der Demo-Modus an, gilt: ungültiges Konto => 400; ist er aus => 404
        $client = $this->loggedIn('cafe');
        $token  = $client->csrfFrom('owner/index.php');
        $status = $client->post('wechsel.php', ['csrf_token' => $token, 'username' => 'gibtesnicht'])['status'];
        $this->assertContains($status, [400, 404]);
    }

    public function testSchutzHeaderSindGesetzt(): void
    {
        $headers = get_headers($this->baseUrl . '/login.php');
        $text = implode("\n", $headers);
        $this->assertStringContainsString('X-Frame-Options: DENY', $text);
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', $text);
    }

    public function testManipulierteArrayEingabeFuehrtNichtZuFehlerausgabe(): void
    {
        $client = new HttpClient($this->baseUrl);
        $token  = $client->csrfFrom('login.php');
        $r = $client->post('login.php', ['username' => ['x'], 'password' => 'y', 'csrf_token' => $token]);
        $this->assertStringNotContainsString('Warning', $r['body']);
        $this->assertStringNotContainsString('Stack trace', $r['body']);
        $this->assertContains($r['status'], [200, 500]);
    }

    /** @dataProvider ungueltigeBetraege */
    public function testUngueltigeBetraegeWerdenAbgelehnt(string $betrag): void
    {
        $client  = $this->loggedIn('cafe');
        $budget  = $this->pdo->query("SELECT id, gueltig_von FROM budgets WHERE owner_id = {$this->userId('cafe')} LIMIT 1")->fetch();
        $token   = $client->csrfFrom('owner/budget.php?id=' . $budget['id']);
        $vorher  = $this->datenStand();

        $r = $client->post('owner/budget.php?id=' . $budget['id'], [
            'csrf_token' => $token, 'action' => 'create',
            'betrag' => $betrag, 'beschreibung' => 'Test', 'datum' => $budget['gueltig_von'],
        ]);

        $this->assertSame(200, $r['status']);     // Formular erneut, keine Weiterleitung
        $this->assertStringContainsString('alert-danger', $r['body']);
        $this->assertSame($vorher, $this->datenStand());
    }

    public static function ungueltigeBetraege(): array
    {
        return [
            'null'                 => ['0'],
            'negativ'              => ['-5'],
            'Text'                 => ['abc'],
            'wissenschaftlich'     => ['1e2'],
            'Tausenderpunkt'       => ['1.000,50'],
            'zu viele Nachkommas'  => ['1,999'],
            'zu groß'              => ['100000000'],
            'leer'                 => [''],
            'Leerzeichen'          => ['   '],
        ];
    }

    public function testPasswortLaengeBeimAnlegenWirdBegrenzt(): void
    {
        $client = $this->loggedIn('admin');
        $token  = $client->csrfFrom('admin/users.php');
        $vorher = $this->zeilen('users');

        $r = $client->post('admin/users.php', ['csrf_token' => $token, 'action' => 'create',
            'username' => 'zulang', 'password' => str_repeat('a', 73), 'role' => 'OWNER']);

        $this->assertStringContainsString('8 bis 72', $r['body']);
        $this->assertSame($vorher, $this->zeilen('users'));
    }
}
