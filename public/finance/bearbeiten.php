<?php
/**
 * Finance: Budget bearbeiten (konzept.md Abschnitt 2: "Budget anlegen /
 * bearbeiten / Owner zuweisen"). Auch zum Verlängern eines abgelaufenen
 * Budgets: neues "Gültig bis" hebt die Sperre auf.
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_FINANCE);

$budget = ladeBudget((int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$owners = $pdo->query("SELECT id, anzeigename FROM benutzer WHERE rolle = 'OWNER' ORDER BY anzeigename")->fetchAll();
$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pruefung = pruefeBudget($_POST, array_column($owners, 'id'));
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        $stmt = $pdo->prepare(
            'UPDATE budget SET name = :name, bereich = :bereich, soll_betrag = :soll_betrag,
                gueltig_von = :gueltig_von, gueltig_bis = :gueltig_bis, owner_id = :owner_id
             WHERE id = :id'
        );
        $stmt->execute($pruefung['werte'] + ['id' => $budget['id']]);

        setzeFlash('erfolg', "Budget „{$pruefung['werte']['name']}“ wurde gespeichert.");
        header('Location: /finance/budget.php?id=' . $budget['id']);
        exit;
    }
}

$werte = $budget;
$werte['soll_betrag'] = number_format((float) $budget['soll_betrag'], 2, ',', '');
$budgetId = (int) $budget['id'];
$istBisher = berechneIst($budgetId);
$aktion = '/finance/bearbeiten.php';
$knopfText = 'Änderungen speichern';
$abbrechenLink = '/finance/budget.php?id=' . $budgetId;

$pageTitle = 'Budget bearbeiten';
require __DIR__ . '/../../templates/header.php';
?>

<a href="/finance/budget.php?id=<?= $budgetId ?>" class="zurueck-link">← <?= e($budget['name']) ?></a>
<div class="seitenkopf">
    <h1>Budget bearbeiten</h1>
    <p class="lead">Änderungen wirken sofort auf Kennzahlen, Status und die Sicht des Owners.</p>
</div>

<?php require __DIR__ . '/../../templates/budget-formular.php'; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
