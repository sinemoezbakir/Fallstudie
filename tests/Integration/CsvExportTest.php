<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

use Malia\Repositories\BudgetRepository;

/** CSV-Export: Besitzprüfung und Format (BOM, Semikolon, Ausgaben + Forecasts). */
class CsvExportTest extends IntegrationTestCase
{
    public static function ownerPaare(): array
    {
        return ['cafe -> marketing' => ['cafe', 'marketing'], 'marketing -> cafe' => ['marketing', 'cafe']];
    }

    /** @dataProvider ownerPaare */
    public function testFremdesBudgetKannNichtExportiertWerden(string $a, string $b): void
    {
        $client = $this->loggedIn($a);
        foreach ((new BudgetRepository($this->pdo))->findByOwner($this->userId($b)) as $budget) {
            $r = $client->get('owner/export.php?id=' . $budget->id);
            $this->assertSame(403, $r['status']);
            $this->assertStringNotContainsString('Art;Datum', $r['body']);
            $this->assertStringNotContainsString($budget->name, $r['body']);
        }
        $this->assertSame(403, $client->get('owner/export.php?id=999999')['status']);
    }

    public function testOhneLoginOderMitFalscherRolleKeinExport(): void
    {
        $this->assertSame(302, (new HttpClient($this->baseUrl))->get('owner/export.php?id=3')['status']);
        $this->assertSame(403, $this->loggedIn('finance')->get('owner/export.php?id=3')['status']);
        $this->assertSame(403, $this->loggedIn('admin')->get('owner/export.php?id=3')['status']);
    }

    public function testExportEnthaeltAusgabenUndForecastsMitBomUndSemikolon(): void
    {
        $budget = (new BudgetRepository($this->pdo))->findByOwner($this->userId('marketing'))[0];
        $r = $this->loggedIn('marketing')->get('owner/export.php?id=' . $budget->id);

        $this->assertSame(200, $r['status']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $r['body']);
        $this->assertStringContainsString('Art;Datum;Beschreibung;"Betrag (EUR)"', $r['body']);

        $ist = (int) $this->pdo->query("SELECT COUNT(*) FROM ist_werte WHERE budget_id = {$budget->id}")->fetchColumn();
        $fc  = (int) $this->pdo->query("SELECT COUNT(*) FROM forecasts WHERE budget_id = {$budget->id}")->fetchColumn();
        $this->assertSame($ist, substr_count($r['body'], 'Ausgabe;'));
        $this->assertSame($fc, substr_count($r['body'], 'Forecast;'));
    }

    public function testExportLinkAufDetailseite(): void
    {
        $budget = (new BudgetRepository($this->pdo))->findByOwner($this->userId('cafe'))[0];
        $body = $this->loggedIn('cafe')->get('owner/budget.php?id=' . $budget->id)['body'];
        $this->assertStringContainsString('export.php?id=' . $budget->id, $body);
        $this->assertStringContainsString('Als CSV exportieren', $body);
    }
}
