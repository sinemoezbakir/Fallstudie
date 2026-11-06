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

// Seitenaufbau nach "Antwort -> Ursache -> Details -> Analyse":
// Banner (wie ernst?), Handlungsbedarf (welches Budget zuerst, warum?),
// Bereichsuebersicht (welche Bereiche, was laeuft gut?), Kennzahlen, Filter + Tabelle.
// Jede Ebene beantwortet eine eigene Frage, nichts wird doppelt genannt.

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
// "Schlechtester Status gewinnt" (Enterprise-Standard, z. B. Power BI/SAP
// Analytics Cloud): die Status-Ampel eines Bereichs kommt NICHT aus der
// Soll-Ist-Summe, sondern aus dem schlechtesten Einzelstatus seiner Budgets.
// So kann ein einzelnes ueberschrittenes Budget nicht in der Summe
// "untergehen", selbst wenn andere Budgets im selben Bereich gut dastehen.
// Risiko je Bereich = Summe der erwarteten Ueberschreitungen seiner Budgets,
// NICHT mit dem Puffer anderer Budgets im selben Bereich verrechnet.
$jeBereich = [];
$schlechtesterStatusJeBereich = [];
foreach (BEREICHE as $schluessel => $label) {
    $jeBereich[$schluessel] = ['label' => $label, 'soll' => 0.0, 'ist' => 0.0, 'hochrechnung' => 0.0, 'risiko' => 0.0, 'risikoBudgets' => 0];
    $schlechtesterStatusJeBereich[$schluessel] = null;
}
foreach ($alle as $eintrag) {
    ['budget' => $budget, 'k' => $k] = $eintrag;
    if ($k['status'] !== 'abgelaufen') {
        $jeBereich[$budget['bereich']]['soll'] += (float) $budget['soll_betrag'];
        $jeBereich[$budget['bereich']]['ist'] += $k['ist'];
        $jeBereich[$budget['bereich']]['hochrechnung'] += $k['hochrechnung'];
        $ueber = erwarteteUeberschreitung($budget, $k);
        if ($ueber > 0) {
            $jeBereich[$budget['bereich']]['risiko'] += $ueber;
            $jeBereich[$budget['bereich']]['risikoBudgets']++;
        }

        $bisher = $schlechtesterStatusJeBereich[$budget['bereich']];
        if ($bisher === null || STATUS_RANG[$k['status']] < STATUS_RANG[$bisher]) {
            $schlechtesterStatusJeBereich[$budget['bereich']] = $k['status'];
        }
    }
}

// Top-Risiken: Bereiche nach Dringlichkeit sortiert (nicht alphabetisch),
// "schlechtester Status gewinnt" wie beim Banner und den Badges.
$topRisiken = array_keys(BEREICHE);
usort($topRisiken, fn ($a, $b) => STATUS_RANG[$schlechtesterStatusJeBereich[$a] ?? 'im_rahmen']
    <=> STATUS_RANG[$schlechtesterStatusJeBereich[$b] ?? 'im_rahmen']);

// Handlungsbedarf je Budget: die Bereichsuebersicht zeigt nur Bereichssummen -
// hier stehen alle ueberschrittenen und gewarnten Budgets einzeln,
// Ueberschritten vor Warnung. Bewusst aus $alle statt $gefiltert,
// damit die Entscheidungsliste nicht von den Tabellenfiltern abhaengt.
$handlungsbedarfListe = array_values(array_filter(
    $alle,
    fn ($eintrag) => in_array($eintrag['k']['status'], ['ueberschritten', 'warnung'], true)
));
// Fruehwarnsystem: entscheidend ist, wo ein Budget am Laufzeitende landet
// (Hochrechnung), nicht nur was schon ausgegeben ist. Innerhalb desselben
// Status steht die groesste erwartete Ueberschreitung zuerst.
$ueberSoll = fn (array $eintrag): float => erwarteteUeberschreitung($eintrag['budget'], $eintrag['k']);
usort($handlungsbedarfListe, fn ($a, $b) => [STATUS_RANG[$a['k']['status']], $ueberSoll($b)]
    <=> [STATUS_RANG[$b['k']['status']], $ueberSoll($a)]);

