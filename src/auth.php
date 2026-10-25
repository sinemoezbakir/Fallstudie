<?php
/**
 * Login-Prüfung und Anmelde-Zustand.
 *
 * pruefeLogin() ist getrennt von Session/Redirect-Logik in login.php,
 * damit sie mit PHPUnit testbar ist (MB-050).
 */

require_once __DIR__ . '/db.php';

/**
 * Gibt die Benutzerdaten zurück, wenn Username + Passwort zusammenpassen,
 * sonst null - bewusst ohne Unterscheidung zwischen "Username unbekannt"
 * und "Passwort falsch" (sonst liesse sich erraten, welche Usernamen
 * existieren).
 */
function pruefeLogin(string $username, string $passwort): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, username, passwort_hash, anzeigename, rolle FROM benutzer WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $benutzer = $stmt->fetch();

    if ($benutzer && password_verify($passwort, $benutzer['passwort_hash'])) {
        return $benutzer;
    }

    return null;
}

/**
 * Schreibt das angemeldete Konto in die Session. Wird beim Login und beim
 * Kontowechsel im Demo-Modus (src/demo.php) genutzt, damit beide Wege
 * exakt denselben Zustand herstellen.
 */
function setzeAngemeldet(array $benutzer): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true); // Schutz vor Session-Fixation
    }

    $_SESSION['user_id'] = (int) $benutzer['id'];
    $_SESSION['username'] = $benutzer['username'];
    $_SESSION['anzeigename'] = $benutzer['anzeigename'];
    $_SESSION['rolle'] = $benutzer['rolle'];
    $_SESSION['letzte_aktivitaet'] = time();
}
