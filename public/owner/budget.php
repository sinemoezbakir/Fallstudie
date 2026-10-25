<?php
declare(strict_types=1);

use Malia\Auth;
use Malia\Models\Forecast;
use Malia\Models\IstWert;
use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Repositories\ForecastRepository;
use Malia\Repositories\IstWertRepository;
use Malia\Services\BudgetService;

require __DIR__ . '/../bootstrap.php';

// Nur OWNER
$user = $auth->requireRole(User::ROLE_OWNER);   // $user wird vom Layout gebraucht

// ----- Zugriffsprüfung: das Budget muss dem angemeldeten Owner gehören -----
// findByIdAndOwner filtert in der SQL-Abfrage nach owner_id. Ein fremdes und ein
// nicht existierendes Budget verhalten sich gleich (403), so verrät die Seite nicht,
// welche IDs es gibt.
$budgetId = (int) ($_GET['id'] ?? 0);
$budget   = (new BudgetRepository($pdo))->findByIdAndOwner($budgetId, $user->id);
if ($budget === null) {
    fehlerseite(403, 'Auf dieses Budget haben Sie keinen Zugriff.');
}

$istRepo      = new IstWertRepository($pdo);
$forecastRepo = new ForecastRepository($pdo);
$service      = new BudgetService();

/** Prüft ein Datum im Format YYYY-MM-DD, gibt es zurück oder null. */
function parseDatum(string $raw): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return ($d && $d->format('Y-m-d') === $raw) ? $raw : null;
}

$errors = [];
$self   = 'budget.php?id=' . $budget->id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Ungültige Anfrage. Bitte erneut versuchen.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        // ----- Ausgabe erfassen -----
        $betrag       = parseBetrag((string) ($_POST['betrag'] ?? ''));
        $beschreibung = trim((string) ($_POST['beschreibung'] ?? ''));
        $datum        = parseDatum((string) ($_POST['datum'] ?? ''));

        if ($betrag === null) {
            $errors[] = 'Betrag muss eine Zahl größer als 0 sein, ohne Tausenderpunkt (z. B. 49,90).';
        }
        if ($beschreibung === '' || mb_strlen($beschreibung) > 255) {
            $errors[] = 'Beschreibung: Pflichtfeld, höchstens 255 Zeichen.';
        }
        if ($datum === null) {
            $errors[] = 'Bitte ein gültiges Datum angeben.';
        } elseif ($datum < $budget->gueltigVon || $datum > $budget->gueltigBis) {
            $errors[] = 'Das Datum muss innerhalb der Gültigkeit des Budgets liegen ('
                . $budget->gueltigVon . ' bis ' . $budget->gueltigBis . ').';
        }

        if (!$errors) {
            // budget_id und erfasst_von kommen vom Server, nie aus dem Formular
            $istRepo->create(new IstWert(0, $budget->id, $betrag, $beschreibung, $datum, $user->id));
            $_SESSION['flash_success'] = 'Ausgabe wurde erfasst.';
            Auth::redirect($self);   // Post/Redirect/Get
        }
    } elseif (($_POST['action'] ?? '') === 'create_forecast') {
        // ----- Forecast erfassen (gleiche Regeln wie bei Ausgaben) -----
        $betrag       = parseBetrag((string) ($_POST['fc_betrag'] ?? ''));
        $beschreibung = trim((string) ($_POST['fc_beschreibung'] ?? ''));
        $datum        = parseDatum((string) ($_POST['fc_datum'] ?? ''));

        if ($betrag === null) {
            $errors[] = 'Forecast-Betrag muss eine Zahl größer als 0 sein, ohne Tausenderpunkt.';
        }
        if ($beschreibung === '' || mb_strlen($beschreibung) > 255) {
            $errors[] = 'Forecast-Beschreibung: Pflichtfeld, höchstens 255 Zeichen.';
        }
        if ($datum === null) {
            $errors[] = 'Bitte ein gültiges Forecast-Datum angeben.';
        } elseif ($datum < $budget->gueltigVon || $datum > $budget->gueltigBis) {
            $errors[] = 'Das Forecast-Datum muss innerhalb der Gültigkeit des Budgets liegen ('
                . $budget->gueltigVon . ' bis ' . $budget->gueltigBis . ').';
        }

        if (!$errors) {
            $forecastRepo->create(new Forecast(0, $budget->id, $betrag, $beschreibung, $datum, $user->id));
            $_SESSION['flash_success'] = 'Forecast wurde erfasst.';
            Auth::redirect($self);
        }
    } elseif (($_POST['action'] ?? '') === 'correct') {
        // ----- Ausgabe korrigieren -----
        $istId = (int) ($_POST['ist_wert_id'] ?? 0);
        $neu   = parseBetrag((string) ($_POST['neuer_betrag'] ?? ''));
        $grund = trim((string) ($_POST['grund'] ?? ''));

        // Der Ist-Wert muss zu DIESEM Budget und DIESEM Owner gehören
        $ist = $istRepo->findByIdAndOwner($istId, $user->id);

        if ($ist === null || $ist->budgetId !== $budget->id) {
            $errors[] = 'Ausgabe nicht gefunden.';
        } else {
            if ($neu === null) {
                $errors[] = 'Neuer Betrag muss eine Zahl größer als 0 sein, ohne Tausenderpunkt.';
            } elseif (abs($neu - $ist->betrag) < 0.005) {
                $errors[] = 'Der neue Betrag entspricht dem bisherigen Betrag.';
            }
            if ($grund === '' || mb_strlen($grund) > 255) {
                $errors[] = 'Der Grund ist ein Pflichtfeld (höchstens 255 Zeichen).';
            }
        }

        if (!$errors) {
            if ($istRepo->correct($ist->id, $user->id, $neu, $grund, $user->id)) {
                $_SESSION['flash_success'] = 'Ausgabe wurde korrigiert. Der alte Betrag ist gespeichert.';
                Auth::redirect($self);
            }
            $errors[] = 'Ausgabe nicht gefunden.';
        }
    }
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// ----- Daten für die Anzeige (immer nur eigene, über owner_id gefiltert) -----
$istWerte = $istRepo->findByBudgetAndOwner($budget->id, $user->id);
$forecasts = $forecastRepo->findByBudgetAndOwner($budget->id, $user->id);
$forecast  = $service->summeForecast($forecasts);
$ist      = $service->summeIst($istWerte);
$rest     = $service->restbudget($budget->sollBetrag, $ist);
$prozent  = $service->auslastungProzent($budget->sollBetrag, $ist);
$status   = $service->status($budget->sollBetrag, $ist, $budget->gueltigBis);
// Prognose und Vergleich mit dem Soll
$prognose        = $service->prognose($ist, $forecast);
$prognoseProzent = $service->auslastungProzent($budget->sollBetrag, $prognose);
$prognoseDiff    = $service->restbudget($budget->sollBetrag, $prognose);   // negativ = Überschreitung erwartet

