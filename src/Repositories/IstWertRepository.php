<?php
declare(strict_types=1);

namespace Malia\Repositories;

use Malia\Models\IstWert;
use PDO;

/**
 * Datenbankzugriff für ist_werte.
 * Ein Ist-Wert hat selbst keinen Owner; der Owner ergibt sich über das Budget.
 * Deshalb verbinden (JOIN) die Owner-Methoden mit budgets und filtern nach owner_id.
 */
class IstWertRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(IstWert $w): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ist_werte (budget_id, betrag, beschreibung, datum, erfasst_von)
             VALUES (:budget, :betrag, :beschr, :datum, :erfasser)'
        );
        $stmt->execute([
            'budget' => $w->budgetId, 'betrag' => $w->betrag, 'beschr' => $w->beschreibung,
            'datum' => $w->datum, 'erfasser' => $w->erfasstVon,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Ohne Owner-Filter: nur für interne Nutzung durch Services. */
    public function findById(int $id): ?IstWert
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ist_werte WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? IstWert::fromRow($row) : null;
    }

    /** Für Owner: liefert den Ist-Wert nur, wenn sein Budget diesem Owner gehört. */
    public function findByIdAndOwner(int $id, int $ownerId): ?IstWert
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.* FROM ist_werte w
             JOIN budgets b ON b.id = w.budget_id
             WHERE w.id = :id AND b.owner_id = :owner'
        );
        $stmt->execute(['id' => $id, 'owner' => $ownerId]);
        $row = $stmt->fetch();
        return $row ? IstWert::fromRow($row) : null;
    }

    /** Für Owner: Ist-Werte eines Budgets, aber nur wenn das Budget dem Owner gehört. */
    public function findByBudgetAndOwner(int $budgetId, int $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.* FROM ist_werte w
             JOIN budgets b ON b.id = w.budget_id
             WHERE w.budget_id = :budget AND b.owner_id = :owner
             ORDER BY w.datum DESC, w.id DESC'
        );
        $stmt->execute(['budget' => $budgetId, 'owner' => $ownerId]);
        return array_map([IstWert::class, 'fromRow'], $stmt->fetchAll());
    }

    /** Für Owner: alle eigenen Ist-Werte über alle Budgets. */
    public function findByOwner(int $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT w.* FROM ist_werte w
             JOIN budgets b ON b.id = w.budget_id
             WHERE b.owner_id = :owner
             ORDER BY w.datum DESC, w.id DESC'
        );
        $stmt->execute(['owner' => $ownerId]);
        return array_map([IstWert::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Korrigiert den Betrag eines Ist-Werts und protokolliert den Altwert.
     * Beides passiert in EINER Transaktion: entweder wird alles gespeichert oder nichts.
     * Gehört der Ist-Wert nicht zu einem Budget dieses Owners, passiert nichts (false).
     */
    public function correct(int $istWertId, int $ownerId, float $neuerBetrag, string $grund, int $userId): bool
    {
        $this->pdo->beginTransaction();
        try {
            // FOR UPDATE sperrt die Zeile, damit der gelesene Altwert nicht gleichzeitig geändert wird
            $stmt = $this->pdo->prepare(
                'SELECT w.betrag, w.datum FROM ist_werte w
                 JOIN budgets b ON b.id = w.budget_id
                 WHERE w.id = :id AND b.owner_id = :owner
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $istWertId, 'owner' => $ownerId]);
            $alt = $stmt->fetch();
            if (!$alt) {
                $this->pdo->rollBack();
                return false;
            }

            // 1. Altwert, altes Datum und Grund speichern (korrigiert_am setzt die Datenbank)
            $stmt = $this->pdo->prepare(
                'INSERT INTO ist_wert_korrekturen (ist_wert_id, alter_betrag, altes_datum, grund, korrigiert_von)
                 VALUES (:id, :alt, :datum, :grund, :user)'
            );
            $stmt->execute([
                'id' => $istWertId, 'alt' => $alt['betrag'], 'datum' => $alt['datum'],
                'grund' => $grund, 'user' => $userId,
            ]);

            // 2. Neuen Betrag setzen
            $stmt = $this->pdo->prepare('UPDATE ist_werte SET betrag = :betrag WHERE id = :id');
            $stmt->execute(['betrag' => $neuerBetrag, 'id' => $istWertId]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $ex) {
            $this->pdo->rollBack();
            throw $ex;
        }
    }

    /**
     * Ändert Betrag, Beschreibung und Datum.
     * Das Speichern des Altwerts (Korrektur) übernimmt später ein Service.
     */
    public function update(IstWert $w): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ist_werte SET betrag = :betrag, beschreibung = :beschr, datum = :datum WHERE id = :id'
        );
        $stmt->execute([
            'betrag' => $w->betrag, 'beschr' => $w->beschreibung, 'datum' => $w->datum, 'id' => $w->id,
        ]);
    }
}
