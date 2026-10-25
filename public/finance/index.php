<?php
/**
 * Finance: Übersicht aller Budgets mit Soll-Ist-Vergleich (MB-032).
 *
 * Kennzahlen über alle laufenden Budgets, Filter nach Status und Bereich,
 * Suche, sortierbare Tabelle und ein Diagramm je Bereich.
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_FINANCE);

$alle = budgetsMitKennzahlen(ladeAlleBudgets());
$summe = zusammenfassung($alle);

// --- Filter (alle per GET, damit man gefilterte Ansichten verlinken kann) ---
$filterStatus = isset(STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$filterBereich = isset(BEREICHE[$_GET['bereich'] ?? '']) ? $_GET['bereich'] : '';
$suche = trim((string) ($_GET['q'] ?? ''));

$gefiltert = filtereBudgets($alle, $filterStatus, $filterBereich, $suche);

/** Link auf die Übersicht mit geänderten Filtern, übrige Filter bleiben. */
function filterLink(array $aenderung): string
{
    $parameter = array_filter(array_merge(
        ['status' => $_GET['status'] ?? '', 'bereich' => $_GET['bereich'] ?? '', 'q' => $_GET['q'] ?? ''],
        $aenderung
    ), fn ($wert) => $wert !== '');

    return '/finance/' . ($parameter ? '?' . http_build_query($parameter) : '');
}

// --- Diagramm: Soll / Ist / Hochrechnung je Bereich (laufende Budgets) ---
$jeBereich = [];
foreach (BEREICHE as $schluessel => $label) {
    $jeBereich[$schluessel] = ['label' => $label, 'soll' => 0.0, 'ist' => 0.0, 'hochrechnung' => 0.0];
}
foreach ($alle as ['budget' => $budget, 'k' => $k]) {
    if ($k['status'] !== 'abgelaufen') {
        $jeBereich[$budget['bereich']]['soll'] += (float) $budget['soll_betrag'];
        $jeBereich[$budget['bereich']]['ist'] += $k['ist'];
        $jeBereich[$budget['bereich']]['hochrechnung'] += $k['hochrechnung'];
    }
}

$handlungsbedarf = $summe['anzahl']['warnung'] + $summe['anzahl']['ueberschritten'];

$pageTitle = 'Alle Budgets';
require __DIR__ . '/../../templates/header.php';
?>