// Balken: Anteile von Ist und Forecast am Soll (auf zusammen 100 % gekappt)
$istBalken = $budget->sollBetrag > 0 ? min(100, $ist / $budget->sollBetrag * 100) : 0;
$fcBalken  = $budget->sollBetrag > 0 ? min(100 - $istBalken, $forecast / $budget->sollBetrag * 100) : 0;

// ----- Daten für die Diagramme (als JSON an Chart.js) -----
// Farbe der Ist-Säule = Status des Budgets, Farbe der Prognose-Säule = Status bei Prognose
$prognoseStatus = $service->status($budget->sollBetrag, $prognose, $budget->gueltigBis);
$chartData = [
    'name'      => $budget->name,
    'soll'      => $budget->sollBetrag,
    'ist'       => $ist,
    'prognose'  => $prognose,
    'farben'    => [
        'soll'     => '--malia-chart-soll',
        'ist'      => '--malia-chart-ist',
        'prognose' => '--malia-chart-forecast',
    ],
    // Statusbeschriftung mit Symbol, damit der Status auch im Diagramm nicht nur an der Farbe hängt
    'labels'    => [
        'ist'      => statusLabel($status),
        'prognose' => statusLabel($prognoseStatus),
    ],
];
// Textalternative für Screenreader (Diagramme sind sonst nur Grafik)
$chartBeschreibung = 'Soll ' . euro($budget->sollBetrag) . ', Ist ' . euro($ist) . ' (' . statusLabel($status)
    . '), Ist plus Forecast ' . euro($prognose) . ' (' . statusLabel($prognoseStatus) . ')';
// JSON_HEX_*: Sonderzeichen werden maskiert, so kann nichts aus dem JSON das HTML verlassen
$chartJson = json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$pageTitle = $budget->name;
require __DIR__ . '/../../src/layout/header.php';
?>
<p class="mb-1"><a href="index.php">&larr; Meine Budgets</a></p>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
<h1 class="h3 mb-0">
    <?= e($budget->name) ?>
    <?= statusBadge($status) ?>
