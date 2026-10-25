<?php
/**
 * Demo-Modus: alle Daten auf den Stand von database/demodaten.sql
 * zurücksetzen (Button im Kontomenü). Danach bleibt man im selben Konto
 * angemeldet, sofern es das Konto in den Demodaten gibt - sonst Finance.
 */

require_once __DIR__ . '/../src/app.php';
require_once __DIR__ . '/../src/demo.php';

eingeloggteBenutzerId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !DEMO_MODUS) {
    http_response_code(403);
    require __DIR__ . '/../templates/403.php';
    exit;
}

$username = $_SESSION['username'] ?? 'finance';

setzeDemoDatenZurueck();

$stmt = $pdo->prepare("SELECT id FROM benutzer WHERE username IN (:username, 'finance') ORDER BY username = :username2 DESC LIMIT 1");
$stmt->execute(['username' => $username, 'username2' => $username]);
wechsleZuKonto((int) $stmt->fetchColumn());

setzeFlash('erfolg', 'Die Demo-Daten wurden auf den Ausgangsstand zurückgesetzt.');
header('Location: /');
exit;
