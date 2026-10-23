<?php
/**
 * Gemeinsamer Seitenkopf für alle Rollen.
 * Erwartet, dass die Session bereits läuft (session_start() im aufrufenden
 * Skript) und $pageTitle optional gesetzt ist.
 */

require_once __DIR__ . '/../src/ui.php';

$rolle = $_SESSION['rolle'] ?? null;
$anzeigename = $_SESSION['anzeigename'] ?? null;
$pageTitle = $pageTitle ?? 'Maliá Budget';

$rollenLabel = [
    'ADMIN' => 'Verwaltung',
    'FINANCE' => 'Finance',
    'OWNER' => 'Owner',
][$rolle] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> · Maliá Budget</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-md">
    <div class="container-fluid">
        <a class="navbar-brand" href="/" style="font-family: var(--serif);">Maliá Budget</a>

        <?php if ($rolle): ?>
        <div class="d-flex align-items-center">
            <ul class="navbar-nav flex-row gap-3 me-3">
                <?php if ($rolle === 'ADMIN'): ?>
                    <li class="nav-item"><a class="nav-link" href="/admin/">Benutzerverwaltung</a></li>
                <?php elseif ($rolle === 'FINANCE'): ?>
                    <li class="nav-item"><a class="nav-link" href="/finance/">Budgets</a></li>
                <?php elseif ($rolle === 'OWNER'): ?>
                    <li class="nav-item"><a class="nav-link" href="/owner/">Mein Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <span class="text-muted small me-3 d-none d-md-inline">
                Hallo, <strong><?= htmlspecialchars($anzeigename ?? '') ?></strong>
                <span class="text-muted">(<?= htmlspecialchars($rollenLabel) ?>)</span>
            </span>
            <a href="/logout.php" class="btn btn-sm btn-maliá-outline">Abmelden</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container py-4">
    <?= zeigeFlash() ?>
