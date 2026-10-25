<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests für Verlauf (MB-044), Zusammenfassung und "Braucht Aufmerksamkeit"
 * (MB-040). Eigene Testdaten in einer Transaktion, Rollback am Ende.
 */
final class AuswertungTest extends TestCase
{
    private PDO $pdo;
    private int $ownerId;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();

        $this->ownerId = (int) $this->pdo->query("SELECT id FROM benutzer WHERE rolle = 'OWNER' LIMIT 1")->fetchColumn();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    private function budget(float $soll, string $von, string $bis): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id)
             VALUES ('Verlauf-Test', 'STUDIO', :soll, :von, :bis, :owner)"
        );
        $stmt->execute(['soll' => $soll, 'von' => $von, 'bis' => $bis, 'owner' => $this->ownerId]);
        $stmt = $this->pdo->prepare('SELECT * FROM budget WHERE id = :id');
        $stmt->execute(['id' => $this->pdo->lastInsertId()]);

        return $stmt->fetch();
    }

    private function buchung(int $budgetId, float $betrag, string $datum): void
    {
        $this->pdo->prepare(
            "INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von) VALUES (?, ?, ?, 'Test', ?)"
        )->execute([$budgetId, $betrag, $datum, $this->ownerId]);
    }

    private function forecast(int $budgetId, string $monat, float $betrag): void
    {
        $this->pdo->prepare('INSERT INTO forecast (budget_id, monat, betrag) VALUES (?, ?, ?)')
            ->execute([$budgetId, $monat, $betrag]);
    }

    public function testVerlaufKumuliertIstUndSetztForecastFort(): void
    {
        $budget = $this->budget(1000, '2026-08-01', '2026-12-31');
        $this->buchung((int) $budget['id'], 100, '2026-08-10');
        $this->buchung((int) $budget['id'], 50, '2026-08-20');
        $this->buchung((int) $budget['id'], 200, '2026-10-05');
        $this->forecast((int) $budget['id'], '2026-11-01', 300);
        $this->forecast((int) $budget['id'], '2026-12-01', 400);

        $verlauf = verlaufFuerBudget($budget, '2026-10-25');

        $this->assertSame(['2026-08-01', '2026-09-01', '2026-10-01', '2026-11-01', '2026-12-01'], $verlauf['monate']);
        $this->assertSame([150.0, 150.0, 350.0, null, null], $verlauf['ist']);
        // Prognose beginnt am Vormonat (Anschluss an die Ist-Linie), dann Ist + Forecast kumuliert
        $this->assertSame([null, 150.0, 350.0, 650.0, 1050.0], $verlauf['prognose']);
        $this->assertSame(1000.0, $verlauf['soll']);
    }

    public function testAbgelaufenesBudgetHatKeinePrognose(): void
    {
        $budget = $this->budget(500, '2026-03-01', '2026-05-31');
        $this->buchung((int) $budget['id'], 120, '2026-04-02');

        $verlauf = verlaufFuerBudget($budget, '2026-10-25');

        $this->assertSame([0.0, 120.0, 120.0], $verlauf['ist']);
        $this->assertSame([null, null, null], $verlauf['prognose']);
    }

    public function testZusammenfassungIgnoriertAbgelaufeneBeiDenSummen(): void
    {
        $eintraege = [
            ['budget' => ['soll_betrag' => '1000.00'], 'k' => ['status' => 'im_rahmen', 'ist' => 400.0, 'verfuegbar' => 600.0, 'hochrechnung' => 700.0]],
            ['budget' => ['soll_betrag' => '500.00'], 'k' => ['status' => 'ueberschritten', 'ist' => 600.0, 'verfuegbar' => -100.0, 'hochrechnung' => 600.0]],
            ['budget' => ['soll_betrag' => '300.00'], 'k' => ['status' => 'abgelaufen', 'ist' => 300.0, 'verfuegbar' => 0.0, 'hochrechnung' => 300.0]],
        ];

        $summe = zusammenfassung($eintraege);

        $this->assertSame(1500.0, $summe['soll']);
        $this->assertSame(1000.0, $summe['ist']);
        $this->assertSame(500.0, $summe['verfuegbar']);
        $this->assertSame(2, $summe['laufend']);
        $this->assertSame(1, $summe['anzahl']['abgelaufen']);
    }

    public function testAufmerksamkeitSortiertNachDringlichkeit(): void
    {
        $budget = fn (string $name) => ['id' => 1, 'name' => $name, 'soll_betrag' => '1000.00', 'gueltig_bis' => '2026-12-31'];
        $k = fn (string $status, int $tage, float $verbrauch = 50.0) => [
            'status' => $status, 'tage_bis_verfall' => $tage, 'ist' => 500.0, 'verfuegbar' => 500.0,
            'verbrauch_prozent' => $verbrauch, 'hochrechnung' => 900.0,
        ];

        $liste = aufmerksamkeitsListe([
            ['budget' => $budget('läuft ab'), 'k' => $k('im_rahmen', 10)],
            ['budget' => $budget('entspannt'), 'k' => $k('im_rahmen', 200)],
            ['budget' => $budget('warnung'), 'k' => $k('warnung', 60, 85.0)],
            ['budget' => $budget('drüber'), 'k' => $k('ueberschritten', 60)],
        ]);

        $this->assertSame(['ueberschritten', 'warnung', 'laeuft_ab'], array_column($liste, 'art'));
        $this->assertSame('85 % verbraucht', str_replace("\u{00A0}", ' ', $liste[1]['text']));
    }
}
