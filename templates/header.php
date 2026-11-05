<?php
/**
 * Gemeinsamer Seitenkopf für alle Rollen.
 * Erwartet, dass src/app.php eingebunden ist und $pageTitle optional gesetzt ist.
 */

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/ui.php';
require_once __DIR__ . '/../src/csrf.php';

$rolle = $_SESSION['rolle'] ?? null;
$anzeigename = $_SESSION['anzeigename'] ?? '';
$pageTitle = $pageTitle ?? 'Maliá Budget';
$rollenLabel = $rolle ? rolleLabel($rolle) : '';

// Demo-Modus (E-021): Leiste zum Kontowechsel unter der Navigation.
// isset($GLOBALS['pdo']): Auf der 500-Seite (Datenbank aus) gibt es keine Konten.
$demoKonten = [];
if ($rolle && DEMO_MODUS && isset($GLOBALS['pdo'])) {
    require_once __DIR__ . '/../src/demo.php';
    $demoKonten = demoKonten();
}
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
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="visually-hidden-focusable skip-link" href="#inhalt">Zum Inhalt springen</a>

<header class="sticky-top">
<nav class="navbar navbar-expand-lg">
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
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/admin') ?>" href="/admin/">
                        <svg class="nav-link__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                        Benutzer
                    </a></li>
                <?php elseif ($rolle === 'FINANCE'): ?>
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/finance') ?>" href="/finance/">
                        <svg class="nav-link__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M12 20V4M20 20v-7"/></svg>
                        Alle Budgets
                    </a></li>
                <?php elseif ($rolle === 'OWNER'): ?>
                    <li class="nav-item"><a class="nav-link <?= aktivWenn('/owner') ?>" href="/owner/">
                        <svg class="nav-link__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M8 15h3"/></svg>
                        Meine Budgets
                    </a></li>
                <?php endif; ?>
            </ul>

            <div class="dropdown">
                <button class="konto-knopf dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar avatar--<?= e(strtolower($rolle)) ?>"><?= e(initiale($anzeigename)) ?></span>
                    <span class="text-start lh-sm">
                        <span class="d-block fw-semibold"><?= e($anzeigename) ?></span>
                        <span class="d-block small text-muted"><?= e($rollenLabel) ?></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end konto-menue">
                    <div class="px-3 py-2 small text-muted"><?= e(ROLLEN[$rolle]['beschreibung'] ?? '') ?></div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="/konto.php">
                        <svg class="nav-link__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Passwort ändern
                    </a>
                    <?php if (DEMO_MODUS): ?>
                        <form method="post" action="/demo-zuruecksetzen.php">
                            <?= csrfFeld() ?>
                            <button type="submit" class="dropdown-item"
                                    data-bestaetigen="Alle Änderungen verwerfen und die Demo-Daten neu laden?">
                                <svg class="nav-link__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 1 3 6.7M3 12v5h5"/></svg>
                                Demo-Daten zurücksetzen
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/logout.php">
                        <?= csrfFeld() ?>
                        <button type="submit" class="dropdown-item">
                            <svg class="nav-link__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

<?php if ($demoKonten): ?>
<div class="demo-leiste">
    <div class="container d-flex align-items-center gap-3">
        <span class="demo-leiste__titel">
            <span class="demo-leiste__punkt" aria-hidden="true"></span>
            <span class="d-none d-md-inline">Demo · Ansicht wechseln</span>
        </span>
        <form method="post" action="/konto-wechseln.php" class="demo-leiste__konten">
            <?= csrfFeld() ?>
            <?php foreach ($demoKonten as $konto): ?>
                <?php $aktiv = (int) $konto['id'] === (int) ($_SESSION['user_id'] ?? 0); ?>
                <button type="submit" name="benutzer_id" value="<?= (int) $konto['id'] ?>"
                        class="demo-konto <?= $aktiv ? 'aktiv' : '' ?>"
                        title="<?= e(rolleLabel($konto['rolle']) . ': ' . ROLLEN[$konto['rolle']]['beschreibung']) ?>"
                        <?= $aktiv ? 'aria-current="true"' : '' ?>>
                    <span class="avatar avatar--klein avatar--<?= e(strtolower($konto['rolle'])) ?>"><?= e(initiale($konto['anzeigename'])) ?></span>
                    <span class="lh-sm text-start">
                        <span class="d-block demo-konto__name"><?= e($konto['anzeigename']) ?></span>
                        <span class="d-block demo-konto__rolle"><?= e(rolleLabel($konto['rolle'])) ?></span>
                    </span>
                </button>
            <?php endforeach; ?>
        </form>
    </div>
</div>
<?php endif; ?>
</header>

<main id="inhalt" class="container py-4 py-lg-5">
    <?= zeigeFlash() ?>