</h1>
<a class="btn btn-outline-secondary btn-sm" href="export.php?id=<?= (int) $budget->id ?>">Als CSV exportieren</a>
</div>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<!-- Budgetdetails -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">Bereich</div><?= e($budget->bereich) ?></div>
            <div class="col-md-4"><div class="text-muted small">Gültigkeit</div>
                <?= e($budget->gueltigVon) ?> bis <?= e($budget->gueltigBis) ?></div>
            <div class="col-md-4"><div class="text-muted small">Soll</div><?= e(euro($budget->sollBetrag)) ?></div>
            <div class="col-md-4"><div class="text-muted small">Ist (Ausgaben)</div><?= e(euro($ist)) ?></div>
            <div class="col-md-4"><div class="text-muted small">Restbudget</div>
                <span class="<?= $rest < 0 ? 'text-over' : '' ?>"><?= $rest < 0 ? '<span aria-hidden="true">&#10006;&#xFE0E;</span> ' : '' ?><?= e(euro($rest)) ?></span></div>
            <div class="col-md-4"><div class="text-muted small">Auslastung</div>
                <?= $prozent === null ? 'nicht berechenbar (Soll = 0)' : e(number_format($prozent, 1, ',', '.')) . ' %' ?></div>
            <div class="col-md-4"><div class="text-muted small">Forecast</div><?= e(euro($forecast)) ?></div>
            <div class="col-md-4"><div class="text-muted small">Prognose (Ist + Forecast)</div>
                <?= e(euro($prognose)) ?>
                (<?= $prognoseProzent === null ? '–' : e(number_format($prognoseProzent, 1, ',', '.')) . ' %' ?> vom Soll)</div>
            <div class="col-md-4"><div class="text-muted small">Prognose gegenüber Soll</div>
                <?php if ($prognoseDiff < 0): ?>
                    <span class="text-over"><span aria-hidden="true">&#10006;&#xFE0E;</span> Überschreitung um <?= e(euro(-$prognoseDiff)) ?> erwartet</span>
                <?php else: ?>
                    <span class="text-ok"><span aria-hidden="true">&#10004;&#xFE0E;</span> Voraussichtlich <?= e(euro($prognoseDiff)) ?> übrig</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Balken: Ist (dunkel) und Forecast (hell) im Verhältnis zum Soll (= volle Breite) -->
        <div class="mt-3">
            <div class="progress" style="height: 1.5rem;" role="progressbar" aria-label="Ist und Forecast im Verhältnis zum Soll">
                <div class="progress-bar balken-ist" style="width: <?= e(round($istBalken, 1)) ?>%">Ist</div>
                <div class="progress-bar balken-forecast" style="width: <?= e(round($fcBalken, 1)) ?>%">Forecast</div>
            </div>
            <div class="small text-muted mt-1">Gesamtbreite = Soll (<?= e(euro($budget->sollBetrag)) ?>)</div>
        </div>
    </div>
</div>

<!-- Diagramme (Chart.js) -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Soll-Ist-Vergleich</h2>
                <canvas id="chartSollIst" aria-label="<?= e('Balkendiagramm Soll gegen Ist. ' . $chartBeschreibung) ?>" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Forecast-Ist-Soll-Vergleich</h2>
                <canvas id="chartForecast" aria-label="<?= e('Balkendiagramm Soll, Ist und Ist plus Forecast. ' . $chartBeschreibung) ?>" role="img"></canvas>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Daten kommen vom Server (BudgetService), das Skript rechnet nichts selbst.
const daten = <?= $chartJson ?>;

// Farben aus den CSS-Variablen in style.css lesen
const css  = getComputedStyle(document.documentElement);
const farbe = (variable) => css.getPropertyValue(variable).trim();

const euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

Chart.defaults.color = farbe('--malia-text');
Chart.defaults.font.size = 13;

// Plugin: schreibt über jeden Balken den Euro-Wert und (falls vorhanden) das Statuslabel
const datenbeschriftung = {
    id: 'datenbeschriftung',
    afterDatasetsDraw(chart) {
        const ctx = chart.ctx;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.fillStyle = farbe('--malia-text');
        chart.data.datasets.forEach((ds, i) => {
            chart.getDatasetMeta(i).data.forEach((balken, j) => {
                if (ds.data[j] === null) return;
                ctx.font = 'bold 13px sans-serif';
                ctx.fillText(euro.format(ds.data[j]), balken.x, balken.y - 6);
                if (ds.statusLabel) {
                    ctx.font = '12px sans-serif';
                    ctx.fillText(ds.statusLabel, balken.x, balken.y - 22);
                }
            });
        });
        ctx.restore();
    }
};

