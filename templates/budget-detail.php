<?php
/**
 * Detailansicht eines Budgets – gemeinsam für Owner (MB-041) und Finance
 * (nur lesend). Rechnet bei jedem Aufruf live aus der Datenbank
 * ("Echtzeit", konzept.md Abschnitt 4).
 *
 * Erwartet: $budget (bereits auf Zugriff geprüft!), $ansicht ('owner' | 'finance'),
 * $zurueck = ['href' => ..., 'text' => ...]
 */

$k = kennzahlenFuerBudget($budget);
$buchungen = ladeBuchungen((int) $budget['id']);
$forecasts = ladeForecasts((int) $budget['id']);
$verlauf = verlaufFuerBudget($budget);
$istAbgelaufen = istBudgetAbgelaufen($budget);
$soll = (float) $budget['soll_betrag'];
$puffer = round($soll - $k['hochrechnung'], 2);
$offeneForecasts = array_filter($forecasts, fn ($monat) => $monat >= date('Y-m-01'), ARRAY_FILTER_USE_KEY);
$istOwner = $ansicht === 'owner';

$pageTitle = $budget['name'];
require __DIR__ . '/header.php';
?>

<a href="<?= e($zurueck['href']) ?>" class="zurueck-link">← <?= e($zurueck['text']) ?></a>

<div class="seitenkopf d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="bereich-chip bereich-chip--<?= e(strtolower($budget['bereich'])) ?>"><?= e(bereichLabel($budget['bereich'])) ?></span>
            <?= statusBadge($k['status']) ?>
            <span class="small text-muted">
                <?= e(datumDe($budget['gueltig_von'])) ?> – <?= e(datumDe($budget['gueltig_bis'])) ?> · <?= e(restlaufzeitText($k['tage_bis_verfall'])) ?>
            </span>
        </div>
        <h1><?= e($budget['name']) ?></h1>
        <?php if (!$istOwner): ?>
            <p class="lead">Verantwortlich: <?= e($budget['owner_name'] ?? '') ?></p>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($istOwner && !$istAbgelaufen): ?>
            <a href="/owner/ist-erfassen.php?budget_id=<?= (int) $budget['id'] ?>" class="btn btn-maliá">+ Ausgabe erfassen</a>
            <a href="/owner/forecast.php?budget_id=<?= (int) $budget['id'] ?>" class="btn btn-maliá-outline">Forecast planen</a>
        <?php endif; ?>
        <?php foreach ($zusatzAktionen ?? [] as $aktion): ?>
            <a href="<?= e($aktion['href']) ?>" class="btn btn-maliá-outline"><?= e($aktion['text']) ?></a>
        <?php endforeach; ?>
        <?php if ($buchungen): ?>
            <a href="/ausgaben-export.php?budget_id=<?= (int) $budget['id'] ?>" class="btn btn-maliá-outline"
               title="Alle Ausgaben dieses Budgets als CSV für Excel">CSV</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($istAbgelaufen): ?>
    <div class="alert alert-warning">
        <strong>Abgelaufen am <?= e(datumDe($budget['gueltig_bis'])) ?>.</strong>
        Nach der Verfallszeit sind Buchungen und Korrekturen gesperrt – die Zahlen bleiben zur Auswertung sichtbar.
    </div>