// Gesamtrisiko fuers Banner: Summe der erwarteten Ueberschreitungen je Budget,
// bewusst nicht mit dem Puffer anderer Budgets verrechnet
$prognoseUeber = array_filter(array_map($ueberSoll, $handlungsbedarfListe), fn ($betrag) => $betrag > 0);

$handlungsbedarf = $summe['anzahl']['warnung'] + $summe['anzahl']['ueberschritten'];
$istRisiko = $handlungsbedarf > 0;
$anzahlJeBereich = array_count_values(array_column(array_column($alle, 'budget'), 'bereich'));

$pageTitle = 'Alle Budgets';
require __DIR__ . '/../../templates/header.php';
?>

<div class="seitenkopf d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
        <p class="etikett mb-2">Stand: <?= date('d.m.Y, H:i') ?> Uhr</p>
        <h1>Alle Budgets</h1>
        <p class="lead">Soll-Ist-Stand aller Bereiche – Kurse, Geräte und Marketing.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/finance/export.php<?= e(substr(filterLink([]), strlen('/finance/'))) ?>" class="btn btn-maliá-outline"
           title="Exportiert die aktuell gefilterte Liste">CSV-Export</a>
        <a href="/finance/neu.php" class="btn btn-maliá">+ Neues Budget</a>
    </div>
</div>