<div class="seitenkopf d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
        <p class="etikett mb-2"><?= e(datumDe(date('Y-m-d'))) ?> · live aus der Datenbank</p>
        <h1>Alle Budgets</h1>
        <p class="lead">Soll-Ist-Stand aller Bereiche – Studio, Café und Marketing.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/finance/export.php<?= e(substr(filterLink([]), strlen('/finance/'))) ?>" class="btn btn-maliá-outline"
           title="Exportiert die aktuell gefilterte Liste">CSV-Export</a>
        <a href="/finance/neu.php" class="btn btn-maliá">+ Neues Budget</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Budget gesamt</div>
            <div class="kennzahl__wert"><?= euro($summe['soll']) ?></div>
            <div class="kennzahl__info"><?= $summe['laufend'] ?> laufende Budgets</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Ausgegeben</div>
            <div class="kennzahl__wert"><?= euro($summe['ist']) ?></div>
            <div class="kennzahl__info"><?= prozent($summe['verbrauch_prozent']) ?> vom Budget</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl <?= $summe['verfuegbar'] < 0 ? 'kennzahl--negativ' : 'kennzahl--betont' ?>">
            <div class="etikett">Noch verfügbar</div>
            <div class="kennzahl__wert"><?= euro($summe['verfuegbar']) ?></div>
            <div class="kennzahl__info">Hochrechnung <?= euro($summe['hochrechnung']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= e(filterLink(['status' => $summe['anzahl']['ueberschritten'] ? 'ueberschritten' : 'warnung'])) ?>" class="text-decoration-none">
            <div class="kennzahl card--klickbar <?= $handlungsbedarf ? 'kennzahl--ueberschritten' : '' ?>">
                <div class="etikett">Handlungsbedarf</div>
                <div class="kennzahl__wert"><?= $handlungsbedarf ?> <span class="fs-6 text-muted">Budgets</span></div>
                <div class="kennzahl__info">
                    <?= $summe['anzahl']['ueberschritten'] ?> überschritten · <?= $summe['anzahl']['warnung'] ?> mit Warnung
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <h2 class="karte-titel mb-0">Soll, Ist und Hochrechnung je Bereich</h2>
        <span class="small text-muted">laufende Budgets</span>
    </div>
    <div class="diagramm-rahmen" style="height: 260px;">
        <canvas id="diagrammBereiche" role="img" aria-label="Balkendiagramm: Soll, Ist und Hochrechnung je Bereich"></canvas>
    </div>
</div>

<div class="filterleiste mb-3">
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(filterLink(['status' => ''])) ?>" class="filter-chip <?= $filterStatus === '' ? 'aktiv' : '' ?>">
            Alle <span class="filter-chip__zahl"><?= count($alle) ?></span>
        </a>
        <?php foreach (STATUS_LABEL as $status => $label): ?>
            <a href="<?= e(filterLink(['status' => $status])) ?>" class="filter-chip filter-chip--<?= e(str_replace('_', '-', $status)) ?> <?= $filterStatus === $status ? 'aktiv' : '' ?>">
                <?= e($label) ?> <span class="filter-chip__zahl"><?= $summe['anzahl'][$status] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="/finance/" class="d-flex flex-wrap flex-sm-nowrap gap-2">
        <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
        <select class="form-select form-select-sm" name="bereich" onchange="this.form.submit()" aria-label="Bereich" style="min-width: 170px; width: auto;">
            <option value="">Alle Bereiche</option>
            <?php foreach (BEREICHE as $schluessel => $label): ?>
                <option value="<?= e($schluessel) ?>" <?= $filterBereich === $schluessel ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" class="form-control form-control-sm suche-feld" name="q" value="<?= e($suche) ?>"
               placeholder="Budget oder Owner suchen …" aria-label="Budget oder Owner suchen">
    </form>
</div>

<div class="tabelle-karte">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sortierbar">
            <thead>
                <tr>
                    <th data-sortieren="text" class="spalte-name">Budget</th>
                    <th data-sortieren="text" class="d-none d-xxl-table-cell">Owner</th>
                    <th data-sortieren="zahl" class="text-end d-none d-md-table-cell">Soll</th>
                    <th data-sortieren="zahl" class="text-end d-none d-sm-table-cell">Ist</th>
                    <th data-sortieren="zahl" style="min-width: 140px;">Verbrauch</th>
                    <th data-sortieren="zahl" class="text-end d-none d-lg-table-cell">Hochrechnung</th>
                    <th data-sortieren="text" class="d-none d-sm-table-cell">Status</th>
                    <th data-sortieren="zahl" class="d-none d-lg-table-cell">Läuft bis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gefiltert as ['budget' => $budget, 'k' => $k]): ?>
                    <?php $link = '/finance/budget.php?id=' . (int) $budget['id']; ?>
                    <tr class="zeile-klickbar" data-link="<?= e($link) ?>">
                        <td data-wert="<?= e($budget['name']) ?>">
                            <a href="<?= e($link) ?>" class="fw-semibold text-reset"><?= e($budget['name']) ?></a>
                            <div class="small text-muted"><?= e(bereichLabel($budget['bereich'])) ?><span class="d-xxl-none"> · <?= e($budget['owner_name']) ?></span></div>
                            <div class="d-sm-none mt-1"><?= statusBadge($k['status']) ?></div>
                        </td>
                        <td class="d-none d-xxl-table-cell" data-wert="<?= e($budget['owner_name']) ?>"><?= e($budget['owner_name']) ?></td>
                        <td class="text-end text-nowrap tabular d-none d-md-table-cell" data-wert="<?= (float) $budget['soll_betrag'] ?>"><?= euro((float) $budget['soll_betrag']) ?></td>
                        <td class="text-end text-nowrap tabular d-none d-sm-table-cell" data-wert="<?= $k['ist'] ?>"><?= euro($k['ist']) ?></td>
                        <td data-wert="<?= $k['verbrauch_prozent'] ?>">
                            <?= verbrauchsBalken($k['verbrauch_prozent']) ?>
                            <span class="small tabular"><?= prozent($k['verbrauch_prozent']) ?></span>
                        </td>
                        <td class="text-end text-nowrap tabular d-none d-lg-table-cell <?= $k['hochrechnung'] > (float) $budget['soll_betrag'] && $k['status'] !== 'abgelaufen' ? 'text-ueberschritten fw-semibold' : '' ?>"
                            data-wert="<?= $k['hochrechnung'] ?>"><?= euro($k['hochrechnung']) ?></td>
                        <td class="d-none d-sm-table-cell" data-wert="<?= e(statusLabel($k['status'])) ?>"><?= statusBadge($k['status']) ?></td>
                        <td class="small text-nowrap d-none d-lg-table-cell" data-wert="<?= e($budget['gueltig_bis']) ?>">
                            <?= e(datumDe($budget['gueltig_bis'])) ?>
                            <div class="text-muted"><?= e(restlaufzeitText($k['tage_bis_verfall'])) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$gefiltert): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        Keine Budgets für diese Auswahl. <a href="/finance/">Filter zurücksetzen</a>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script type="application/json" id="bereich-daten"><?= json_encode(array_values($jeBereich), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="/assets/js/diagramm-bereiche.js"></script>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
