<?php
/**
 * Owner: eine bestehende Ist-Buchung korrigieren (MB-035).
 *
 * Der neue Betrag überschreibt den alten, aber der ursprüngliche Betrag,
 * das Korrekturdatum und der Grund werden zusätzlich gespeichert
 * (konzept.md Abschnitt 3, Entscheidung E-006). Der ursprüngliche Betrag
 * wird nur beim ERSTEN Korrigieren gesetzt - eine zweite Korrektur
 * überschreibt ihn nicht nochmal, sonst ginge der wirklich ursprüngliche
 * Wert verloren.
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_OWNER);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$benutzerId = eingeloggteBenutzerId();
$buchungId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Prüft gleichzeitig, dass die Buchung zu einem Budget dieses Owners gehört.
$buchung = ladeEigeneBuchung($buchungId, $benutzerId);

$budgetStmt = $pdo->prepare('SELECT * FROM budget WHERE id = :id');
$budgetStmt->execute(['id' => $buchung['budget_id']]);
$budget = $budgetStmt->fetch();

$istAbgelaufen = istBudgetAbgelaufen($budget);
$fehler = null;

if ($istAbgelaufen) {
    $fehler = 'Dieses Budget ist abgelaufen, Buchungen können nicht mehr korrigiert werden.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $neuerBetrag = $_POST['betrag'] ?? '';
    $grund = trim($_POST['grund'] ?? '');

    if (!is_numeric($neuerBetrag) || (float) $neuerBetrag <= 0) {
        $fehler = 'Betrag muss eine positive Zahl sein.';
    } elseif ($grund === '') {
        $fehler = 'Bitte einen Grund für die Korrektur angeben.';
    } elseif (mb_strlen($grund) > 255) {
        $fehler = 'Grund darf höchstens 255 Zeichen lang sein.';
    } else {
        // Ursprünglichen Betrag nur beim ersten Mal festhalten.
        $urspruenglicherBetrag = $buchung['urspruenglicher_betrag'] ?? $buchung['betrag'];

        $stmt = $pdo->prepare(
            'UPDATE ist_buchung
             SET betrag = :neuer_betrag,
                 urspruenglicher_betrag = :urspruenglicher_betrag,
                 korrigiert_am = NOW(),
                 korrektur_grund = :grund
             WHERE id = :id'
        );
        $stmt->execute([
            'neuer_betrag' => round((float) $neuerBetrag, 2),
            'urspruenglicher_betrag' => $urspruenglicherBetrag,
            'grund' => $grund,
            'id' => $buchung['id'],
        ]);

        setzeFlash('erfolg', 'Buchung wurde korrigiert.');
        header('Location: /owner/budget.php?id=' . $budget['id']);
        exit;
    }
}

$pageTitle = 'Ist-Wert korrigieren';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Buchung korrigieren – <?= htmlspecialchars($budget['name']) ?></h1>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<?php if (!$istAbgelaufen): ?>
<div class="card p-4" style="max-width: 500px;">
    <p class="text-muted">
        Aktuell: <?= number_format((float) $buchung['betrag'], 2, ',', '.') ?> €
        am <?= htmlspecialchars($buchung['datum']) ?> –
        <?= htmlspecialchars($buchung['beschreibung']) ?>
        <?php if ($buchung['urspruenglicher_betrag'] !== null): ?>
            <br><small>Bereits einmal korrigiert, ursprünglich:
            <?= number_format((float) $buchung['urspruenglicher_betrag'], 2, ',', '.') ?> €</small>
        <?php endif; ?>
    </p>

    <form method="post" action="/owner/ist-korrigieren.php">
        <input type="hidden" name="id" value="<?= (int) $buchung['id'] ?>">
        <div class="mb-3">
            <label for="betrag" class="form-label">Neuer Betrag (€)</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="betrag" name="betrag"
                   value="<?= htmlspecialchars((string) $buchung['betrag']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="grund" class="form-label">Grund der Korrektur</label>
            <input type="text" class="form-control" id="grund" name="grund" maxlength="255" required>
        </div>
        <button type="submit" class="btn" style="background-color: var(--sage); color: #fff;">Korrektur speichern</button>
        <a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="btn" style="border: 1px solid var(--line);">Abbrechen</a>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
