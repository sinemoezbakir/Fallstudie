<?php
/**
 * Reine Login-Prüfung, getrennt von Session/Redirect-Logik in login.php,
 * damit sie mit PHPUnit testbar ist (MB-050).
 *
 * Gibt die Benutzerdaten zurück, wenn Username + Passwort zusammenpassen,
 * sonst null - bewusst ohne Unterscheidung zwischen "Username unbekannt"
 * und "Passwort falsch" (sonst liesse sich erraten, welche Usernamen
 * existieren).
 */

require_once __DIR__ . '/db.php';

function pruefeLogin(string $username, string $passwort): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, passwort_hash, anzeigename, rolle FROM benutzer WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $benutzer = $stmt->fetch();

    if ($benutzer && password_verify($passwort, $benutzer['passwort_hash'])) {
        return $benutzer;
    }

    return null;
}
