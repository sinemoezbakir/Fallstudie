<?php
/**
 * Datentrennung und Budget-Zugriff (Risiko R3: "Owner sieht fremde Daten").
 *
 * ladeEigenesBudget() ist die EINZIGE erlaubte Art, wie eine Owner-Seite ein
 * Budget anhand einer ID aus der URL lädt. Niemals direktes
 * "SELECT ... WHERE id = :id" ohne Owner-Prüfung - sonst reicht es, die ID
 * in der URL zu ändern, um fremde Budgets zu sehen.
 *
 * Die Kennzahlen-Berechnung (Ist, Verfügbar, Status, ...) kommt mit MB-033
 * dazu; diese Datei kümmert sich nur um den sicheren Zugriff.
 */

require_once __DIR__ . '/db.php';

/**
 * Lädt ein Budget, aber NUR wenn es dem angegebenen Owner gehört.
 * Gehört es jemand anderem (oder existiert es nicht), bricht die Anfrage
 * mit 403 ab - unabhängig davon, ob die ID in der URL manipuliert wurde.
 */
function ladeEigenesBudget(int $budgetId, int $ownerId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM budget WHERE id = :id AND owner_id = :owner_id');
    $stmt->execute(['id' => $budgetId, 'owner_id' => $ownerId]);
    $budget = $stmt->fetch();

    if (!$budget) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }

    return $budget;
}

/**
 * Lädt alle Budgets eines Owners (für das Dashboard, MB-040) - hier ist
 * keine zusätzliche Prüfung nötig, weil der Filter "WHERE owner_id = ..."
 * selbst schon die Trennung herstellt.
 */
function ladeBudgetsVonOwner(int $ownerId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM budget WHERE owner_id = :owner_id ORDER BY gueltig_bis');
    $stmt->execute(['owner_id' => $ownerId]);

    return $stmt->fetchAll();
}
