<?php
declare(strict_types=1);

namespace Malia\Services;

use Malia\Models\Forecast;
use Malia\Models\IstWert;

/**
 * Reine Berechnungen rund um ein Budget. Kein Datenbankzugriff, keine Session:
 * Die Methoden bekommen Werte und liefern ein Ergebnis. Dadurch sind sie leicht testbar.
 *
 * Beträge werden intern in Cent (ganze Zahlen) verglichen, weil Kommazahlen
 * (float) ungenau sein können, z. B. 0.07 * 100 = 7.000000000000001.
 */
class BudgetService
{
    // Status-Werte
    public const STATUS_IM_RAHMEN      = 'IM_RAHMEN';
    public const STATUS_WARNUNG        = 'WARNUNG';
    public const STATUS_UEBERSCHRITTEN = 'UEBERSCHRITTEN';
    public const STATUS_ABGELAUFEN     = 'ABGELAUFEN';

    /** Ab dieser Auslastung (in Prozent) gilt ein Budget als "Warnung". */
    public const WARNUNG_SCHWELLE_PROZENT = 80;

    /** Summe aller Ist-Werte (Ausgaben). @param IstWert[] $istWerte */
    public function summeIst(array $istWerte): float
    {
        $cents = 0;
        foreach ($istWerte as $wert) {
            $cents += $this->inCent($wert->betrag);
        }
        return $cents / 100;
    }

    /** Summe aller Forecast-Werte (erwartete Ausgaben). @param Forecast[] $forecasts */
    public function summeForecast(array $forecasts): float
    {
        $cents = 0;
        foreach ($forecasts as $forecast) {
            $cents += $this->inCent($forecast->betrag);
        }
        return $cents / 100;
    }

    /** Restbudget = Soll minus Ist. Negativ, wenn das Budget überschritten ist. */
    public function restbudget(float $soll, float $ist): float
    {
        return ($this->inCent($soll) - $this->inCent($ist)) / 100;
    }

    /**
     * Auslastung in Prozent = Ist / Soll * 100.
     * Bei Soll = 0 ist keine Prozentzahl möglich:
     *   - Ist = 0  -> 0.0
     *   - Ist > 0  -> null ("nicht berechenbar"; status() meldet dann UEBERSCHRITTEN)
     */
    public function auslastungProzent(float $soll, float $ist): ?float
    {
        $sollCent = $this->inCent($soll);
        $istCent  = $this->inCent($ist);

        if ($sollCent <= 0) {
            return $istCent > 0 ? null : 0.0;
        }
        return $istCent / $sollCent * 100;
    }

    /** Prognose = bisherige Ausgaben (Ist) plus erwartete Ausgaben (Forecast). */
    public function prognose(float $ist, float $forecast): float
    {
        return ($this->inCent($ist) + $this->inCent($forecast)) / 100;
    }

    /**
     * Status eines Budgets. Reihenfolge der Prüfung:
     *  1. ABGELAUFEN:     Verfallszeit vorbei (heute liegt nach gueltigBis)
     *  2. UEBERSCHRITTEN: Ist liegt über dem Soll (mehr als 100 %)
     *  3. WARNUNG:        Auslastung ab 80 % (genau 80 % zählt schon als Warnung)
     *  4. IM_RAHMEN:      sonst
     * Genau 100 % ist noch nicht überschritten, sondern Warnung.
     *
     * @param string|null $heute Datum YYYY-MM-DD; Standard: heutiges Datum (für Tests änderbar)
     */
    public function status(float $soll, float $ist, string $gueltigBis, ?string $heute = null): string
    {
        $heute ??= date('Y-m-d');

        // Datumstexte im Format YYYY-MM-DD lassen sich direkt vergleichen.
        // Am letzten Gültigkeitstag ist das Budget noch gültig.
        if ($heute > $gueltigBis) {
            return self::STATUS_ABGELAUFEN;
        }

        $sollCent = $this->inCent($soll);
        $istCent  = $this->inCent($ist);

        if ($istCent > $sollCent) {
            return self::STATUS_UEBERSCHRITTEN;   // gilt auch bei Soll = 0 und Ist > 0
        }
        // Ganzzahl-Vergleich statt Prozentrechnung: Ist/Soll >= 80 %  <=>  Ist*100 >= Soll*80
        if ($sollCent > 0 && $istCent * 100 >= $sollCent * self::WARNUNG_SCHWELLE_PROZENT) {
            return self::STATUS_WARNUNG;
        }
        return self::STATUS_IM_RAHMEN;
    }

    /** Euro -> ganze Cent (gerundet). */
    private function inCent(float $euro): int
    {
        return (int) round($euro * 100);
    }
}
