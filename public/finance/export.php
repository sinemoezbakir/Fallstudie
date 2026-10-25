<?php
/**
 * Finance: Budgetübersicht als CSV (MB-060) - mit denselben Filtern wie
 * die Übersicht (Status, Bereich, Suche).
 */

require_once __DIR__ . '/../../src/app.php';
require_once __DIR__ . '/../../src/csv.php';
erfordereRolle(ROLLE_FINANCE);

$status = isset(STATUS_LABEL[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$bereich = isset(BEREICHE[$_GET['bereich'] ?? '']) ? $_GET['bereich'] : '';
$suche = trim((string) ($_GET['q'] ?? ''));

$eintraege = filtereBudgets(budgetsMitKennzahlen(ladeAlleBudgets()), $status, $bereich, $suche);

$zeilen = array_map(fn (array $eintrag) => [
    csvText($eintrag['budget']['name']),
    bereichLabel($eintrag['budget']['bereich']),
    csvText($eintrag['budget']['owner_name']),
    datumDe($eintrag['budget']['gueltig_von']),
    datumDe($eintrag['budget']['gueltig_bis']),
    csvZahl((float) $eintrag['budget']['soll_betrag']),
    csvZahl($eintrag['k']['ist']),
    csvZahl($eintrag['k']['verfuegbar']),
    number_format($eintrag['k']['verbrauch_prozent'], 1, ',', ''),
    csvZahl($eintrag['k']['offener_forecast']),
    csvZahl($eintrag['k']['hochrechnung']),
    statusLabel($eintrag['k']['status']),
], $eintraege);

sendeCsv(
    'malia-budgets-' . date('Y-m-d') . '.csv',
    ['Budget', 'Bereich', 'Owner', 'Gültig von', 'Gültig bis', 'Soll (€)', 'Ist (€)', 'Verfügbar (€)',
        'Verbrauch (%)', 'Offener Forecast (€)', 'Hochrechnung (€)', 'Status'],
    $zeilen
);
