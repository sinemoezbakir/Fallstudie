<?php
/**
 * Owner-Dashboard: Status aller eigenen Budgets (MB-040).
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_OWNER);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$benutzerId = eingeloggteBenutzerId();
$budgets = ladeBudgetsVonOwner($benutzerId);

$pageTitle = 'Mein Dashboard';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Meine Budgets</h1>

<div class="row g-3">
    <?php foreach ($budgets as $budget): ?>
        <?php $k = kennzahlenFuerBudget($budget); ?>
        <div class="col-md-6 col-lg-4">
            <a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="text-decoration-none" style="color: inherit;">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h6 mb-0"><?= htmlspecialchars($budget['name']) ?></h2>
                        <?= statusBadge($k['status']) ?>
                    </div>
                    <div class="text-muted small mb-2"><?= htmlspecialchars($budget['bereich']) ?></div>

                    <?= verbrauchsBalken($k['verbrauch_prozent']) ?>
                    <div class="d-flex justify-content-between small mt-1 mb-2">
                        <span><?= number_format($k['ist'], 2, ',', '.') ?> € von <?= number_format((float) $budget['soll_betrag'], 2, ',', '.') ?> €</span>
                        <span><?= $k['verbrauch_prozent'] ?>%</span>
                    </div>

                    <div class="small text-muted">
                        <?php if ($k['tage_bis_verfall'] >= 0): ?>
                            noch <?= $k['tage_bis_verfall'] ?> Tage
                        <?php else: ?>
                            seit <?= abs($k['tage_bis_verfall']) ?> Tagen abgelaufen
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

    <?php if (empty($budgets)): ?>
        <p class="text-muted">Dir sind noch keine Budgets zugewiesen.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
