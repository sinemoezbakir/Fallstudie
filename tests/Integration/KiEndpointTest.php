<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

use Malia\Repositories\BudgetRepository;

/** KI-Endpunkt: Zugriffsschutz (Rolle, CSRF, Besitz). Es wird keine echte KI aufgerufen. */
class KiEndpointTest extends IntegrationTestCase
{
    public function testOhneLoginWirdZumLoginGeleitet(): void
    {
        $this->assertSame(302, (new HttpClient($this->baseUrl))->post('owner/ki_prognose.php', ['id' => 3])['status']);
    }

    public function testFinanceUndAdminBekommen403(): void
    {
        $this->assertSame(403, $this->loggedIn('finance')->post('owner/ki_prognose.php', ['id' => 3])['status']);
        $this->assertSame(403, $this->loggedIn('admin')->post('owner/ki_prognose.php', ['id' => 3])['status']);
    }

    public function testGetIstNichtErlaubt(): void
    {
        $this->assertSame(405, $this->loggedIn('marketing')->get('owner/ki_prognose.php?id=3')['status']);
    }

    public function testOhneCsrfTokenWirdAbgelehnt(): void
    {
        $r = $this->loggedIn('marketing')->post('owner/ki_prognose.php', ['id' => 3]);
        $this->assertSame(400, $r['status']);
    }

    public function testFremdesBudgetBekommt403(): void
    {
        $client = $this->loggedIn('cafe');
        $token  = $client->csrfFrom('owner/index.php');
        foreach ((new BudgetRepository($this->pdo))->findByOwner($this->userId('marketing')) as $budget) {
            $r = $client->post('owner/ki_prognose.php', ['id' => $budget->id, 'csrf_token' => $token]);
            $this->assertSame(403, $r['status']);
            $this->assertStringNotContainsString($budget->name, $r['body']);
        }
    }
}
