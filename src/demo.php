<?php
/**
 * Demo-Modus: Kontowechsel ohne erneuten Login (E-021).
 *
 * Für Präsentation und Sprechstunde: Man meldet sich einmal an und kann
 * danach über die Leiste unter der Navigation in jedes Konto wechseln.
 * Der Wechsel setzt exakt denselben Session-Zustand wie ein echter Login
 * (setzeAngemeldet()), d. h. Rollenprüfung (erfordereRolle) und
 * Datentrennung (ladeEigenesBudget) greifen für das neue Konto genauso.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

/** Alle Konten für die Wechsel-Leiste: Admin, Finance, dann Owner. */
function demoKonten(): array
{
    global $pdo;

    return $pdo->query(
        "SELECT id, username, anzeigename, rolle FROM benutzer
         ORDER BY FIELD(rolle, 'ADMIN', 'FINANCE', 'OWNER'), anzeigename"
    )->fetchAll();
}

/**
 * Wechselt in das Konto mit der angegebenen ID. Gibt das Konto zurück,
 * oder null, wenn der Demo-Modus aus ist oder es das Konto nicht gibt.
 */
function wechsleZuKonto(int $benutzerId, bool $demoModus = DEMO_MODUS): ?array
{
    global $pdo;

    if (!$demoModus) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, username, anzeigename, rolle FROM benutzer WHERE id = :id');
    $stmt->execute(['id' => $benutzerId]);
    $benutzer = $stmt->fetch();

    if (!$benutzer) {
        return null;
    }

    setzeAngemeldet($benutzer);

    return $benutzer;
}
