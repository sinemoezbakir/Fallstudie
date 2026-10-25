<?php
/*
 * Gemeinsamer Seitenkopf. Vor dem Einbinden setzt jede Seite:
 *   $pageTitle  – Titel der Seite (optional)
 *   $user       – angemeldeter Benutzer (User-Objekt) oder null (z. B. Login-Seite)
 * Danach folgt der Seiteninhalt und am Ende footer.php.
 */

use Malia\Auth;
use Malia\Models\User;

$pageTitle = $pageTitle ?? '';
$user      = $user ?? null;

// Menüpunkte je Rolle: Dateiname => Beschriftung.
// Neue Seiten werden hier einfach ergänzt.
$navigation = [
    User::ROLE_OWNER   => ['owner/index.php' => 'Meine Budgets'],
    User::ROLE_FINANCE => [
        'finance/overview.php' => 'Soll-Ist-Vergleich',
        'finance/budgets.php'  => 'Budgets verwalten',
    ],
    User::ROLE_ADMIN   => ['admin/users.php' => 'Benutzerverwaltung'],
];
$menu = $user ? ($navigation[$user->role] ?? []) : [];

// $basePath kommt aus bootstrap.php ("" oder "../" in Unterordnern)
$basePath = $basePath ?? '';
// Aktive Seite: Pfad relativ zu public/, z. B. "admin/users.php"
$publicDir   = str_replace('\\', '/', realpath(__DIR__ . '/../../public'));
$scriptFile  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$currentPage = ltrim(substr($scriptFile, strlen($publicDir)), '/');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle !== '' ? $pageTitle . ' – ' : '') ?>Studio Maliá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($basePath) ?>css/style.css" rel="stylesheet">
</head>
<body class="<?= $user ? 'app-shell' : '' ?>">
<?php if (!$user): ?>
<!-- Nicht angemeldet (Login, Fehlerseiten): nur eine schlichte Kopfleiste mit der Marke -->
<nav class="navbar malia-navbar mb-4">
    <div class="container"><span class="navbar-brand">Studio Maliá</span></div>
</nav>
<main class="container pb-4">
<?php else: ?>
<!-- Angemeldet: Seitenleiste links (auf Handys oben, einklappbar) und Inhalt rechts -->
<div class="app-layout">
<aside class="sidebar">
    <div class="sidebar-head">
        <span class="sidebar-brand">Studio Maliá</span>
        <button class="btn btn-sm sidebar-toggle d-md-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menü">&#9776; Menü</button>
    </div>
    <div class="sidebar-body collapse d-md-flex" id="mainNav">
        <nav aria-label="Hauptnavigation">
            <ul class="nav flex-column">
                <?php foreach ($menu as $file => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $file === $currentPage ? 'active' : '' ?>"
                           <?= $file === $currentPage ? 'aria-current="page"' : '' ?>
                           href="<?= e($basePath . $file) ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="sidebar-foot">
            <?php if (!empty($config['demo_rollenwechsel'])): ?>
                <!-- Demo-Modus: Konto wechseln ohne erneutes Login -->
                <form method="post" action="<?= e($basePath) ?>wechsel.php" class="mb-3">
                    <?= Auth::csrfField() ?>
                    <label for="kontoWechsel" class="small">Konto wechseln (Demo)</label>
                    <select name="username" id="kontoWechsel" class="form-select form-select-sm"
                            onchange="this.form.submit()">
                        <?php foreach ($config['demo_konten'] ?? [] as $konto): ?>
                            <option value="<?= e($konto) ?>" <?= $konto === $user->username ? 'selected' : '' ?>>
                                <?= e($konto) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-outline-secondary mt-1">Wechseln</button></noscript>
                </form>
            <?php endif; ?>
            <!-- Angemeldeter Benutzer und Logout (POST mit CSRF-Token) -->
            <div class="sidebar-user"><?= e($user->username) ?> (<?= e($user->role) ?>)</div>
            <form method="post" action="<?= e($basePath) ?>logout.php" class="mb-0">
                <?= Auth::csrfField() ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Abmelden</button>
            </form>
        </div>
    </div>
</aside>
<div class="app-content">
<main class="pb-4">
<?php endif; ?>
