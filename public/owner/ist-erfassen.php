<?php
/**
 * Owner: Ist-Wert für ein eigenes Budget erfassen (MB-034).
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_OWNER);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$benutzerId = eingeloggteBenutzerId();
$budgetId = (int) ($_GET['budget_id'] ?? $_POST['budget_id'] ?? 0);

// ladeEigenesBudget() bricht selbst mit 403 ab, falls das Budget nicht
// existiert oder jemand anderem gehört (Risiko R3).
$budget = ladeEigenesBudget($budgetId, $benutzerId);

$istAbgelaufen = istBudgetAbgelaufen($budget);
$fehler = null;

if ($istAbgelaufen) {
    $fehler = 'Dieses Budget ist abgelaufen und kann nicht mehr bebucht werden.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $betrag = $_POST['betrag'] ?? '';
    $datum = $_POST['datum'] ?? '';
    $beschreibung = trim($_POST['beschreibung'] ?? '');

    if (!is_numeric($betrag) || (float) $betrag <= 0) {
        $fehler = 'Betrag muss eine positive Zahl sein.';
    } elseif ($datum === '') {
        $fehler = 'Bitte ein Datum angeben.';
    } elseif ($beschreibung === '') {
        $fehler = 'Bitte eine Beschreibung angeben.';
    } elseif (mb_strlen($beschreibung) > 255) {
        $fehler = 'Beschreibung darf höchstens 255 Zeichen lang sein.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von)
             VALUES (:budget_id, :betrag, :datum, :beschreibung, :erfasst_von)'
        );
        $stmt->execute([
            'budget_id' => $budget['id'],
            'betrag' => round((float) $betrag, 2),
            'datum' => $datum,
            'beschreibung' => $beschreibung,
            'erfasst_von' => $benutzerId,
        ]);

        setzeFlash('erfolg', 'Ausgabe wurde erfasst.');
        header('Location: /owner/budget.php?id=' . $budget['id']);
        exit;
    }
}

$pageTitle = 'Ist-Wert erfassen';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Ausgabe erfassen – <?= htmlspecialchars($budget['name']) ?></h1>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<?php if (!$istAbgelaufen): ?>
<div class="card p-4" style="max-width: 500px;">
    <form method="post" action="/owner/ist-erfassen.php">
        <input type="hidden" name="budget_id" value="<?= (int) $budget['id'] ?>">
        <div class="mb-3">
            <label for="betrag" class="form-label">Betrag (€)</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="betrag" name="betrag" required>
        </div>
        <div class="mb-3">
            <label for="datum" class="form-label">Datum</label>
            <input type="date" class="form-control" id="datum" name="datum"
                   value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
            <label for="beschreibung" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" id="beschreibung" name="beschreibung" maxlength="255" required>
        </div>
        <button type="submit" class="btn btn-maliá">Speichern</button>
        <a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="btn btn-maliá-outline">Abbrechen</a>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
