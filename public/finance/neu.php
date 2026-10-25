<?php
/**
 * Finance: Neues Budget anlegen und einem Owner zuweisen (MB-031).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_FINANCE);

$owners = $pdo->query("SELECT id, anzeigename FROM benutzer WHERE rolle = 'OWNER' ORDER BY anzeigename")->fetchAll();
$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pruefung = pruefeBudget($_POST, array_column($owners, 'id'));
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        $stmt = $pdo->prepare(
            'INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id)
             VALUES (:name, :bereich, :soll_betrag, :gueltig_von, :gueltig_bis, :owner_id)'
        );
        $stmt->execute($pruefung['werte']);

        setzeFlash('erfolg', "Budget „{$pruefung['werte']['name']}“ wurde angelegt und zugewiesen.");
        header('Location: /finance/budget.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

$werte = ['gueltig_von' => date('Y-m-d'), 'gueltig_bis' => date('Y') . '-12-31'];
$aktion = '/finance/neu.php';
$knopfText = 'Budget anlegen';

$pageTitle = 'Neues Budget';
require __DIR__ . '/../../templates/header.php';
?>

<a href="/finance/" class="zurueck-link">← Alle Budgets</a>
<div class="seitenkopf">
    <h1>Neues Budget</h1>
    <p class="lead">Lege ein Budget an und weise es einer verantwortlichen Person zu.</p>
</div>

<?php require __DIR__ . '/../../templates/budget-formular.php'; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