// Jeder Balken ist ein eigenes Dataset, so zeigt die Legende jede Reihe mit ihrer Farbe.
// "stacked" sorgt dafür, dass die Balken trotzdem mittig auf ihrer Position stehen.
function reihe(name, index, anzahl, wert, farbVariable, statusLabel) {
    const werte = new Array(anzahl).fill(null);
    werte[index] = wert;
    return {
        label: name,
        data: werte,
        backgroundColor: farbe(farbVariable),
        borderColor: farbe('--malia-text'),   // dunkler Rahmen: Balken heben sich vom Hintergrund ab
        borderWidth: 2,
        statusLabel: statusLabel
    };
}

// Gemeinsame Einstellungen: Legende unten, Achse in Euro beschriftet, Tooltip mit Euro-Format
function optionen() {
    return {
        responsive: true,
        layout: { padding: { top: 40 } },
        plugins: {
            legend: { display: true, position: 'bottom', labels: { boxWidth: 24, boxHeight: 16 } },
            tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + euro.format(ctx.parsed.y) } }
        },
        scales: {
            y: {
                stacked: true,
                beginAtZero: true,
                grace: '10%',
                title: { display: true, text: 'Betrag in Euro (€)' },
                ticks: { callback: (wert) => euro.format(wert) }
            },
            x: { stacked: true, title: { display: true, text: 'Budget: ' + daten.name } }
        }
    };
}

// Diagramm 1: Soll gegen Ist (Soll = hellblau, Ist = dunkelblau)
new Chart(document.getElementById('chartSollIst'), {
    type: 'bar',
    data: {
        labels: ['Soll', 'Ist'],
        datasets: [
            reihe('Soll', 0, 2, daten.soll, daten.farben.soll, ''),
            reihe('Ist', 1, 2, daten.ist, daten.farben.ist, daten.labels.ist)
        ]
    },
    options: optionen(),
    plugins: [datenbeschriftung]
});

// Diagramm 2: Soll, Ist und Prognose (Ist + Forecast = mittelblau)
new Chart(document.getElementById('chartForecast'), {
    type: 'bar',
    data: {
        labels: ['Soll', 'Ist', 'Ist + Forecast'],
        datasets: [
            reihe('Soll', 0, 3, daten.soll, daten.farben.soll, ''),
            reihe('Ist', 1, 3, daten.ist, daten.farben.ist, daten.labels.ist),
            reihe('Ist + Forecast', 2, 3, daten.prognose, daten.farben.prognose, daten.labels.prognose)
        ]
    },
    options: optionen(),
    plugins: [datenbeschriftung]
});
</script>

<!-- KI-Einschätzung: nur auf Knopfdruck, es gehen nur Zahlen und Daten an die KI (keine Texte/Namen) -->
<?php $kiAktiv = !empty($config['ki']['aktiv']); ?>
<div class="card mb-4 ki-karte">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">KI-Einschätzung</h2>
                <p class="small text-muted mb-0">
                    Eine KI schätzt anhand der Ausgaben und Forecasts, ob das Budget reicht.
                    Gesendet werden nur Beträge und Daten, keine Beschreibungen oder Namen.
                </p>
            </div>
            <button type="button" class="btn btn-primary" id="kiButton" <?= $kiAktiv ? '' : 'disabled' ?>>
                KI-Einschätzung holen
            </button>
        </div>
        <?php if (!$kiAktiv): ?>
            <p class="small mt-2 mb-0">Nicht eingerichtet: In <code>config/config.php</code> den Abschnitt <code>ki</code> aktivieren und einen API-Key eintragen.</p>
        <?php endif; ?>
        <div id="kiAusgabe" class="mt-3" role="status" aria-live="polite"></div>
    </div>
</div>
<script>
(function () {
    const knopf   = document.getElementById('kiButton');
    const ausgabe = document.getElementById('kiAusgabe');
    const token   = <?= json_encode(Auth::csrfToken()) ?>;
    const id      = <?= (int) $budget->id ?>;

    knopf.addEventListener('click', async () => {
        knopf.disabled = true;
        ausgabe.textContent = 'Die KI rechnet …';
        ausgabe.className = 'mt-3';
        try {
            const antwort = await fetch('ki_prognose.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: token, id: id })
            });
            const daten = await antwort.json();
            if (antwort.ok) {
                // textContent statt innerHTML: Der KI-Text kann so kein HTML einschleusen
                ausgabe.textContent = daten.text;
                ausgabe.className = 'mt-3 ki-text';
            } else {
                ausgabe.textContent = '✖ ' + (daten.fehler || 'Fehler bei der KI-Anfrage.');
                ausgabe.className = 'mt-3 text-over';
            }
        } catch (e) {
            ausgabe.textContent = '✖ Keine Verbindung zum Server.';
            ausgabe.className = 'mt-3 text-over';
        }
        knopf.disabled = false;
    });
})();
</script>

