<?php
/**
 * Owner: Budget-Detailseite - Kennzahlen, Diagramme (MB-043, MB-044) und
 * alle Ist-Buchungen (MB-041). Die Anzeige steht in templates/budget-detail.php
 * und wird auch von der Finance-Detailseite genutzt.
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_OWNER);

// Bricht selbst mit 403 ab, falls fremd oder nicht vorhanden (Risiko R3).
$budget = ladeEigenesBudget((int) ($_GET['id'] ?? 0), eingeloggteBenutzerId());

$ansicht = 'owner';
$zurueck = ['href' => '/owner/', 'text' => 'Meine Budgets'];

require __DIR__ . '/../../templates/budget-detail.php';
