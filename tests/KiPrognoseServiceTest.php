<?php
declare(strict_types=1);

namespace Malia\Tests;

use Malia\Models\Budget;
use Malia\Services\KiPrognoseService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Tests der KI-Schnittstelle ohne Internet: Der HTTP-Aufruf wird durch eine Attrappe ersetzt. */
class KiPrognoseServiceTest extends TestCase
{
    private function budget(): Budget
    {
        return new Budget(1, 'Geheimer Budgetname', 'Marketing', 1500.0, '2026-01-01', '2026-12-31', 4, 2);
    }

    public function testOhneKeyIstDieKiNichtAktiv(): void
    {
        putenv('GEMINI_API_KEY');   // Umgebungsvariable entfernen
        $this->assertFalse((new KiPrognoseService(['aktiv' => true, 'api_key' => '']))->istAktiv());
    }

    public function testAusgeschaltetTrotzKeyIstNichtAktiv(): void
    {
        $this->assertFalse((new KiPrognoseService(['aktiv' => false, 'api_key' => 'x']))->istAktiv());
    }

    public function testMitKeyUndAktivIstDieKiAktiv(): void
    {
        $this->assertTrue((new KiPrognoseService(['aktiv' => true, 'api_key' => 'x']))->istAktiv());
    }

    public function testEinschaetzungOhneKeyWirftFehler(): void
    {
        putenv('GEMINI_API_KEY');
        $this->expectException(RuntimeException::class);
        (new KiPrognoseService(['aktiv' => true, 'api_key' => '']))->einschaetzung([]);
    }

    public function testDatenEnthaltenKeineNamenOderBeschreibungen(): void
    {
        $service = new KiPrognoseService(['aktiv' => true, 'api_key' => 'x']);
        $daten   = $service->baueDaten($this->budget(), [['datum' => '2026-04-01', 'betrag' => 900.0]], [], 'WARNUNG', '2026-06-15');
        $anweisung = $service->baueAnweisung($daten);

        $this->assertStringContainsString('1500', $anweisung);
        $this->assertStringContainsString('2026-04-01', $anweisung);
        $this->assertStringNotContainsString('Geheimer Budgetname', $anweisung);
        $this->assertStringNotContainsString('Marketing', $anweisung);
    }

    public function testAntwortTextWirdAusDenTeilenZusammengesetzt(): void
    {
        $gesehen = [];
        $transport = function (string $url, array $header, array $body) use (&$gesehen): array {
            $gesehen = compact('url', 'header', 'body');
            return ['candidates' => [['content' => ['parts' => [['text' => 'Teil 1. '], ['text' => 'Teil 2.']]]]]];
        };
        $service = new KiPrognoseService(['aktiv' => true, 'api_key' => 'GEHEIM', 'modell' => 'test-modell'], $transport);

        $this->assertSame('Teil 1. Teil 2.', $service->einschaetzung(['soll_euro' => 1.0]));
        $this->assertStringEndsWith('test-modell:generateContent', $gesehen['url']);
        // Der Key darf nur im Header stehen, nie in der URL
        $this->assertStringNotContainsString('GEHEIM', $gesehen['url']);
        $this->assertContains('x-goog-api-key: GEHEIM', $gesehen['header']);
    }

    public function testLeereAntwortWirdAlsFehlerGemeldet(): void
    {
        $service = new KiPrognoseService(['aktiv' => true, 'api_key' => 'x'], fn() => ['candidates' => []]);
        $this->expectException(RuntimeException::class);
        $service->einschaetzung([]);
    }

    public function testUngueltigerModellnameWirdAbgelehnt(): void
    {
        $service = new KiPrognoseService(['aktiv' => true, 'api_key' => 'x', 'modell' => '../böse?x=1'], fn() => []);
        $this->expectException(RuntimeException::class);
        $service->einschaetzung([]);
    }
}