<div class="status-banner <?= $istRisiko ? 'status-banner--risiko' : 'status-banner--gut' ?> mb-4">
    <span class="status-banner__icon" aria-hidden="true"><?= $istRisiko ? '!' : '✓' ?></span>
    <div>
        <p class="status-banner__titel"><?= $istRisiko ? 'Risiko erkannt' : 'Keine aktiven Risiken' ?></p>
        <p class="status-banner__text">
            <?php // Nur das Urteil (wie gross?) - welche Budgets und warum steht direkt darunter ?>
            <?php if ($istRisiko): ?>
                <?php $ueberschritten = $summe['anzahl']['ueberschritten']; $warnung = $summe['anzahl']['warnung']; ?>
                <?php if ($prognoseUeber): ?>
                    <strong>Gesamtrisiko: voraussichtlich <?= euro(array_sum($prognoseUeber)) ?> über Budget</strong> bis Laufzeitende,
                    verteilt auf <?= count($prognoseUeber) === 1 ? '1 Budget' : count($prognoseUeber) . ' Budgets' ?>.
                <?php else: ?>
                    Kein Budget überschreitet voraussichtlich sein Soll.
                <?php endif; ?>
                Aktuell <?= $ueberschritten ? $ueberschritten . ' überschritten' : '' ?><?= $ueberschritten && $warnung ? ', ' : '' ?><?= $warnung ? $warnung . ' mit Warnung' : '' ?>.
            <?php else: ?>
                Alle <?= $summe['laufend'] ?> laufenden Budgets sind im grünen Bereich.
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if ($handlungsbedarfListe): ?>
<section class="aufmerksamkeit mb-4" aria-labelledby="handlungsbedarf-titel">
    <h2 id="handlungsbedarf-titel" class="karte-titel mb-2">Handlungsbedarf <span class="small text-muted fw-normal">– in dieser Reihenfolge prüfen</span></h2>
    <ul class="list-unstyled mb-0">
        <?php foreach ($handlungsbedarfListe as $eintrag): ?>
            <?php ['budget' => $budget, 'k' => $k] = $eintrag; ?>
            <li>
                <a class="aufmerksamkeit__zeile" href="/finance/budget.php?id=<?= (int) $budget['id'] ?>">
                    <span class="aufmerksamkeit__punkt aufmerksamkeit__punkt--<?= e($k['status']) ?>"></span>
                    <span class="fw-semibold"><?= e($budget['name']) ?></span>
                    <span class="small text-muted"><?= e(bereichLabel($budget['bereich'])) ?></span>
                    <span class="fw-semibold <?= $k['status'] === 'ueberschritten' ? 'text-ueberschritten' : 'text-warnung' ?>"><?= e(statusGrund($budget, $k)) ?></span>
                    <span class="ms-auto aufmerksamkeit__pfeil" aria-hidden="true">→</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<div class="card p-3 p-md-4 mb-4">
    <h2 class="karte-titel mb-3">
        Bereichsübersicht
        <button type="button" class="info-knopf" data-bs-toggle="popover" data-bs-trigger="focus"
                data-bs-title="Was ist die Hochrechnung?"
                data-bs-content="Ist-Ausgaben + bereits eingetragener Forecast für die restliche Laufzeit. Zeigt, wo ein Bereich am Ende der Laufzeit stehen wird, wenn alles wie geplant eintrifft – auch wenn das Ist bisher noch niedrig ist."
                aria-label="Was bedeutet Hochrechnung?">i</button>
    </h2>
    <div class="top-risiken">
        <?php foreach ($topRisiken as $schluessel): ?>
            <?php
                $daten = $jeBereich[$schluessel];
                $status = $schlechtesterStatusJeBereich[$schluessel] ?? 'im_rahmen';
                $ruhig = $status === 'im_rahmen';
                $puffer = $daten['soll'] - $daten['hochrechnung'];
                $fuellung = $daten['soll'] > 0 ? round(min(100, $daten['hochrechnung'] / $daten['soll'] * 100), 1) : 0;
                $balkenKlasse = $ruhig ? '' : $status;
            ?>
            <a href="<?= e(filterLink(['bereich' => $schluessel])) ?>"
               class="top-risiko text-decoration-none <?= $ruhig ? 'top-risiko--ruhig' : '' ?>">
                <div class="top-risiko__kopf">
                    <span class="fw-semibold"><?= e(BEREICHE[$schluessel]) ?></span>
                    <?= statusBadge($status) ?>
                    <?php // Ueberschreitung einzelner Budgets wird nicht mit dem Puffer der anderen verrechnet ?>
                    <span class="ms-auto tabular small <?= $daten['risiko'] > 0 ? 'text-ueberschritten fw-semibold' : 'text-muted' ?>">
                        <?= $daten['risiko'] > 0
                            ? 'Voraussichtlich ' . euro($daten['risiko']) . ' über Budget (' . ($daten['risikoBudgets'] === 1 ? '1 Budget' : $daten['risikoBudgets'] . ' Budgets') . ')'
                            : euro($puffer) . ' Puffer bis Laufzeitende' ?>
                    </span>
                </div>
                <div class="verbrauch-balken verbrauch-balken--gross">
                    <div class="verbrauch-balken__fuellung <?= e($balkenKlasse) ?>" style="width: <?= $fuellung ?>%"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="kennzahl">
            <div class="etikett">Gesamtbudget</div>
            <div class="kennzahl__wert"><?= euro($summe['soll']) ?></div>
            <div class="kennzahl__info"><?= $summe['laufend'] === 1 ? '1 laufendes Budget' : $summe['laufend'] . ' laufende Budgets' ?></div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="kennzahl">
            <div class="etikett">Ausgegeben</div>
            <div class="kennzahl__wert"><?= euro($summe['ist']) ?></div>
            <div class="kennzahl__info"><?= prozent($summe['verbrauch_prozent']) ?> vom Budget</div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <?php
            // Hochrechnung statt "Verfuegbar" als Hauptzahl: "noch 9.800 € verfuegbar"
            // klingt nach Spielraum, obwohl laut Plan Geld fehlt. Verfuegbar bleibt als Zusatz.
            $gesamtDifferenz = $summe['hochrechnung'] - $summe['soll'];
        ?>
        <div class="kennzahl <?= $gesamtDifferenz > 0 ? 'kennzahl--negativ' : ($prognoseUeber ? '' : 'kennzahl--betont') ?>">
            <div class="etikett">Hochrechnung gesamt</div>
            <div class="kennzahl__wert"><?= euro($summe['hochrechnung']) ?></div>
            <div class="kennzahl__info fw-semibold">
                <?= $gesamtDifferenz > 0 ? euro($gesamtDifferenz) . ' über dem Gesamtbudget' : euro(-$gesamtDifferenz) . ' Puffer zum Gesamtbudget' ?>
            </div>
            <?php if ($prognoseUeber): ?>
                <?php // Einzige verrechnete Zahl der Seite - ausdruecklich als solche benannt ?>
                <div class="kennzahl__info">wenn Puffer anderer Budgets umgeschichtet werden</div>
            <?php endif; ?>
            <div class="kennzahl__info">Heute noch <?= euro($summe['verfuegbar']) ?> verfügbar</div>
        </div>
    </div>
