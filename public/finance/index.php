<?php
/**
 * Finance: Übersicht aller Budgets mit Soll-Ist-Vergleich (MB-032).
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_FINANCE);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$bereichFilter = $_GET['bereich'] ?? '';
$gueltigeBereiche = ['STUDIO', 'CAFE', 'MARKETING'];

if (in_array($bereichFilter, $gueltigeBereiche, true)) {
    $stmt = $pdo->prepare(
        'SELECT b.*, u.anzeigename AS owner_name
         FROM budget b JOIN benutzer u ON u.id = b.owner_id
         WHERE b.bereich = :bereich
         ORDER BY b.gueltig_bis'
    );
    $stmt->execute(['bereich' => $bereichFilter]);
} else {
    $stmt = $pdo->query(
        'SELECT b.*, u.anzeigename AS owner_name
         FROM budget b JOIN benutzer u ON u.id = b.owner_id
         ORDER BY b.gueltig_bis'
    );
}

$budgets = $stmt->fetchAll();

$pageTitle = 'Budget-Übersicht';
require __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Budget-Übersicht</h1>
    <a href="/finance/neu.php" class="btn" style="background-color: var(--sage); color: #fff;">+ Neues Budget</a>
</div>

<form method="get" action="/finance/" class="mb-3" style="max-width: 250px;">
    <select class="form-select" name="bereich" onchange="this.form.submit()">
        <option value="">Alle Bereiche</option>
        <option value="STUDIO" <?= $bereichFilter === 'STUDIO' ? 'selected' : '' ?>>Studio</option>
        <option value="CAFE" <?= $bereichFilter === 'CAFE' ? 'selected' : '' ?>>Café</option>
        <option value="MARKETING" <?= $bereichFilter === 'MARKETING' ? 'selected' : '' ?>>Marketing</option>
    </select>
</form>

<table class="table align-middle">
    <thead>
        <tr>
            <th>Budget</th>
            <th>Bereich</th>
            <th>Owner</th>
            <th>Soll</th>
            <th>Ist</th>
            <th>Verbrauch</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($budgets as $budget): ?>
            <?php $k = kennzahlenFuerBudget($budget); ?>
            <tr>
                <td><?= htmlspecialchars($budget['name']) ?></td>
                <td><?= htmlspecialchars($budget['bereich']) ?></td>
                <td><?= htmlspecialchars($budget['owner_name']) ?></td>
                <td><?= number_format((float) $budget['soll_betrag'], 2, ',', '.') ?> €</td>
                <td><?= number_format($k['ist'], 2, ',', '.') ?> €</td>
                <td style="min-width: 120px;">
                    <?= verbrauchsBalken($k['verbrauch_prozent']) ?>
                    <small><?= $k['verbrauch_prozent'] ?>%</small>
                </td>
                <td><?= statusBadge($k['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($budgets)): ?>
            <tr><td colspan="7" class="text-center text-muted">Keine Budgets in diesem Bereich.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
