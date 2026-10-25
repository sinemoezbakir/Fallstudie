<?php
/**
 * Passwort vergessen – bewusst einfach gehalten (E-009): kein Mailserver
 * im Projekt eingerichtet, daher kein automatischer Reset-Link per Mail.
 * Stattdessen wie in einem kleinen Studio üblich: die Verwaltung (Admin)
 * setzt das Passwort manuell zurück.
 */

require_once __DIR__ . '/../src/app.php';

$pageTitle = 'Passwort vergessen';
require __DIR__ . '/../templates/header.php';
?>

<div class="card p-4 p-md-5 mx-auto" style="max-width: 520px;">
    <h1 class="h2 mb-3">Passwort vergessen?</h1>
    <p>Kein Problem – so geht's:</p>
    <ol class="mb-4">
        <li class="mb-2">Gib der <strong>Verwaltung (Admin)</strong> kurz Bescheid.</li>
        <li class="mb-2">Sie setzt in der Benutzerverwaltung ein vorläufiges Passwort für dich.</li>
        <li>Melde dich damit an und ändere es im Kontomenü unter <strong>„Passwort ändern“</strong>.</li>
    </ol>
    <p class="small text-muted">
        Einen automatischen Reset per E-Mail gibt es bewusst nicht – dafür ist in diesem kleinen Studio kein Mailversand eingerichtet.
    </p>
    <a href="/login.php" class="btn btn-maliá-outline align-self-start">← Zurück zum Login</a>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
