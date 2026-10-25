<?php
declare(strict_types=1);

namespace Malia\Models;

/** Ein Forecast = eine erwartete zukünftige Ausgabe zu einem Budget. */
class Forecast
{
    public function __construct(
        public int $id,
        public int $budgetId,
        public float $betrag,
        public string $beschreibung,
        public string $datum,          // Format YYYY-MM-DD
        public int $erfasstVon
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['budget_id'],
            (float) $row['betrag'],
            $row['beschreibung'],
            $row['datum'],
            (int) $row['erfasst_von']
        );
    }
}
