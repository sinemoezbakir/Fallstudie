<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests für die Datentrennung (MB-022, Risiko R3, MB-050).
 * Prüft findeBudgetFuerOwner() direkt - die Funktion hinter
 * ladeEigenesBudget(), ohne den 403/exit-Seiteneffekt.
 */
final class DatentrennungTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    private function legeBenutzerAn(): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle)
             VALUES (:username, 'x', 'Test', 'OWNER')"
        );
        $stmt->execute(['username' => 'test_' . uniqid()]);

        return (int) $this->pdo->lastInsertId();
    }

    private function legeBudgetAn(int $ownerId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id)
             VALUES ('Test-Budget', 'STUDIO', 100.00, '2020-01-01', '2030-12-31', :owner)"
        );
        $stmt->execute(['owner' => $ownerId]);

        return (int) $this->pdo->lastInsertId();
    }

    public function testEigenesBudgetWirdGefunden(): void
    {
        $owner = $this->legeBenutzerAn();
        $budgetId = $this->legeBudgetAn($owner);

        $gefunden = findeBudgetFuerOwner($budgetId, $owner);

        $this->assertNotNull($gefunden);
        $this->assertSame($budgetId, (int) $gefunden['id']);
    }

    public function testFremdesBudgetWirdNichtGefunden(): void
    {
        $ownerA = $this->legeBenutzerAn();
        $ownerB = $this->legeBenutzerAn();
        $budgetVonA = $this->legeBudgetAn($ownerA);

        // Das ist der Kern von Risiko R3: Owner B versucht, Owner As
        // Budget-ID zu laden (z. B. durch Ändern der ID in der URL).
        $gefunden = findeBudgetFuerOwner($budgetVonA, $ownerB);

        $this->assertNull($gefunden);
    }

    public function testNichtExistierendeBudgetIdWirdNichtGefunden(): void
    {
        $owner = $this->legeBenutzerAn();

        $gefunden = findeBudgetFuerOwner(999999, $owner);

        $this->assertNull($gefunden);
    }

    public function testFremdeBuchungWirdNichtGefunden(): void
    {
        $ownerA = $this->legeBenutzerAn();
        $ownerB = $this->legeBenutzerAn();
        $budgetVonA = $this->legeBudgetAn($ownerA);

        $stmt = $this->pdo->prepare(
            "INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von)
             VALUES (:budget_id, 50.00, CURDATE(), 'Test', :erfasst_von)"
        );
        $stmt->execute(['budget_id' => $budgetVonA, 'erfasst_von' => $ownerA]);
        $buchungId = (int) $this->pdo->lastInsertId();

        $gefunden = findeBuchungFuerOwner($buchungId, $ownerB);

        $this->assertNull($gefunden);
    }
}
