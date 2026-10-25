<?php
/**
 * Gemeinsames Aussehen aller Fehlerseiten (403, 404, 419, 500).
 * Erwartet $fehlerCode, $fehlerTitel und $fehlerText.
 */

$pageTitle = $fehlerTitel;
require __DIR__ . '/header.php';
?>

<div class="fehlerseite text-center">
    <div class="fehlerseite__code"><?= htmlspecialchars((string) $fehlerCode) ?></div>
    <h1 class="h2 mb-3"><?= htmlspecialchars($fehlerTitel) ?></h1>
    <p class="text-muted mx-auto mb-4" style="max-width: 32rem;"><?= $fehlerText ?></p>
    <a href="/" class="btn btn-maliá">Zur Startseite</a>
</div>

<?php require __DIR__ . '/footer.php'; ?>
