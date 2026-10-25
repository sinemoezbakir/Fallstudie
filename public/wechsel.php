<?php
declare(strict_types=1);

// Demo-Modus: Wechsel zwischen den Demo-Konten ohne erneute Anmeldung.
// Funktioniert nur, wenn 'demo_rollenwechsel' in config.php true ist.

use Malia\Repositories\UserRepository;

require __DIR__ . '/bootstrap.php';

$auth->requireLogin();

// Demo-Modus aus? Dann tut die Seite so, als gäbe es sie nicht.
if (empty($config['demo_rollenwechsel'])) {
    fehlerseite(404, 'Diese Seite existiert nicht.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Ungültige Anfrage.');
}

$username = (string) ($_POST['username'] ?? '');

// Nur Konten aus der Demo-Liste sind erlaubt
$ziel = in_array($username, $config['demo_konten'] ?? [], true)
    ? (new UserRepository($pdo))->findByUsername($username)
    : null;

if ($ziel === null) {
    http_response_code(400);
    exit('Unbekanntes Demo-Konto.');
}

$auth->switchTo($ziel);
$auth::redirect($auth::homeFor($ziel->role));
