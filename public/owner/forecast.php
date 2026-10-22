<?php
/**
 * Owner: Forecast pro Monat eintragen und anzeigen (MB-042).
 *
 * Pro (budget_id, monat) existiert genau ein Eintrag (UNIQUE-Constraint in
 * der DB) - neu eintragen überschreibt den bestehenden Wert für denselben
 * Monat (konzept.md Abschnitt 3).
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_OWNER);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$benutzerId = eingeloggteBenutzerId();
$budgetId = (int) ($_GET['budget_id'] ?? $_POST['budget_id'] ?? 0);
$budget = ladeEigenesBudget($budgetId, $benutzerId);

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monatEingabe = $_POST['monat'] ?? ''; // Format aus <input type="month">: YYYY-MM
    $betrag = $_POST['betrag'] ?? '';

    if (!preg_match('/^\d{4}-\d{2}$/', $monatEingabe)) {
        $fehler = 'Bitte einen gültigen Monat auswählen.';
    } elseif (!is_numeric($betrag) || (float) $betrag < 0) {
        $fehler = 'Betrag muss eine Zahl >= 0 sein.';
    } else {
        $monat = $monatEingabe . '-01';

        $stmt = $pdo->prepare(
            'INSERT INTO forecast (budget_id, monat, betrag)
             VALUES (:budget_id, :monat, :betrag)
             ON DUPLICATE KEY UPDATE betrag = VALUES(betrag)'
        );
        $stmt->execute([
            'budget_id' => $budget['id'],
            'monat' => $monat,
            'betrag' => round((float) $betrag, 2),
        ]);

        setzeFlash('erfolg', 'Forecast wurde gespeichert.');
        header('Location: /owner/forecast.php?budget_id=' . $budget['id']);
        exit;
    }
}

$forecastStmt = $pdo->prepare('SELECT * FROM forecast WHERE budget_id = :id ORDER BY monat');
$forecastStmt->execute(['id' => $budget['id']]);
$forecasts = $forecastStmt->fetchAll();

$pageTitle = 'Forecast';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Forecast – <?= htmlspecialchars($budget['name']) ?></h1>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<div class="card p-4 mb-4" style="max-width: 500px;">
    <form method="post" action="/owner/forecast.php">
        <input type="hidden" name="budget_id" value="<?= (int) $budget['id'] ?>">
        <div class="row">
            <div class="col-6 mb-3">
                <label for="monat" class="form-label">Monat</label>
                <input type="month" class="form-control" id="monat" name="monat" required>
            </div>
            <div class="col-6 mb-3">
                <label for="betrag" class="form-label">Betrag (€)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="betrag" name="betrag" required>
            </div>
        </div>
        <button type="submit" class="btn" style="background-color: var(--sage); color: #fff;">Speichern</button>
        <a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="btn" style="border: 1px solid var(--line);">Zurück</a>
    </form>
</div>

<h2 class="h5 mb-3">Eingetragene Forecasts</h2>
<table class="table" style="max-width: 400px;">
    <thead><tr><th>Monat</th><th>Betrag</th></tr></thead>
    <tbody>
        <?php foreach ($forecasts as $f): ?>
            <tr>
                <td><?= htmlspecialchars(date('m/Y', strtotime($f['monat']))) ?></td>
                <td><?= number_format((float) $f['betrag'], 2, ',', '.') ?> €</td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($forecasts)): ?>
            <tr><td colspan="2" class="text-center text-muted">Noch kein Forecast erfasst.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
