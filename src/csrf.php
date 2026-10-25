<?php
/**
 * Schutz vor Cross-Site Request Forgery (CSRF).
 *
 * Jedes Formular schickt ein geheimes Token aus der Session mit. Eine fremde
 * Webseite kennt dieses Token nicht und kann deshalb kein Formular im Namen
 * der eingeloggten Person abschicken (z. B. eine Ausgabe buchen).
 *
 * Die Prüfung passiert zentral in src/app.php für JEDEN POST - eine Seite
 * kann sie also nicht vergessen. Im Formular reicht <?= csrfFeld() ?>.
 */

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfFeld(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Vergleicht in konstanter Zeit (hash_equals), damit sich das Token nicht
 * Zeichen für Zeichen über Antwortzeiten erraten lässt.
 */
function csrfGueltig(?string $token): bool
{
    $erwartet = $_SESSION['csrf_token'] ?? '';

    return is_string($token) && $erwartet !== '' && hash_equals($erwartet, $token);
}
