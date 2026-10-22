<?php
/**
 * Einstiegspunkt der Anwendung.
 *
 * Leitet eingeloggte Nutzer je nach Rolle auf ihre Startseite weiter,
 * nicht eingeloggte Nutzer zum Login. Die eigentliche Session- und
 * Rollenprüfung kommt mit MB-020 (Login) und MB-021 (Rollen) dazu -
 * bis dahin ist dies nur das Grundgerüst.
 */

session_start();

$rolle = $_SESSION['rolle'] ?? null;

switch ($rolle) {
    case 'ADMIN':
        header('Location: /admin/');
        break;
    case 'FINANCE':
        header('Location: /finance/');
        break;
    case 'OWNER':
        header('Location: /owner/');
        break;
    default:
        header('Location: /login.php');
        break;
}

exit;
