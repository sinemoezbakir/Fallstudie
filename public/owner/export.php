<?php
declare(strict_types=1);

// CSV-Export aller Ausgaben und Forecasts eines eigenen Budgets (für Excel).

use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Repositories\ForecastRepository;
use Malia\Repositories\IstWertRepository;

require __DIR__ . '/../bootstrap.php';

$user = $auth->requireRole(User::ROLE_OWNER);

// Besitzprüfung wie in budget.php: fremdes oder unbekanntes Budget => 403
$budget = (new BudgetRepository($pdo))->findByIdAndOwner((int) ($_GET['id'] ?? 0), $user->id);
if ($budget === null) {
    fehlerseite(403, 'Auf dieses Budget haben Sie keinen Zugriff.');
}

$istWerte  = (new IstWertRepository($pdo))->findByBudgetAndOwner($budget->id, $user->id);
$forecasts = (new ForecastRepository($pdo))->findByBudgetAndOwner($budget->id, $user->id);

/**
 * Schützt vor "CSV-Injection": Excel führt Zellen, die mit = + - @ beginnen, als Formel aus.
 * Ein vorangestelltes Apostroph macht daraus normalen Text.
 */
function csvText(string $text): string
{
    return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
}

/** Deutsches Zahlenformat (Komma), damit Excel den Betrag als Zahl erkennt. */
function csvBetrag(float $betrag): string
{
    return number_format($betrag, 2, ',', '');
}

// Dateiname nur aus sicheren Zeichen
$dateiname = 'budget_' . $budget->id . '_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dateiname . '"');
header('Cache-Control: no-store');   // Finanzdaten nicht zwischenspeichern

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8-BOM: Excel erkennt sonst Umlaute falsch

// Semikolon als Trennzeichen (deutsches Excel). Vier Argumente: Anführungszeichen und Escape-Zeichen
$zeile = fn(array $felder) => fputcsv($out, $felder, ';', '"', '');

$zeile(['Budget', csvText($budget->name)]);
$zeile(['Bereich', csvText($budget->bereich)]);
$zeile(['Gültig von', $budget->gueltigVon]);
$zeile(['Gültig bis', $budget->gueltigBis]);
$zeile(['Soll', csvBetrag($budget->sollBetrag)]);
$zeile([]);

$zeile(['Art', 'Datum', 'Beschreibung', 'Betrag (EUR)']);
foreach ($istWerte as $w) {
    $zeile(['Ausgabe', $w->datum, csvText($w->beschreibung), csvBetrag($w->betrag)]);
}
foreach ($forecasts as $f) {
    $zeile(['Forecast', $f->datum, csvText($f->beschreibung), csvBetrag($f->betrag)]);
}

fclose($out);
