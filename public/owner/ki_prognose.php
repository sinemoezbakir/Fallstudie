<?php
declare(strict_types=1);

use Malia\Auth;
use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Repositories\ForecastRepository;
use Malia\Repositories\IstWertRepository;
use Malia\Services\BudgetService;
use Malia\Services\KiPrognoseService;

require __DIR__ . '/../bootstrap.php';

// Nur OWNER. Antwort ist immer JSON (wird vom Skript auf der Detailseite abgeholt).
$user = $auth->requireRole(User::ROLE_OWNER);

/** Sendet eine JSON-Antwort und beendet das Skript. */
function jsonAntwort(int $status, array $daten): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($daten, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonAntwort(405, ['fehler' => 'Nur POST erlaubt.']);
}
if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    jsonAntwort(400, ['fehler' => 'Ungültige Anfrage. Bitte Seite neu laden.']);
}

// Besitzprüfung wie in der Detailansicht: fremdes oder unbekanntes Budget = 403
$budget = (new BudgetRepository($pdo))->findByIdAndOwner((int) ($_POST['id'] ?? 0), $user->id);
if ($budget === null) {
    jsonAntwort(403, ['fehler' => 'Auf dieses Budget haben Sie keinen Zugriff.']);
}

$ki = new KiPrognoseService($config['ki'] ?? []);
if (!$ki->istAktiv()) {
    jsonAntwort(503, ['fehler' => 'Die KI-Prognose ist nicht eingerichtet (API-Key fehlt in config.php).']);
}

// Wartezeit: höchstens ein Aufruf alle 10 Sekunden je Sitzung (schützt das Gratis-Kontingent)
$jetzt = time();
if ($jetzt - (int) ($_SESSION['ki_letzter_aufruf'] ?? 0) < 10) {
    jsonAntwort(429, ['fehler' => 'Bitte einen Moment warten und dann erneut versuchen.']);
}
$_SESSION['ki_letzter_aufruf'] = $jetzt;

// Nur Datum und Betrag werden weitergegeben (keine Beschreibungen, keine Namen)
$istWerte  = (new IstWertRepository($pdo))->findByBudgetAndOwner($budget->id, $user->id);
$forecasts = (new ForecastRepository($pdo))->findByBudgetAndOwner($budget->id, $user->id);
$nurZahlen = fn(array $liste) => array_map(fn($x) => ['datum' => $x->datum, 'betrag' => $x->betrag], $liste);

$service = new BudgetService();
$status  = $service->status($budget->sollBetrag, $service->summeIst($istWerte), $budget->gueltigBis);

try {
    $daten = $ki->baueDaten($budget, $nurZahlen($istWerte), $nurZahlen($forecasts), $status, date('Y-m-d'));
    jsonAntwort(200, ['text' => $ki->einschaetzung($daten)]);
} catch (RuntimeException $ex) {
    // Die Meldung ist bewusst nutzerfreundlich; technische Details stehen im Error-Log
    jsonAntwort(502, ['fehler' => $ex->getMessage()]);
}
