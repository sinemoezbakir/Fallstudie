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

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4">
            <h1 class="h3 mb-3">Passwort vergessen?</h1>
            <p>
                Kein Problem — wende dich kurz an die <strong>Verwaltung</strong> (Admin).
                Sie kann dein Passwort in der Benutzerverwaltung zurücksetzen.
            </p>
            <p class="text-muted small">
                Es gibt bewusst keinen automatischen Reset per E-Mail, dafür ist in diesem
                kleinen Studio kein Mailversand eingerichtet.
            </p>
            <a href="/login.php" class="btn btn-maliá-outline">Zurück zum Login</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
