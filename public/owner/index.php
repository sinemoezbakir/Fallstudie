<?php
declare(strict_types=1);

use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Repositories\ForecastRepository;
use Malia\Repositories\IstWertRepository;
use Malia\Services\BudgetService;

require __DIR__ . '/../bootstrap.php';

// Nur OWNER. Alle Abfragen unten filtern nach der owner_id des angemeldeten Benutzers.
$user = $auth->requireRole(User::ROLE_OWNER);   // $user wird vom Layout gebraucht

$service = new BudgetService();

$budgets   = (new BudgetRepository($pdo))->findByOwner($user->id);
$istWerte  = (new IstWertRepository($pdo))->findByOwner($user->id);
$forecasts = (new ForecastRepository($pdo))->findByOwner($user->id);

// Ist-Werte und Forecasts nach Budget gruppieren (je Budget eine Liste)
$istByBudget = [];
foreach ($istWerte as $w) {
    $istByBudget[$w->budgetId][] = $w;
}
$forecastByBudget = [];
foreach ($forecasts as $f) {
    $forecastByBudget[$f->budgetId][] = $f;
}

// Kennzahl-Zeilen je Budget und Gesamtsummen für die Kacheln oben
$zeilen    = [];
$gesamtSoll = 0.0;
$gesamtIst  = 0.0;
foreach ($budgets as $b) {
    $ist = $service->summeIst($istByBudget[$b->id] ?? []);
    $zeilen[] = [
        'budget'  => $b,
        'ist'     => $ist,
        'rest'    => $service->restbudget($b->sollBetrag, $ist),
        'prozent' => $service->auslastungProzent($b->sollBetrag, $ist),
        'status'  => $service->status($b->sollBetrag, $ist, $b->gueltigBis),
    ];
    $gesamtSoll += $b->sollBetrag;
    $gesamtIst  += $ist;
}
$gesamtRest    = $service->restbudget($gesamtSoll, $gesamtIst);
$gesamtProzent = $service->auslastungProzent($gesamtSoll, $gesamtIst);

// Balkenfüllung je Status (Farbe + Muster, siehe style.css)
$balkenKlasse = [
    'IM_RAHMEN' => 'balken-ok', 'WARNUNG' => 'balken-warning',
    'UEBERSCHRITTEN' => 'balken-over', 'ABGELAUFEN' => 'balken-expired',
];

// Daten für das Donut-Diagramm: Gesamt-Soll aufgeteilt in "verbraucht" und "übrig"
$donutJson = json_encode([
    'ist'  => $gesamtIst,
    'rest' => max(0, $gesamtRest),   // bei Überschreitung gibt es keinen Rest-Abschnitt
    'text' => $gesamtProzent === null ? '–' : number_format($gesamtProzent, 0, ',', '.') . ' %',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$pageTitle = 'Meine Budgets';
require __DIR__ . '/../../src/layout/header.php';
?>
<p class="page-hello mb-0">Hallo, <?= e($user->username) ?></p>
<h1 class="h3 mb-4">Meine Budgets</h1>

<?php if ($zeilen): ?>
<!-- Kennzahlen über alle eigenen Budgets -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="kpi">
        <div class="kpi-label">Gesamt-Soll</div><div class="kpi-wert"><?= e(euro($gesamtSoll)) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi">
        <div class="kpi-label">Ist (Ausgaben)</div><div class="kpi-wert"><?= e(euro($gesamtIst)) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi">
        <div class="kpi-label">Restbudget</div>
        <div class="kpi-wert <?= $gesamtRest < 0 ? 'text-over' : '' ?>">
            <?= $gesamtRest < 0 ? '<span aria-hidden="true">&#10006;&#xFE0E;</span> ' : '' ?><?= e(euro($gesamtRest)) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi">
        <div class="kpi-label">Auslastung</div>
        <div class="kpi-wert"><?= $gesamtProzent === null ? '–' : e(number_format($gesamtProzent, 1, ',', '.')) . ' %' ?></div></div></div>
</div>

<div class="row g-4 mb-4">
    <!-- Donut: Verhältnis Ist zu Restbudget -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Budget-Verbrauch</h2>
            <div class="donut-wrap">
                <canvas id="chartDonut" role="img"
                        aria-label="<?= e('Von ' . euro($gesamtSoll) . ' sind ' . euro($gesamtIst) . ' verbraucht, Restbudget ' . euro($gesamtRest)) ?>"></canvas>
                <div class="donut-mitte" id="donutMitte"></div>
            </div>
        </div></div>
    </div>

    <!-- Budgetkarten -->
    <div class="col-lg-8">
        <div class="row g-3">
        <?php foreach ($zeilen as $z): $b = $z['budget']; $pct = $z['prozent'] ?? 0; ?>
            <div class="col-md-6">
                <a class="budget-karte" href="budget.php?id=<?= e($b->id) ?>">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="budget-karte-name"><?= e($b->name) ?></div>
                            <div class="small text-muted"><?= e($b->bereich) ?> · gültig bis <?= e($b->gueltigBis) ?></div>
                        </div>
                        <?= statusBadge($z['status']) ?>
                    </div>
                    <div class="progress mb-1" role="progressbar" aria-label="Auslastung"
                         aria-valuenow="<?= e(round($pct)) ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar <?= e($balkenKlasse[$z['status']]) ?>"
                             style="width: <?= e(min(100, round($pct, 1))) ?>%"></div>
                    </div>
                    <div class="small mb-2"><?= $z['prozent'] === null ? '–' : e(number_format($z['prozent'], 1, ',', '.')) . ' %' ?> ausgelastet</div>
                    <dl class="budget-karte-zahlen mb-0">
                        <div><dt>Soll</dt><dd><?= e(euro($b->sollBetrag)) ?></dd></div>
                        <div><dt>Ist</dt><dd><?= e(euro($z['ist'])) ?></dd></div>
                        <div><dt>Rest</dt>
                            <dd class="<?= $z['rest'] < 0 ? 'text-over' : '' ?>"><?= e(euro($z['rest'])) ?></dd></div>
                    </dl>
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Daten kommen vom Server (BudgetService)
const donut = <?= $donutJson ?>;
const css   = getComputedStyle(document.documentElement);
const farbe = (v) => css.getPropertyValue(v).trim();
const euro  = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

Chart.defaults.color = farbe('--malia-text');
Chart.defaults.font.size = 13;
document.getElementById('donutMitte').textContent = donut.text;

new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Ist (verbraucht)', 'Restbudget'],
        datasets: [{
            data: [donut.ist, donut.rest],
            backgroundColor: [farbe('--malia-chart-ist'), farbe('--malia-chart-soll')],
            borderColor: farbe('--malia-text'),
            borderWidth: 2
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 24, boxHeight: 16 } },
            tooltip: { callbacks: { label: (ctx) => ctx.label + ': ' + euro.format(ctx.parsed) } }
        }
    }
});
</script>
<?php else: ?>
    <div class="alert alert-info">Dir sind noch keine Budgets zugewiesen.</div>
<?php endif; ?>
<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
