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

// Für MB-043 (Soll-Ist-Diagramm) und MB-044 (Forecast-Ist-Soll-Vergleich).
$chartSollIst = [
    'soll' => (float) $budget['soll_betrag'],
    'ist' => $k['ist'],
];
$chartHochrechnung = [
    'soll' => (float) $budget['soll_betrag'],
    'ist' => $k['ist'],
    'offener_forecast' => $k['offener_forecast'],
    'hochrechnung' => $k['hochrechnung'],
];

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
    <a href="/owner/ist-erfassen.php?budget_id=<?= (int) $budget['id'] ?>" class="btn btn-maliá">+ Ausgabe erfassen</a>
    <a href="/owner/forecast.php?budget_id=<?= (int) $budget['id'] ?>" class="btn btn-maliá-outline">Forecast eintragen</a>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3">
            <h2 class="h6">Soll-Ist-Vergleich</h2>
            <canvas id="chartSollIst" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h2 class="h6">Forecast-Ist-Soll-Vergleich (Hochrechnung)</h2>
            <canvas id="chartHochrechnung" height="200"></canvas>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const datenSollIst = <?= json_encode($chartSollIst) ?>;
const datenHochrechnung = <?= json_encode($chartHochrechnung) ?>;
const farbeSage = getComputedStyle(document.documentElement).getPropertyValue('--sage').trim();
const farbeRose = getComputedStyle(document.documentElement).getPropertyValue('--rose').trim();
const farbeMuted = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim();

// MB-043: Soll-Ist-Diagramm
new Chart(document.getElementById('chartSollIst'), {
    type: 'bar',
    data: {
        labels: ['Soll', 'Ist'],
        datasets: [{
            data: [datenSollIst.soll, datenSollIst.ist],
            backgroundColor: [farbeMuted, datenSollIst.ist > datenSollIst.soll ? farbeRose : farbeSage],
        }],
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
    },
});

// MB-044: Forecast-Ist-Soll-Vergleich - zeigt, ob Ist + offener Forecast
// (Hochrechnung) das Soll übersteigen würde.
new Chart(document.getElementById('chartHochrechnung'), {
    type: 'bar',
    data: {
        labels: ['Soll', 'Ist', 'Hochrechnung'],
        datasets: [{
            data: [datenHochrechnung.soll, datenHochrechnung.ist, datenHochrechnung.hochrechnung],
            backgroundColor: [
                farbeMuted,
                farbeSage,
                datenHochrechnung.hochrechnung > datenHochrechnung.soll ? farbeRose : farbeSage,
            ],
        }],
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
    },
});
</script>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
