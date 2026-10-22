<?php
/**
 * 404 – Seite/Datensatz nicht gefunden.
 */

$pageTitle = 'Nicht gefunden';
require __DIR__ . '/header.php';
?>

<div class="text-center py-5">
    <h1>404 – Nicht gefunden</h1>
    <p>Die angeforderte Seite oder der Datensatz existiert nicht.</p>
    <a href="/" class="btn" style="background-color: var(--sage); color: #fff;">Zur Startseite</a>
</div>

<?php require __DIR__ . '/footer.php'; ?>
