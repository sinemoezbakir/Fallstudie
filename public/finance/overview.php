<?php
declare(strict_types=1);

use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Services\BudgetService;

require __DIR__ . '/../bootstrap.php';

// Nur FINANCE sieht alle Budgets im Soll-Ist-Vergleich
$user = $auth->requireRole(User::ROLE_FINANCE);   // $user wird vom Layout gebraucht

$rows    = (new BudgetRepository($pdo))->findAllWithIst();
$service = new BudgetService();

// Balkenfüllung je Status (Farbe + Muster, siehe style.css)
$balkenKlasse = [
    'IM_RAHMEN' => 'balken-ok', 'WARNUNG' => 'balken-warning',
    'UEBERSCHRITTEN' => 'balken-over', 'ABGELAUFEN' => 'balken-expired',
];

$pageTitle = 'Soll-Ist-Vergleich';

// Daten fürs Diagramm (Soll und Ist je Budget), als JSON mit maskierten Sonderzeichen
$chartJson = json_encode([
    'namen' => array_map(fn($r) => $r['budget']->name, $rows),
    'soll'  => array_map(fn($r) => $r['budget']->sollBetrag, $rows),
    'ist'   => array_map(fn($r) => $r['ist'], $rows),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

require __DIR__ . '/../../src/layout/header.php';
?>
<h1 class="h3 mb-4">Soll-Ist-Vergleich</h1>

<?php if ($rows): ?>
<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Soll und Ist je Budget</h2>
        <canvas id="chartFinance" height="110" role="img"
                aria-label="Balkendiagramm Soll und Ist je Budget. Die Werte stehen in der Tabelle darunter."></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const daten = <?= $chartJson ?>;
const css   = getComputedStyle(document.documentElement);
const farbe = (v) => css.getPropertyValue(v).trim();
const euro  = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

Chart.defaults.color = farbe('--malia-text');
Chart.defaults.font.size = 13;
new Chart(document.getElementById('chartFinance'), {
    type: 'bar',
    data: {
        labels: daten.namen,
        datasets: [
            { label: 'Soll', data: daten.soll, backgroundColor: farbe('--malia-chart-soll'),
              borderColor: farbe('--malia-text'), borderWidth: 2 },
            { label: 'Ist', data: daten.ist, backgroundColor: farbe('--malia-chart-ist'),
              borderColor: farbe('--malia-text'), borderWidth: 2 }
        ]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 24, boxHeight: 16 } },
            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + euro.format(c.parsed.y) } }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Betrag in Euro (€)' },
                 ticks: { callback: (w) => euro.format(w) } },
            x: { title: { display: true, text: 'Budget' } }
        }
    }
});
</script>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Budget</th><th>Owner</th>
                    <th class="text-end">Soll</th>
                    <th class="text-end">Ist (Summe)</th>
                    <th class="text-end">Differenz</th>
                    <th>Status</th>
                    <th style="min-width: 160px;">Auslastung</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row):
                $b      = $row['budget'];
                $ist    = $row['ist'];
                $diff   = $service->restbudget($b->sollBetrag, $ist);   // positiv = noch Budget übrig
                $pct    = $b->sollBetrag > 0 ? $ist / $b->sollBetrag * 100 : 0;
                $status = $service->status($b->sollBetrag, $ist, $b->gueltigBis);
            ?>
                <tr>
                    <td><?= e($b->name) ?><div class="small text-muted"><?= e($b->bereich) ?></div></td>
                    <td><?= e($row['ownerName']) ?></td>
                    <td class="text-end"><?= e(number_format($b->sollBetrag, 2, ',', '.')) ?> €</td>
                    <td class="text-end"><?= e(number_format($ist, 2, ',', '.')) ?> €</td>
                    <td class="text-end <?= $diff < 0 ? 'text-over' : '' ?>">
                        <?= $diff < 0 ? '<span aria-hidden="true">&#10006;&#xFE0E;</span> ' : '' ?><?= e(number_format($diff, 2, ',', '.')) ?> €
                    </td>
                    <td><?= statusBadge($status) ?></td>
                    <td>
                        <div class="progress" role="progressbar" aria-label="Auslastung" aria-valuenow="<?= e(round($pct)) ?>"
                             aria-valuemin="0" aria-valuemax="100">
                            <!-- Der Balken wird bei 100 % gekappt, die Zahl darunter zeigt den echten Wert -->
                            <div class="progress-bar <?= e($balkenKlasse[$status]) ?>" style="width: <?= e(min(100, round($pct, 1))) ?>%"></div>
                        </div>
                        <div class="small"><?= e(number_format($pct, 1, ',', '.')) ?> %</div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="7">Noch keine Budgets vorhanden.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
