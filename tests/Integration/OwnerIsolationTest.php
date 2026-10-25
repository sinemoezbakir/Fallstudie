<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

use Malia\Repositories\BudgetRepository;
use Malia\Repositories\ForecastRepository;
use Malia\Repositories\IstWertRepository;

/**
 * Datentrennung: Owner A darf nichts von Owner B lesen, ändern oder korrigieren.
 * Jeder Test läuft in beide Richtungen (cafe -> marketing und marketing -> cafe).
 */
class OwnerIsolationTest extends IntegrationTestCase
{
    public static function ownerPaare(): array
    {
        return [
            'cafe greift marketing an' => ['cafe', 'marketing'],
            'marketing greift cafe an' => ['marketing', 'cafe'],
        ];
    }

    // ---------- Repository-Ebene (Lesen) ----------

    /** @dataProvider ownerPaare */
    public function testRepositoriesLiefernKeineFremdenDaten(string $a, string $b): void
    {
        $idA = $this->userId($a);
        $idB = $this->userId($b);
        $budgets   = new BudgetRepository($this->pdo);
        $istRepo   = new IstWertRepository($this->pdo);
        $forecasts = new ForecastRepository($this->pdo);

        $fremdeBudgets = $budgets->findByOwner($idB);
        $this->assertNotEmpty($fremdeBudgets, 'Seed-Daten fehlen');

        foreach ($fremdeBudgets as $budget) {
            $this->assertNull($budgets->findByIdAndOwner($budget->id, $idA), 'Fremdes Budget gelesen');
            $this->assertSame([], $istRepo->findByBudgetAndOwner($budget->id, $idA), 'Fremde Ist-Werte gelesen');
            $this->assertSame([], $forecasts->findByBudgetAndOwner($budget->id, $idA), 'Fremde Forecasts gelesen');
        }
        foreach ($istRepo->findByOwner($idB) as $ist) {
            $this->assertNull($istRepo->findByIdAndOwner($ist->id, $idA));
        }
        foreach ($forecasts->findByOwner($idB) as $forecast) {
            $this->assertNull($forecasts->findByIdAndOwner($forecast->id, $idA));
        }

        // Die Listen von A enthalten ausschließlich Budgets von A
        foreach ($budgets->findByOwner($idA) as $budget) {
            $this->assertSame($idA, $budget->ownerId);
        }
    }

    /** @dataProvider ownerPaare */
    public function testRepositoryKorrekturFremderAusgabeWirdAbgelehnt(string $a, string $b): void
    {
        $idA = $this->userId($a);
        $istRepo = new IstWertRepository($this->pdo);
        $fremdeIst = $istRepo->findByOwner($this->userId($b))[0];
        $vorher = $this->datenStand();

        $ok = $istRepo->correct($fremdeIst->id, $idA, 1.00, 'Angriff', $idA);

        $this->assertFalse($ok);
        $this->assertSame($vorher, $this->datenStand(), 'Daten wurden trotz Ablehnung verändert');
    }

    // ---------- Seiten-Ebene (Lesen per URL) ----------

    /** @dataProvider ownerPaare */
    public function testFremdesBudgetPerUrlGibt403(string $a, string $b): void
    {
        $client = $this->loggedIn($a);
        foreach ((new BudgetRepository($this->pdo))->findByOwner($this->userId($b)) as $budget) {
            $response = $client->get('owner/budget.php?id=' . $budget->id);
            $this->assertSame(403, $response['status']);
            $this->assertStringNotContainsString($budget->name, $response['body']);
        }
    }

    /** @dataProvider ownerPaare */
    public function testDashboardZeigtNurEigeneBudgets(string $a, string $b): void
    {
        $body = $this->loggedIn($a)->get('owner/index.php')['body'];
        $repo = new BudgetRepository($this->pdo);
        foreach ($repo->findByOwner($this->userId($b)) as $budget) {
            $this->assertStringNotContainsString($budget->name, $body);
        }
        foreach ($repo->findByOwner($this->userId($a)) as $budget) {
            $this->assertStringContainsString($budget->name, $body);
        }
    }

    public function testNichtExistierendesBudgetVerhaeltSichWieFremdes(): void
    {
        $client = $this->loggedIn('cafe');
        $this->assertSame(403, $client->get('owner/budget.php?id=999999')['status']);
        $this->assertSame(403, $client->get('owner/budget.php?id=abc')['status']);
        $this->assertSame(403, $client->get('owner/budget.php')['status']);
    }

