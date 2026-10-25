<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

/**
 * Rollenprüfung der Seiten: Wer darf welche Seite aufrufen?
 * Erwartung: nicht angemeldet -> 302 zum Login, falsche Rolle -> 403.
 */
class AccessControlTest extends IntegrationTestCase
{
    private const OWNER_SEITEN   = ['owner/index.php', 'owner/budget.php?id=1'];
    private const FINANCE_SEITEN = ['finance/overview.php', 'finance/budgets.php'];
    private const ADMIN_SEITEN   = ['admin/users.php'];

    public static function ownerKonten(): array
    {
        return ['cafe' => ['cafe'], 'marketing' => ['marketing']];
    }

    // ---------- nicht angemeldet ----------

    public function testNichtAngemeldeteWerdenZumLoginGeleitet(): void
    {
        $client = new HttpClient($this->baseUrl);
        $seiten = array_merge(self::OWNER_SEITEN, self::FINANCE_SEITEN, self::ADMIN_SEITEN);
        foreach ($seiten as $seite) {
            $r = $client->get($seite);
            $this->assertSame(302, $r['status'], "$seite ist ohne Login erreichbar");
            $this->assertStringContainsString('login.php', (string) $r['location'], $seite);
            $this->assertSame('', trim($r['body']), "$seite sendet Inhalt ohne Login");
        }
    }

    public function testNichtAngemeldetePostsWerdenZumLoginGeleitet(): void
    {
        $vorher = $this->datenStand();
        $client = new HttpClient($this->baseUrl);

        $r1 = $client->post('admin/users.php', ['action' => 'create', 'username' => 'eindringling',
            'password' => 'Passwort123!', 'role' => 'ADMIN']);
        $r2 = $client->post('finance/budgets.php', ['action' => 'create', 'name' => 'Fake']);

        $this->assertSame(302, $r1['status']);
        $this->assertSame(302, $r2['status']);
        $this->assertSame($vorher, $this->datenStand());
    }

    // ---------- OWNER ----------

    /** @dataProvider ownerKonten */
    public function testOwnerDarfKeineFinanceOderAdminSeitenAufrufen(string $konto): void
    {
        $client = $this->loggedIn($konto);
        foreach (array_merge(self::FINANCE_SEITEN, self::ADMIN_SEITEN) as $seite) {
            $this->assertSame(403, $client->get($seite)['status'], "$konto darf $seite aufrufen");
        }
    }

    public function testOwnerKannKeineBenutzerOderBudgetsAnlegen(): void
    {
        $client = $this->loggedIn('cafe');
        $token  = $client->csrfFrom('owner/index.php');
        $vorher = $this->datenStand();

        $r1 = $client->post('admin/users.php', ['csrf_token' => $token, 'action' => 'create',
            'username' => 'eindringling', 'password' => 'Passwort123!', 'role' => 'ADMIN']);
        $r2 = $client->post('finance/budgets.php', ['csrf_token' => $token, 'action' => 'create',
            'name' => 'Fake', 'bereich' => 'Café', 'soll_betrag' => '1000', 'gueltig_von' => '2026-01-01',
            'gueltig_bis' => '2026-12-31', 'owner_id' => $this->userId('cafe')]);

        $this->assertSame(403, $r1['status']);
        $this->assertSame(403, $r2['status']);
        $this->assertSame($vorher, $this->datenStand());
    }

    // ---------- FINANCE ----------

    public function testFinanceDarfKeineAdminSeitenAufrufen(): void
    {
        $this->assertSame(403, $this->loggedIn('finance')->get('admin/users.php')['status']);
    }

    public function testFinanceKannKeineBenutzerAnlegen(): void
    {
        $client = $this->loggedIn('finance');
        $token  = $client->csrfFrom('finance/overview.php');
        $vorher = $this->datenStand();

        $r = $client->post('admin/users.php', ['csrf_token' => $token, 'action' => 'create',
            'username' => 'eindringling', 'password' => 'Passwort123!', 'role' => 'ADMIN']);

        $this->assertSame(403, $r['status']);
        $this->assertSame($vorher, $this->datenStand());
    }

    public function testFinanceDarfKeineOwnerSeitenAufrufen(): void
    {
        $client = $this->loggedIn('finance');
        $this->assertSame(403, $client->get('owner/index.php')['status']);
        $this->assertSame(403, $client->get('owner/budget.php?id=1')['status']);
    }

    // ---------- ADMIN ----------

    public function testAdminSiehtKeineBudgetdaten(): void
    {
        $client = $this->loggedIn('admin');
        foreach (array_merge(self::OWNER_SEITEN, self::FINANCE_SEITEN) as $seite) {
            $this->assertSame(403, $client->get($seite)['status'], "Admin darf $seite aufrufen");
        }
    }

    // ---------- Gegenproben: erlaubte Zugriffe funktionieren ----------

    public function testErlaubteSeitenSindErreichbar(): void
    {
        $this->assertSame(200, $this->loggedIn('admin')->get('admin/users.php')['status']);
        $this->assertSame(200, $this->loggedIn('finance')->get('finance/overview.php')['status']);
        $this->assertSame(200, $this->loggedIn('finance')->get('finance/budgets.php')['status']);
        $this->assertSame(200, $this->loggedIn('cafe')->get('owner/index.php')['status']);
    }

    public function testWeiterleitungNachLoginJeNachRolle(): void
    {
        $ziele = [
            'admin'     => 'admin/users.php',
            'finance'   => 'finance/overview.php',
            'cafe'      => 'owner/index.php',
            'marketing' => 'owner/index.php',
        ];
        foreach ($ziele as $konto => $ziel) {
            $r = (new HttpClient($this->baseUrl))->login($konto, self::PASSWORDS[$konto]);
            $this->assertSame(302, $r['status']);
            $this->assertStringContainsString($ziel, (string) $r['location'], $konto);
        }
    }

    public function testNachLogoutIstDieSessionUngueltig(): void
    {
        $client = $this->loggedIn('cafe');
        $token  = $client->csrfFrom('owner/index.php');
        $client->post('logout.php', ['csrf_token' => $token]);

        $r = $client->get('owner/index.php');
        $this->assertSame(302, $r['status']);
        $this->assertStringContainsString('login.php', (string) $r['location']);
    }
}
