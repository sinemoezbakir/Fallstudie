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

/**
 * Zerlegt eine SQL-Datei in einzelne Anweisungen: Kommentarzeilen raus,
 * getrennt wird an ";" am Zeilenende (siehe Hinweis in demodaten.sql).
 */
function zerlegeSql(string $sql): array
{
    $ohneKommentare = preg_replace('/^\s*--.*$/m', '', $sql);
    $anweisungen = preg_split('/;\s*$/m', $ohneKommentare);

    return array_values(array_filter(array_map('trim', $anweisungen), fn ($a) => $a !== ''));
}

/**
 * Stellt den Auslieferungszustand der Demo wieder her: alle Tabellen leeren
 * und database/demodaten.sql neu einspielen. Praktisch vor der
 * Präsentation oder nachdem man in der Demo viel ausprobiert hat.
 */
function setzeDemoDatenZurueck(): void
{
    global $pdo;

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['forecast', 'ist_buchung', 'budget', 'benutzer'] as $tabelle) {
        $pdo->exec("TRUNCATE TABLE {$tabelle}");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    foreach (zerlegeSql(file_get_contents(__DIR__ . '/../database/demodaten.sql')) as $anweisung) {
        $pdo->exec($anweisung);
    }
}
