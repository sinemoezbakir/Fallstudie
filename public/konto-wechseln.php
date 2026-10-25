<?php
/**
 * Demo-Modus: in ein anderes Konto wechseln (E-021). Nur per POST mit
 * CSRF-Token (geprüft in src/app.php) und nur für angemeldete Personen.
 */

require_once __DIR__ . '/../src/app.php';
require_once __DIR__ . '/../src/demo.php';

eingeloggteBenutzerId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !DEMO_MODUS) {
    http_response_code(403);
    require __DIR__ . '/../templates/403.php';
    exit;
}

$konto = wechsleZuKonto((int) ($_POST['benutzer_id'] ?? 0));

if ($konto === null) {
    setzeFlash('fehler', 'Dieses Konto gibt es nicht (mehr).');
} else {
    setzeFlash('hinweis', "Du siehst Maliá Budget jetzt als {$konto['anzeigename']} (" . rolleLabel($konto['rolle']) . ').');
}

header('Location: /');
exit;
