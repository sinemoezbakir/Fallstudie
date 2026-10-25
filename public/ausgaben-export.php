<?php
/**
 * Alle Ausgaben eines Budgets als CSV (MB-060).
 *
 * Finance darf jedes Budget exportieren, ein Owner nur seine eigenen -
 * dieselbe Datentrennung wie auf den Seiten (ladeEigenesBudget, Risiko R3).
 */

require_once __DIR__ . '/../src/app.php';
require_once __DIR__ . '/../src/csv.php';

$budgetId = (int) ($_GET['budget_id'] ?? 0);

$budget = match ($_SESSION['rolle'] ?? null) {
    ROLLE_FINANCE => ladeBudget($budgetId),
    ROLLE_OWNER => ladeEigenesBudget($budgetId, eingeloggteBenutzerId()),
    default => (function (): never {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    })(),
};

$zeilen = array_map(fn (array $buchung) => [
    datumDe($buchung['datum']),
    csvText($buchung['beschreibung']),
    csvZahl((float) $buchung['betrag']),
    $buchung['urspruenglicher_betrag'] !== null ? csvZahl((float) $buchung['urspruenglicher_betrag']) : '',
    $buchung['korrigiert_am'] !== null ? datumDe($buchung['korrigiert_am']) : '',
    csvText($buchung['korrektur_grund']),
    csvText($buchung['erfasst_von_name']),
], ladeBuchungen((int) $budget['id']));

$umlaute = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss', 'é' => 'e', 'á' => 'a'];
$kurzname = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', strtr($budget['name'], $umlaute)), '-'));

sendeCsv(
    "ausgaben-{$kurzname}-" . date('Y-m-d') . '.csv',
    ['Datum', 'Beschreibung', 'Betrag (€)', 'Ursprünglicher Betrag (€)', 'Korrigiert am', 'Korrekturgrund', 'Erfasst von'],
    $zeilen
);
