<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Status-Berechnung (MB-033, MB-050).
 * Jeder Test läuft in einer eigenen Transaktion, die am Ende zurückgerollt
 * wird - die Demodaten bleiben dadurch unangetastet.
 */
final class KennzahlenTest extends TestCase
{
    private PDO $pdo;
    private int $ownerId;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();

        $this->ownerId = (int) $this->pdo
            ->query("SELECT id FROM benutzer WHERE rolle = 'OWNER' LIMIT 1")
            ->fetchColumn();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    private function legeTestBudgetAn(float $sollBetrag, string $gueltigVon, string $gueltigBis): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id)
             VALUES (:name, :bereich, :soll, :von, :bis, :owner)'
        );
        $stmt->execute([
            'name' => 'Test-Budget ' . uniqid(),
            'bereich' => 'STUDIO',
            'soll' => $sollBetrag,
            'von' => $gueltigVon,
            'bis' => $gueltigBis,
            'owner' => $this->ownerId,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare('SELECT * FROM budget WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    private function fuegeIstBuchungHinzu(int $budgetId, float $betrag): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von)
             VALUES (:budget_id, :betrag, CURDATE(), 'Test', :erfasst_von)"
        );
        $stmt->execute(['budget_id' => $budgetId, 'betrag' => $betrag, 'erfasst_von' => $this->ownerId]);
    }

    public function testImRahmenUnter80Prozent(): void
    {
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', '2030-12-31');
        $this->fuegeIstBuchungHinzu($budget['id'], 799.00); // 79,9 %

        $this->assertSame('im_rahmen', berechneStatus($budget));
    }

    public function testWarnungBeiGenau80Prozent(): void
    {
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', '2030-12-31');
        $this->fuegeIstBuchungHinzu($budget['id'], 800.00); // genau 80 % - Grenzfall

        $this->assertSame('warnung', berechneStatus($budget));
    }

    public function testUeberschrittenWennIstGroesserSoll(): void
    {
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', '2030-12-31');
        $this->fuegeIstBuchungHinzu($budget['id'], 1000.01);

        $this->assertSame('ueberschritten', berechneStatus($budget));
    }

    public function testGenauAufSollIstWarnungNichtUeberschritten(): void
    {
        // Ist == Soll ist NICHT "Ist > Soll" - Grenzfall für die Status-Reihenfolge.
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', '2030-12-31');
        $this->fuegeIstBuchungHinzu($budget['id'], 1000.00);

        $this->assertSame('warnung', berechneStatus($budget));
    }

    public function testAbgelaufenGewinntTrotzNiedrigemVerbrauch(): void
    {
        $gestern = date('Y-m-d', strtotime('-1 day'));
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', $gestern);
        $this->fuegeIstBuchungHinzu($budget['id'], 10.00); // nur 1 % Verbrauch

        $this->assertSame('abgelaufen', berechneStatus($budget));
    }

    public function testAmLetztenGueltigenTagNochNichtAbgelaufen(): void
    {
        // Grenzfall: gueltig_bis = heute -> noch gültig, erst der Folgetag
        // zählt als abgelaufen (Regel: heute > gueltig_bis).
        $heute = date('Y-m-d');
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', $heute);
        $this->fuegeIstBuchungHinzu($budget['id'], 10.00);

        $this->assertNotSame('abgelaufen', berechneStatus($budget));
    }

    public function testWarnungDurchHochrechnungTrotzNiedrigemVerbrauch(): void
    {
        $budget = $this->legeTestBudgetAn(1000.00, '2020-01-01', '2030-12-31');
        $this->fuegeIstBuchungHinzu($budget['id'], 100.00); // nur 10 % bisher

        $stmt = $this->pdo->prepare(
            'INSERT INTO forecast (budget_id, monat, betrag) VALUES (:id, :monat, :betrag)'
        );
        $stmt->execute(['id' => $budget['id'], 'monat' => date('Y-m-01'), 'betrag' => 950.00]);

        // Hochrechnung = 100 + 950 = 1050 > 1000 Soll -> Warnung, obwohl
        // der bisherige Verbrauch allein nur 10 % wäre.
        $this->assertSame('warnung', berechneStatus($budget));
    }
}