</div>

<div class="filterleiste mb-3">
    <div class="filterleiste__gruppe">
        <span class="etikett filterleiste__label">Status</span>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(filterLink(['status' => ''])) ?>" class="filter-chip <?= $filterStatus === '' ? 'aktiv' : '' ?>">
                Alle <span class="filter-chip__zahl"><?= count($alle) ?></span>
            </a>
            <?php foreach (STATUS_LABEL as $status => $label): ?>
                <?php $aktiv = $filterStatus === $status; ?>
                <a href="<?= e(filterLink(['status' => $aktiv ? '' : $status])) ?>"
                   class="filter-chip filter-chip--<?= e(str_replace('_', '-', $status)) ?> <?= $aktiv ? 'aktiv' : '' ?>"
                   title="<?= $aktiv ? 'Klicken, um den Filter zu entfernen' : '' ?>">
                    <?= e($label) ?> <span class="filter-chip__zahl"><?= $summe['anzahl'][$status] ?></span>
                    <?php if ($aktiv): ?><span class="filter-chip__entfernen" aria-hidden="true">×</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="filterleiste__gruppe">
        <span class="etikett filterleiste__label">Bereich</span>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(filterLink(['bereich' => ''])) ?>" class="filter-chip <?= $filterBereich === '' ? 'aktiv' : '' ?>">
                Alle Bereiche
            </a>
            <?php foreach (BEREICHE as $schluessel => $label): ?>
                <?php $aktiv = $filterBereich === $schluessel; ?>
                <a href="<?= e(filterLink(['bereich' => $aktiv ? '' : $schluessel])) ?>"
                   class="filter-chip <?= $aktiv ? 'aktiv' : '' ?>"
                   title="<?= $aktiv ? 'Klicken, um den Filter zu entfernen' : '' ?>">
                    <?= e($label) ?> <span class="filter-chip__zahl"><?= (int) ($anzahlJeBereich[$schluessel] ?? 0) ?></span>
                    <?php if ($aktiv): ?><span class="filter-chip__entfernen" aria-hidden="true">×</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="filterleiste__gruppe">
        <span class="etikett filterleiste__label">Suche</span>
        <form method="get" action="/finance/" class="d-flex flex-wrap flex-sm-nowrap gap-2">
            <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            <input type="hidden" name="bereich" value="<?= e($filterBereich) ?>">
            <input type="search" class="form-control form-control-sm suche-feld" name="q" value="<?= e($suche) ?>"
                   placeholder="Budget oder Owner suchen …" aria-label="Budget oder Owner suchen">
        </form>
    </div>
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
                    <th data-sortieren="zahl" class="d-none d-sm-table-cell" title="Sortiert nach Dringlichkeit: Überschritten, Warnung, Im Rahmen, Abgelaufen">Status</th>
                    <th data-sortieren="zahl" class="d-none d-lg-table-cell">Läuft bis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gefiltert as ['budget' => $budget, 'k' => $k]): ?>
                    <?php $link = '/finance/budget.php?id=' . (int) $budget['id']; ?>
                    <tr class="zeile-klickbar" data-link="<?= e($link) ?>">
                        <td data-wert="<?= e($budget['name']) ?>">
                            <a href="<?= e($link) ?>" class="fw-semibold text-reset"><?= e($budget['name']) ?></a>
                            <div class="small text-muted"><?= e(bereichLabel($budget['bereich'])) ?></div>
                            <div class="d-sm-none mt-1"><?= statusBadge($k['status'], statusGrund($budget, $k)) ?></div>
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
                        <td class="d-none d-sm-table-cell" data-wert="<?= STATUS_RANG[$k['status']] ?>"><?= statusBadge($k['status'], statusGrund($budget, $k)) ?></td>
                        <td class="small text-nowrap d-none d-lg-table-cell" data-wert="<?= e($budget['gueltig_bis']) ?>">
                            <?= e(datumDe($budget['gueltig_bis'])) ?>
                            <div class="<?= laeuftBaldAb($k['tage_bis_verfall']) ? 'text-warnung fw-semibold' : 'text-muted' ?>"><?= e(restlaufzeitText($k['tage_bis_verfall'])) ?></div>
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

<?php require __DIR__ . '/../../templates/footer.php'; ?>