<div class="row g-4">
    <!-- Ist-Werte -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <h2 class="h5">Ausgaben (Ist-Werte)</h2>
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Datum</th><th>Beschreibung</th><th class="text-end">Betrag</th><th>Korrigieren</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($istWerte as $w): ?>
                        <tr>
                            <td><?= e($w->datum) ?></td>
                            <td><?= e($w->beschreibung) ?></td>
                            <td class="text-end"><?= e(euro($w->betrag)) ?></td>
                            <td>
                                <form method="post" class="row g-1">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="action" value="correct">
                                    <input type="hidden" name="ist_wert_id" value="<?= e($w->id) ?>">
                                    <div class="col-5">
                                        <input type="text" inputmode="decimal" name="neuer_betrag" required
                                               class="form-control form-control-sm" placeholder="Neuer Betrag"
                                               aria-label="Neuer Betrag">
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="grund" required maxlength="255"
                                               class="form-control form-control-sm" placeholder="Grund"
                                               aria-label="Grund der Korrektur">
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">OK</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$istWerte): ?>
                        <tr><td colspan="4">Noch keine Ausgaben erfasst.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ausgabe erfassen und Forecast-Bereich -->
    <div class="col-lg-4">
        <form method="post" class="card card-body shadow-sm mb-4">
            <h2 class="h5">Ausgabe erfassen</h2>
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label for="betrag" class="form-label">Betrag (€)</label>
                <input type="text" inputmode="decimal" class="form-control" id="betrag" name="betrag" required
                       value="<?= e($_POST['betrag'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="beschreibung" class="form-label">Beschreibung</label>
                <input type="text" class="form-control" id="beschreibung" name="beschreibung" required maxlength="255"
                       value="<?= e($_POST['beschreibung'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="datum" class="form-label">Datum</label>
                <input type="date" class="form-control" id="datum" name="datum" required
                       min="<?= e($budget->gueltigVon) ?>" max="<?= e($budget->gueltigBis) ?>"
                       value="<?= e($_POST['datum'] ?? '') ?>">
                <div class="form-text">Nur zwischen <?= e($budget->gueltigVon) ?> und <?= e($budget->gueltigBis) ?>.</div>
            </div>
            <button type="submit" class="btn btn-primary">Erfassen</button>
        </form>

        <!-- Forecast erfassen -->
        <form method="post" class="card card-body shadow-sm">
            <h2 class="h5">Forecast erfassen</h2>
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="create_forecast">
            <div class="mb-3">
                <label for="fc_betrag" class="form-label">Erwarteter Betrag (€)</label>
                <input type="text" inputmode="decimal" class="form-control" id="fc_betrag" name="fc_betrag" required
                       value="<?= e($_POST['fc_betrag'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="fc_beschreibung" class="form-label">Beschreibung</label>
                <input type="text" class="form-control" id="fc_beschreibung" name="fc_beschreibung" required maxlength="255"
                       value="<?= e($_POST['fc_beschreibung'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="fc_datum" class="form-label">Voraussichtliches Datum</label>
                <input type="date" class="form-control" id="fc_datum" name="fc_datum" required
                       min="<?= e($budget->gueltigVon) ?>" max="<?= e($budget->gueltigBis) ?>"
                       value="<?= e($_POST['fc_datum'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Forecast speichern</button>
        </form>
    </div>
</div>

<!-- Liste der Forecasts -->
<div class="card shadow-sm mt-4">
    <div class="card-body table-responsive">
        <h2 class="h5">Forecasts (erwartete Ausgaben)</h2>
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Datum</th><th>Beschreibung</th><th class="text-end">Betrag</th></tr>
            </thead>
            <tbody>
            <?php foreach ($forecasts as $f): ?>
                <tr>
                    <td><?= e($f->datum) ?></td>
                    <td><?= e($f->beschreibung) ?></td>
                    <td class="text-end"><?= e(euro($f->betrag)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$forecasts): ?>
                <tr><td colspan="3">Noch keine Forecasts erfasst.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
