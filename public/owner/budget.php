<?php
/**
 * Owner: Budget-Detailseite - Kennzahlen, alle Ist-Buchungen, verfügbares
 * Budget (MB-041). Rechnet bei jedem Aufruf live aus der DB ("Echtzeit",
 * konzept.md Abschnitt 4).
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_OWNER);

require_once __DIR__ . '/../../src/budget.php';
require_once __DIR__ . '/../../src/ui.php';

$benutzerId = eingeloggteBenutzerId();
$budgetId = (int) ($_GET['id'] ?? 0);

// Bricht selbst mit 403 ab, falls fremd oder nicht vorhanden (Risiko R3).
$budget = ladeEigenesBudget($budgetId, $benutzerId);
$k = kennzahlenFuerBudget($budget);

$buchungenStmt = $pdo->prepare('SELECT * FROM ist_buchung WHERE budget_id = :id ORDER BY datum DESC, id DESC');
$buchungenStmt->execute(['id' => $budget['id']]);
$buchungen = $buchungenStmt->fetchAll();

$istAbgelaufen = istBudgetAbgelaufen($budget);

$pageTitle = $budget['name'];
require __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($budget['name']) ?></h1>
        <div class="text-muted"><?= htmlspecialchars($budget['bereich']) ?> ·
            gültig <?= htmlspecialchars($budget['gueltig_von']) ?> bis <?= htmlspecialchars($budget['gueltig_bis']) ?>
        </div>
    </div>
    <?= statusBadge($k['status']) ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Soll</div>
        <div class="zahl-gross h4 mb-0"><?= number_format((float) $budget['soll_betrag'], 2, ',', '.') ?> €</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Ist</div>
        <div class="zahl-gross h4 mb-0"><?= number_format($k['ist'], 2, ',', '.') ?> €</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Verfügbar</div>
        <div class="zahl-gross h4 mb-0"><?= number_format($k['verfuegbar'], 2, ',', '.') ?> €</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">
        <?= $k['tage_bis_verfall'] >= 0 ? 'Noch Tage' : 'Abgelaufen seit' ?>
    </div>
        <div class="zahl-gross h4 mb-0"><?= abs($k['tage_bis_verfall']) ?></div></div></div>
</div>

<?= verbrauchsBalken($k['verbrauch_prozent']) ?>
<div class="small text-muted mb-4"><?= $k['verbrauch_prozent'] ?>% verbraucht</div>

<?php if (!$istAbgelaufen): ?>
<div class="mb-4">
    <a href="/owner/ist-erfassen.php?budget_id=<?= (int) $budget['id'] ?>" class="btn" style="background-color: var(--sage); color: #fff;">+ Ausgabe erfassen</a>
    <a href="/owner/forecast.php?budget_id=<?= (int) $budget['id'] ?>" class="btn" style="border: 1px solid var(--line);">Forecast eintragen</a>
</div>
<?php endif; ?>

<h2 class="h5 mb-3">Alle Ausgaben</h2>
<table class="table">
    <thead>
        <tr><th>Datum</th><th>Beschreibung</th><th>Betrag</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($buchungen as $buchung): ?>
            <tr>
                <td><?= htmlspecialchars($buchung['datum']) ?></td>
                <td>
                    <?= htmlspecialchars($buchung['beschreibung']) ?>
                    <?php if ($buchung['urspruenglicher_betrag'] !== null): ?>
                        <br><small class="text-muted">
                            korrigiert von <?= number_format((float) $buchung['urspruenglicher_betrag'], 2, ',', '.') ?> €
                            am <?= htmlspecialchars($buchung['korrigiert_am']) ?> –
                            Grund: <?= htmlspecialchars($buchung['korrektur_grund']) ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td><?= number_format((float) $buchung['betrag'], 2, ',', '.') ?> €</td>
                <td>
                    <?php if (!$istAbgelaufen): ?>
                        <a href="/owner/ist-korrigieren.php?id=<?= (int) $buchung['id'] ?>" class="small">korrigieren</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($buchungen)): ?>
            <tr><td colspan="4" class="text-center text-muted">Noch keine Ausgaben erfasst.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
