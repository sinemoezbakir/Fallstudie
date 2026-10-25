<?php
declare(strict_types=1);

// Gemeinsamer Start für alle Seiten: Autoloader, Datenbank, Session, Auth.
// Jede Seite bindet diese Datei mit require ein und erhält $auth.

use Malia\Auth;
use Malia\Database;
use Malia\Repositories\UserRepository;

require __DIR__ . '/../vendor/autoload.php';

// Fehler nie im Browser anzeigen (sie könnten Pfade oder Zugangsdaten verraten),
// sondern ins PHP-Error-Log schreiben. Der Besucher sieht nur eine allgemeine 500-Seite.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $ex): void {
    error_log((string) $ex);
    fehlerseite(500, 'Es ist ein unerwarteter Fehler aufgetreten. Bitte später erneut versuchen.');
});

// Einfache Schutz-Header gegen Einbetten in fremde Seiten und MIME-Sniffing
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$config = require __DIR__ . '/../config/config.php';
$pdo    = Database::connect($config['db']);

// Basispfad für Links: Seiten in Unterordnern (z. B. admin/) brauchen "../",
// damit Links zu login.php, css/ usw. stimmen. Er wird aus der Ordnertiefe berechnet.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$publicDir = str_replace('\\', '/', __DIR__);
$relative  = trim(substr($scriptDir, strlen($publicDir)), '/');
$basePath  = $relative === '' ? '' : str_repeat('../', substr_count($relative, '/') + 1);
Auth::setBasePath($basePath);

Auth::startSession();
$auth = new Auth(new UserRepository($pdo));
