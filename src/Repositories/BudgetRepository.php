<?php
declare(strict_types=1);

namespace Malia\Repositories;

use Malia\Models\Budget;
use PDO;

/** Datenbankzugriff für die Tabelle budgets. */
class BudgetRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Legt ein Budget an (Finance) und gibt die neue ID zurück. */
    public function create(Budget $b): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO budgets (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id, erstellt_von)
             VALUES (:name, :bereich, :soll, :von, :bis, :owner, :ersteller)'
        );
        $stmt->execute([
            'name' => $b->name, 'bereich' => $b->bereich, 'soll' => $b->sollBetrag,
            'von' => $b->gueltigVon, 'bis' => $b->gueltigBis,
            'owner' => $b->ownerId, 'ersteller' => $b->erstelltVon,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Ohne Owner-Filter: nur für Finance bestimmt. */
    public function findById(int $id): ?Budget
    {
        $stmt = $this->pdo->prepare('SELECT * FROM budgets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Budget::fromRow($row) : null;
    }

    /** Für Owner: liefert das Budget nur, wenn es diesem Owner gehört. */
    public function findByIdAndOwner(int $id, int $ownerId): ?Budget
    {
        $stmt = $this->pdo->prepare('SELECT * FROM budgets WHERE id = :id AND owner_id = :owner');
        $stmt->execute(['id' => $id, 'owner' => $ownerId]);
        $row = $stmt->fetch();
        return $row ? Budget::fromRow($row) : null;
    }

    /** Für Owner: alle eigenen Budgets. */
    public function findByOwner(int $ownerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM budgets WHERE owner_id = :owner ORDER BY gueltig_bis, name');
        $stmt->execute(['owner' => $ownerId]);
        return array_map([Budget::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Alle Budgets: nur für Finance (Soll-Ist-Vergleich). */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM budgets ORDER BY gueltig_bis, name')->fetchAll();
        return array_map([Budget::class, 'fromRow'], $rows);
    }

    /**
     * Nur für Finance: alle Budgets mit Ownername und Summe der Ist-Werte.
     * LEFT JOIN: Budgets ohne Ausgaben erscheinen trotzdem (Summe 0).
     * Liefert Arrays der Form ['budget' => Budget, 'ownerName' => string, 'ist' => float].
     */
    public function findAllWithIst(): array
    {
        $rows = $this->pdo->query(
            'SELECT b.*, u.username AS owner_name, COALESCE(SUM(w.betrag), 0) AS ist_summe
             FROM budgets b
             JOIN users u ON u.id = b.owner_id
             LEFT JOIN ist_werte w ON w.budget_id = b.id
             GROUP BY b.id, u.username
             ORDER BY b.gueltig_bis, b.name'
        )->fetchAll();

        return array_map(fn(array $row) => [
            'budget'    => Budget::fromRow($row),
            'ownerName' => $row['owner_name'],
            'ist'       => (float) $row['ist_summe'],
        ], $rows);
    }

    /** Nur für Finance: weist ein Budget einem anderen Owner zu. */
    public function assignOwner(int $budgetId, int $ownerId): void
    {
        $stmt = $this->pdo->prepare('UPDATE budgets SET owner_id = :owner WHERE id = :id');
        $stmt->execute(['owner' => $ownerId, 'id' => $budgetId]);
    }

    /** Ändert ein Budget (Finance). Der Ersteller bleibt unverändert. */
    public function update(Budget $b): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE budgets
             SET name = :name, bereich = :bereich, soll_betrag = :soll,
                 gueltig_von = :von, gueltig_bis = :bis, owner_id = :owner
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $b->name, 'bereich' => $b->bereich, 'soll' => $b->sollBetrag,
            'von' => $b->gueltigVon, 'bis' => $b->gueltigBis,
            'owner' => $b->ownerId, 'id' => $b->id,
        ]);
    }
}
