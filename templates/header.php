<?php
/**
 * Gemeinsamer Seitenkopf für alle Rollen.
 * Erwartet, dass src/app.php eingebunden ist und $pageTitle optional gesetzt ist.
 */

require_once __DIR__ . '/../src/ui.php';
require_once __DIR__ . '/../src/csrf.php';

$rolle = $_SESSION['rolle'] ?? null;
$anzeigename = $_SESSION['anzeigename'] ?? '';
$pageTitle = $pageTitle ?? 'Maliá Budget';

$rollenLabel = [
    'ADMIN' => 'Verwaltung',
    'FINANCE' => 'Finance',
    'OWNER' => 'Owner',
][$rolle] ?? '';

$initialen = mb_strtoupper(mb_substr($anzeigename, 0, 1));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffdfa">
    <title><?= e($pageTitle) ?> · Maliá Budget</title>
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="visually-hidden-focusable skip-link" href="#inhalt">Zum Inhalt springen</a>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/assets/img/favicon.svg" alt="" width="30" height="30">
            <span>Maliá <span class="navbar-brand__zusatz">Budget</span></span>
        </a>

        <?php if ($rolle): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#hauptnavigation"
                aria-controls="hauptnavigation" aria-expanded="false" aria-label="Menü öffnen">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="hauptnavigation">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                <?php if ($rolle === 'ADMIN'): ?>
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/admin') ?>" href="/admin/">Benutzer</a></li>
                <?php elseif ($rolle === 'FINANCE'): ?>
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/finance') ?>" href="/finance/">Alle Budgets</a></li>
                <?php elseif ($rolle === 'OWNER'): ?>
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/owner') ?>" href="/owner/">Meine Budgets</a></li>
                <?php endif; ?>
            </ul>

            <div class="dropdown">
                <button class="konto-knopf dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar avatar--<?= e(strtolower($rolle)) ?>"><?= e($initialen) ?></span>
                    <span class="text-start lh-sm">
                        <span class="d-block fw-semibold"><?= e($anzeigename) ?></span>
                        <span class="d-block small text-muted"><?= e($rollenLabel) ?></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end konto-menue">
                    <form method="post" action="/logout.php">
                        <?= csrfFeld() ?>
                        <button type="submit" class="dropdown-item">Abmelden</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

<main id="inhalt" class="container py-4 py-lg-5">
    <?= zeigeFlash() ?>