<?php elseif ($k['status'] === 'ueberschritten'): ?>
    <div class="alert alert-danger">
        <strong>Budget überschritten:</strong> Es wurden <?= euro($k['ist'] - $soll) ?> mehr ausgegeben als geplant.
        Bitte mit Finance abstimmen.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Soll</div>
            <div class="kennzahl__wert"><?= euro($soll) ?></div>
            <div class="kennzahl__info">geplantes Budget</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Ist</div>
            <div class="kennzahl__wert"><?= euro($k['ist']) ?></div>
            <div class="kennzahl__info"><?= prozent($k['verbrauch_prozent']) ?> verbraucht · <?= count($buchungen) ?> Ausgaben</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl <?= $k['verfuegbar'] < 0 ? 'kennzahl--negativ' : 'kennzahl--betont' ?>">
            <div class="etikett">Verfügbar</div>
            <div class="kennzahl__wert"><?= euro($k['verfuegbar']) ?></div>
            <div class="kennzahl__info">Soll − Ist</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl <?= $puffer < 0 ? 'kennzahl--ueberschritten' : '' ?>">
            <div class="etikett">Hochrechnung</div>
            <div class="kennzahl__wert"><?= euro($k['hochrechnung']) ?></div>
            <div class="kennzahl__info <?= $puffer < 0 ? 'text-ueberschritten fw-semibold' : '' ?>">
                <?php if ($istAbgelaufen): ?>
                    Budget abgeschlossen
                <?php else: ?>
                    <?= $puffer < 0 ? euro(-$puffer) . ' über Soll' : euro($puffer) . ' Puffer' ?> · Ist + offener Forecast
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 p-md-4 h-100">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h2 class="karte-titel mb-0">Verlauf: Ist, Forecast und Soll</h2>
                <span class="small text-muted">kumuliert je Monat</span>
            </div>
            <div class="diagramm-rahmen diagramm-rahmen--gross">
                <canvas id="diagrammVerlauf" role="img"
                        aria-label="Liniendiagramm: kumulierte Ausgaben, Forecast-Hochrechnung und Soll je Monat"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 p-md-4 h-100">
            <h2 class="karte-titel mb-2">Soll-Ist-Vergleich</h2>
            <div class="diagramm-rahmen diagramm-rahmen--rund">
                <canvas id="diagrammSollIst" role="img"
                        aria-label="Ringdiagramm: <?= e(prozent($k['verbrauch_prozent'])) ?> des Budgets verbraucht"></canvas>
            </div>
            <div class="mt-3">
                <div class="etikett mb-2">Offener Forecast</div>
                <?php if ($offeneForecasts): ?>
                    <ul class="list-unstyled small mb-0 forecast-liste">
                        <?php foreach ($offeneForecasts as $monat => $betrag): ?>
                            <li><span><?= e(monatDe($monat)) ?></span><span class="tabular"><?= euro($betrag) ?></span></li>
                        <?php endforeach; ?>
                        <li class="fw-semibold"><span>Summe</span><span class="tabular"><?= euro($k['offener_forecast']) ?></span></li>
                    </ul>
                <?php else: ?>
                    <p class="small text-muted mb-0">
                        Noch nichts geplant.
                        <?php if ($istOwner && !$istAbgelaufen): ?>
                            <a href="/owner/forecast.php?budget_id=<?= (int) $budget['id'] ?>">Forecast planen</a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="karte-titel mb-0">Alle Ausgaben <span class="text-muted fw-normal">(<?= count($buchungen) ?>)</span></h2>
    <?php if (count($buchungen) > 4): ?>
        <input type="search" class="form-control form-control-sm suche-feld" placeholder="Ausgaben durchsuchen …"
               data-filter-tabelle="#tabelle-ausgaben" aria-label="Ausgaben durchsuchen">
    <?php endif; ?>
</div>

<div class="tabelle-karte">
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="tabelle-ausgaben">
            <thead>
                <tr>
                    <th class="d-none d-sm-table-cell">Datum</th>
                    <th>Beschreibung</th>
                    <th class="d-none d-md-table-cell">Erfasst von</th>
                    <th class="text-end">Betrag</th>
                    <?php if ($istOwner && !$istAbgelaufen): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buchungen as $buchung): ?>
                    <tr>
                        <td class="d-none d-sm-table-cell text-nowrap tabular"><?= e(datumDe($buchung['datum'])) ?></td>
                        <td>
                            <div class="small text-muted d-sm-none tabular"><?= e(datumDe($buchung['datum'])) ?></div>
                            <?= e($buchung['beschreibung']) ?>
                            <?php if ($buchung['urspruenglicher_betrag'] !== null): ?>
                                <div class="korrektur-hinweis">
                                    Korrigiert am <?= e(datumDe($buchung['korrigiert_am'])) ?>:
                                    „<?= e($buchung['korrektur_grund']) ?>“
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted"><?= e($buchung['erfasst_von_name']) ?></td>
                        <td class="text-end text-nowrap tabular">
                            <?php if ($buchung['urspruenglicher_betrag'] !== null): ?>
                                <s class="text-muted small me-1 d-none d-md-inline"><?= euro((float) $buchung['urspruenglicher_betrag']) ?></s>
                            <?php endif; ?>
                            <span class="fw-semibold"><?= euro((float) $buchung['betrag']) ?></span>
                        </td>
                        <?php if ($istOwner && !$istAbgelaufen): ?>
                            <td class="text-end">
                                <a href="/owner/ist-korrigieren.php?id=<?= (int) $buchung['id'] ?>" class="btn btn-sm btn-maliá-outline" aria-label="Ausgabe „<?= e($buchung['beschreibung']) ?>“ korrigieren"><span class="d-none d-sm-inline">Korrigieren</span><span class="d-sm-none" aria-hidden="true">✎</span></a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$buchungen): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Noch keine Ausgaben erfasst.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script type="application/json" id="diagramm-daten"><?= json_encode([
    'soll' => $soll,
    'ist' => $k['ist'],
    'status' => $k['status'],
    'verbrauch' => $k['verbrauch_prozent'],
    'verlauf' => $verlauf,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="/assets/js/diagramme.js"></script>

<?php require __DIR__ . '/footer.php'; ?>
