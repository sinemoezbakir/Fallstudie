<?php
declare(strict_types=1);

namespace Malia\Models;

/** Ein Budget (Soll-Wert) mit Verfallszeit, einem Owner zugewiesen. */
class Budget
{
    public function __construct(
        public int $id,
        public string $name,
        public string $bereich,
        public float $sollBetrag,
        public string $gueltigVon,     // Format YYYY-MM-DD
        public string $gueltigBis,     // Verfallszeit, Format YYYY-MM-DD
        public int $ownerId,
        public int $erstelltVon
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['name'],
            $row['bereich'],
            (float) $row['soll_betrag'],
            $row['gueltig_von'],
            $row['gueltig_bis'],
            (int) $row['owner_id'],
            (int) $row['erstellt_von']
        );
    }
}
