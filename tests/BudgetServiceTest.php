<?php
declare(strict_types=1);

namespace Malia\Tests;

use Malia\Models\Forecast;
use Malia\Models\IstWert;
use Malia\Services\BudgetService;
use PHPUnit\Framework\TestCase;

class BudgetServiceTest extends TestCase
{
    private BudgetService $service;

    // Festes "heute" für alle Statustests, damit sie nicht vom Datum abhängen
    private const HEUTE = '2026-06-15';
    private const NOCH_GUELTIG = '2026-12-31';

    protected function setUp(): void
    {
        $this->service = new BudgetService();
    }

    private function ist(float $betrag): IstWert
    {
        return new IstWert(1, 1, $betrag, 'Test', '2026-01-01', 3);
    }

    private function forecast(float $betrag): Forecast
    {
        return new Forecast(1, 1, $betrag, 'Test', '2026-07-01', 3);
    }

    // ---------- Summen ----------

    public function testSummeIstAddiertAlleBetraege(): void
    {
        $this->assertSame(2150.5, $this->service->summeIst([$this->ist(1200.00), $this->ist(950.50)]));
    }

    public function testSummeIstLeereListeIstNull(): void
    {
        $this->assertSame(0.0, $this->service->summeIst([]));
    }

    public function testSummeIstHatKeineFliesskommaFehler(): void
    {
        // 0.1 + 0.2 ergibt in PHP 0.30000000000000004
        $this->assertSame(0.3, $this->service->summeIst([$this->ist(0.1), $this->ist(0.2)]));
    }

    public function testSummeForecastAddiertAlleBetraege(): void
    {
        $this->assertSame(800.0, $this->service->summeForecast([$this->forecast(500.0), $this->forecast(300.0)]));
    }

    public function testSummeForecastLeereListeIstNull(): void
    {
        $this->assertSame(0.0, $this->service->summeForecast([]));
    }

    // ---------- Restbudget ----------

    public function testRestbudgetIstSollMinusIst(): void
    {
        $this->assertSame(2849.5, $this->service->restbudget(5000.0, 2150.5));
    }

    public function testRestbudgetIstNegativBeiUeberschreitung(): void
    {
        $this->assertSame(-180.0, $this->service->restbudget(1500.0, 1680.0));
    }

    public function testRestbudgetBeiExaktAusgeschoepftemBudgetIstNull(): void
    {
        $this->assertSame(0.0, $this->service->restbudget(1000.0, 1000.0));
    }

    // ---------- Auslastung ----------

    public function testAuslastungNormalfall(): void
    {
        $this->assertEqualsWithDelta(43.01, $this->service->auslastungProzent(5000.0, 2150.5), 0.0001);
    }

    public function testAuslastungGenau100Prozent(): void
    {
        $this->assertEqualsWithDelta(100.0, $this->service->auslastungProzent(1000.0, 1000.0), 0.0001);
    }

    public function testAuslastungUeber100Prozent(): void
    {
        $this->assertEqualsWithDelta(112.0, $this->service->auslastungProzent(1500.0, 1680.0), 0.0001);
    }

    public function testAuslastungSollNullOhneAusgabenIstNull(): void
    {
        $this->assertSame(0.0, $this->service->auslastungProzent(0.0, 0.0));
    }

    public function testAuslastungSollNullMitAusgabenIstNichtBerechenbar(): void
    {
        $this->assertNull($this->service->auslastungProzent(0.0, 50.0));
    }

    // ---------- Prognose ----------

    public function testPrognoseIstIstPlusForecast(): void
    {
        $this->assertSame(2950.5, $this->service->prognose(2150.5, 800.0));
    }

    public function testPrognoseOhneForecastEntsprichtIst(): void
    {
        $this->assertSame(500.0, $this->service->prognose(500.0, 0.0));
    }

    // ---------- Status ----------

    public function testStatusImRahmenUnter80Prozent(): void
    {
        $this->assertSame(
            BudgetService::STATUS_IM_RAHMEN,
            $this->service->status(1000.0, 799.99, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusGenau80ProzentIstSchonWarnung(): void
    {
        $this->assertSame(
            BudgetService::STATUS_WARNUNG,
            $this->service->status(1000.0, 800.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusGenau80ProzentBeiKrummenWertenIstWarnung(): void
    {
        // 0.07 * 100 ergäbe als float 7.000000000000001: hier prüfen wir die Cent-Rechnung
        $this->assertSame(
            BudgetService::STATUS_WARNUNG,
            $this->service->status(0.10, 0.08, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusZwischen80Und100ProzentIstWarnung(): void
    {
        $this->assertSame(
            BudgetService::STATUS_WARNUNG,
            $this->service->status(2000.0, 1720.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusGenau100ProzentIstNochNichtUeberschritten(): void
    {
        $this->assertSame(
            BudgetService::STATUS_WARNUNG,
            $this->service->status(1000.0, 1000.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusEinCentUeberSollIstUeberschritten(): void
    {
        $this->assertSame(
            BudgetService::STATUS_UEBERSCHRITTEN,
            $this->service->status(1000.0, 1000.01, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusUeberschrittenBeiDeutlicherUeberschreitung(): void
    {
        $this->assertSame(
            BudgetService::STATUS_UEBERSCHRITTEN,
            $this->service->status(1500.0, 1680.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusSollNullOhneAusgabenIstImRahmen(): void
    {
        $this->assertSame(
            BudgetService::STATUS_IM_RAHMEN,
            $this->service->status(0.0, 0.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusSollNullMitAusgabenIstUeberschritten(): void
    {
        $this->assertSame(
            BudgetService::STATUS_UEBERSCHRITTEN,
            $this->service->status(0.0, 10.0, self::NOCH_GUELTIG, self::HEUTE)
        );
    }

    public function testStatusAbgelaufenNachVerfallsdatum(): void
    {
        $this->assertSame(
            BudgetService::STATUS_ABGELAUFEN,
            $this->service->status(1000.0, 100.0, '2026-06-14', self::HEUTE)
        );
    }

    public function testStatusAmLetztenGueltigkeitstagIstNochNichtAbgelaufen(): void
    {
        $this->assertSame(
            BudgetService::STATUS_IM_RAHMEN,
            $this->service->status(1000.0, 100.0, self::HEUTE, self::HEUTE)
        );
    }

    public function testStatusAbgelaufenHatVorrangVorUeberschritten(): void
    {
        $this->assertSame(
            BudgetService::STATUS_ABGELAUFEN,
            $this->service->status(1000.0, 5000.0, '2025-12-31', self::HEUTE)
        );
    }

    public function testStatusOhneHeuteParameterNutztAktuellesDatum(): void
    {
        $this->assertSame(
            BudgetService::STATUS_ABGELAUFEN,
            $this->service->status(1000.0, 0.0, '2000-01-01')
        );
        $this->assertSame(
            BudgetService::STATUS_IM_RAHMEN,
            $this->service->status(1000.0, 0.0, '2999-12-31')
        );
    }

    public function testSchwelleIst80Prozent(): void
    {
        $this->assertSame(80, BudgetService::WARNUNG_SCHWELLE_PROZENT);
    }
}
