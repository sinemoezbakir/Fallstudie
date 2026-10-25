<?php
/**
 * Finance: Detailansicht eines beliebigen Budgets (nur lesend) - dieselbe
 * Ansicht wie beim Owner (templates/budget-detail.php), aber ohne Buchen.
 * Finance darf laut konzept.md Abschnitt 2 alle Budgets sehen.
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_FINANCE);

$budget = ladeBudget((int) ($_GET['id'] ?? 0));

$ansicht = 'finance';
$zurueck = ['href' => '/finance/', 'text' => 'Alle Budgets'];
$zusatzAktionen = [
    ['href' => '/finance/bearbeiten.php?id=' . $budget['id'], 'text' => 'Bearbeiten'],
];

require __DIR__ . '/../../templates/budget-detail.php';
