<?php
/**
 * 403 – Zugriff verweigert.
 * Wird von erfordereRolle() (src/rollen.php) und ladeEigenesBudget()
 * (src/budget.php) eingebunden, wenn die Rolle nicht passt oder ein Budget
 * nicht der anfragenden Person gehört (Risiko R3: Owner sieht fremde Daten).
 */

$fehlerCode = 403;
$fehlerTitel = 'Kein Zugriff';
$fehlerText = empty($_SESSION['user_id'])
    ? 'Bitte melde dich zuerst an. <a href="/login.php">Zum Login</a>'
    : 'Diese Seite oder diese Daten gehören nicht zu deinem Konto.';
require __DIR__ . '/fehlerseite.php';