    /** @dataProvider ownerPaare */
    public function testEigenesBudgetIstErreichbar(string $a, string $b): void
    {
        // Gegenprobe: Ohne sie könnte ein kaputter Test die 403 vortäuschen
        $eigenes = (new BudgetRepository($this->pdo))->findByOwner($this->userId($a))[0];
        $response = $this->loggedIn($a)->get('owner/budget.php?id=' . $eigenes->id);
        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString($eigenes->name, $response['body']);
    }

    // ---------- Seiten-Ebene (Ändern per POST) ----------

    /** @dataProvider ownerPaare */
    public function testAusgabeUndForecastAufFremdemBudgetWirdAbgelehnt(string $a, string $b): void
    {
        $repo    = new BudgetRepository($this->pdo);
        $client  = $this->loggedIn($a);
        $eigenes = $repo->findByOwner($this->userId($a))[0];
        $token   = $client->csrfFrom('owner/budget.php?id=' . $eigenes->id);   // gültiges Token des Angreifers
        $fremdes = $repo->findByOwner($this->userId($b))[0];
        $vorher  = $this->datenStand();

        // Datum liegt bewusst in der Gültigkeit des fremden Budgets
        $datum = $fremdes->gueltigVon;
        $ausgabe = $client->post('owner/budget.php?id=' . $fremdes->id, [
            'csrf_token' => $token, 'action' => 'create',
            'betrag' => '10,00', 'beschreibung' => 'Angriff', 'datum' => $datum,
        ]);
        $forecast = $client->post('owner/budget.php?id=' . $fremdes->id, [
            'csrf_token' => $token, 'action' => 'create_forecast',
            'fc_betrag' => '10,00', 'fc_beschreibung' => 'Angriff', 'fc_datum' => $datum,
        ]);

        $this->assertSame(403, $ausgabe['status']);
        $this->assertSame(403, $forecast['status']);
        $this->assertSame($vorher, $this->datenStand());
    }

    /** @dataProvider ownerPaare */
    public function testKorrekturFremderAusgabeWirdAbgelehnt(string $a, string $b): void
    {
        $client  = $this->loggedIn($a);
        $eigenes = (new BudgetRepository($this->pdo))->findByOwner($this->userId($a))[0];
        $token   = $client->csrfFrom('owner/budget.php?id=' . $eigenes->id);
        $fremdeIst = (new IstWertRepository($this->pdo))->findByOwner($this->userId($b))[0];
        $vorher  = $this->datenStand();

        // Variante 1: direkt über die URL des fremden Budgets
        $r1 = $client->post('owner/budget.php?id=' . $fremdeIst->budgetId, [
            'csrf_token' => $token, 'action' => 'correct',
            'ist_wert_id' => $fremdeIst->id, 'neuer_betrag' => '1,00', 'grund' => 'Angriff',
        ]);
        // Variante 2: eigenes Budget in der URL, aber fremde ist_wert_id im Formular
        $r2 = $client->post('owner/budget.php?id=' . $eigenes->id, [
            'csrf_token' => $token, 'action' => 'correct',
            'ist_wert_id' => $fremdeIst->id, 'neuer_betrag' => '1,00', 'grund' => 'Angriff',
        ]);

        $this->assertSame(403, $r1['status']);
        $this->assertSame(200, $r2['status']);                 // Seite mit Fehlermeldung, keine Weiterleitung
        $this->assertStringContainsString('Ausgabe nicht gefunden', $r2['body']);
        $this->assertSame($vorher, $this->datenStand());
    }

    public function testPostOhneCsrfTokenWirdAbgelehnt(): void
    {
        $client  = $this->loggedIn('cafe');
        $eigenes = (new BudgetRepository($this->pdo))->findByOwner($this->userId('cafe'))[0];
        $vorher  = $this->datenStand();

        $r = $client->post('owner/budget.php?id=' . $eigenes->id, [
            'action' => 'create', 'betrag' => '10,00', 'beschreibung' => 'ohne Token',
            'datum' => $eigenes->gueltigVon,
        ]);

        $this->assertStringContainsString('Ungültige Anfrage', $r['body']);
        $this->assertSame($vorher, $this->datenStand());
    }
}
