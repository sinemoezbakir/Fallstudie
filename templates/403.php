<?php
/**
 * 403 – Zugriff verweigert.
 * Wird von erfordereRolle() (src/rollen.php) und ladeEigenesBudget()
 * (src/budget.php) eingebunden, wenn die Rolle nicht passt oder ein Budget
 * nicht der anfragenden Person gehört (Risiko R3: Owner sieht fremde Daten).
 */

$pageTitle = 'Kein Zugriff';
require __DIR__ . '/header.php';
?>

<div class="text-center py-5">
    <h1 style="color: var(--rose);">403 – Kein Zugriff</h1>
    <p>Du hast keine Berechtigung, diese Seite oder diese Daten zu sehen.</p>
    <a href="/" class="btn" style="background-color: var(--sage); color: #fff;">Zur Startseite</a>
</div>

<?php require __DIR__ . '/footer.php'; ?>
