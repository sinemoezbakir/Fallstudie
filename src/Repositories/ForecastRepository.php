<?php
declare(strict_types=1);

namespace Malia\Repositories;

use Malia\Models\Forecast;
use PDO;

/**
 * Datenbankzugriff für forecasts.
 * Wie bei den Ist-Werten ergibt sich der Owner über das Budget (JOIN).
 */
class ForecastRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Forecast $f): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO forecasts (budget_id, betrag, beschreibung, datum, erfasst_von)
             VALUES (:budget, :betrag, :beschr, :datum, :erfasser)'
        );
        $stmt->execute([
            'budget' => $f->budgetId, 'betrag' => $f->betrag, 'beschr' => $f->beschreibung,
            'datum' => $f->datum, 'erfasser' => $f->erfasstVon,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Ohne Owner-Filter: nur für interne Nutzung durch Services. */
    public function findById(int $id): ?Forecast
    {
        $stmt = $this->pdo->prepare('SELECT * FROM forecasts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Forecast::fromRow($row) : null;
    }

    /** Für Owner: liefert den Forecast nur, wenn sein Budget diesem Owner gehört. */
    public function findByIdAndOwner(int $id, int $ownerId): ?Forecast
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.* FROM forecasts f
             JOIN budgets b ON b.id = f.budget_id
             WHERE f.id = :id AND b.owner_id = :owner'
        );
        $stmt->execute(['id' => $id, 'owner' => $ownerId]);
        $row = $stmt->fetch();
        return $row ? Forecast::fromRow($row) : null;
    }

    /** Für Owner: Forecasts eines Budgets, aber nur wenn das Budget dem Owner gehört. */
    public function findByBudgetAndOwner(int $budgetId, int $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.* FROM forecasts f
             JOIN budgets b ON b.id = f.budget_id
             WHERE f.budget_id = :budget AND b.owner_id = :owner
             ORDER BY f.datum, f.id'
        );
        $stmt->execute(['budget' => $budgetId, 'owner' => $ownerId]);
        return array_map([Forecast::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Für Owner: alle eigenen Forecasts über alle Budgets. */
    public function findByOwner(int $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.* FROM forecasts f
             JOIN budgets b ON b.id = f.budget_id
             WHERE b.owner_id = :owner
             ORDER BY f.datum, f.id'
        );
        $stmt->execute(['owner' => $ownerId]);
        return array_map([Forecast::class, 'fromRow'], $stmt->fetchAll());
    }

    public function update(Forecast $f): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE forecasts SET betrag = :betrag, beschreibung = :beschr, datum = :datum WHERE id = :id'
        );
        $stmt->execute([
            'betrag' => $f->betrag, 'beschr' => $f->beschreibung, 'datum' => $f->datum, 'id' => $f->id,
        ]);
    }
}
