<?php
/**
 * Gemeinsamer Einstieg für jede Seite in public/.
 *
 *   require_once __DIR__ . '/../../src/app.php';
 *   erfordereRolle(ROLLE_OWNER);
 *
 * Erledigt an einer Stelle, was sonst jede Seite selbst machen müsste:
 * sichere Session, Sicherheits-Header, freundliche Fehlerseite statt
 * PHP-Fehlermeldung, CSRF-Prüfung für jeden POST.
 */

require_once __DIR__ . '/config.php';

/**
 * Unerwartete Fehler (z. B. MariaDB in XAMPP nicht gestartet) zeigen eine
 * verständliche Seite statt eines Stacktraces. Details landen im PHP-Log.
 */
set_exception_handler(function (Throwable $fehler): void {
    error_log('[Maliá Budget] ' . $fehler);
    http_response_code(500);
    $datenbankFehler = $fehler instanceof PDOException;
    require __DIR__ . '/../templates/500.php';
});

// --- Session ---------------------------------------------------------------
session_name('malia_budget');
session_set_cookie_params([
    'httponly' => true,      // JavaScript kommt nicht an das Session-Cookie
    'samesite' => 'Lax',     // Cookie wird bei fremden POSTs nicht mitgeschickt
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

if (!empty($_SESSION['user_id'])) {
    $letzteAktivitaet = $_SESSION['letzte_aktivitaet'] ?? time();
    if (time() - $letzteAktivitaet > SESSION_TIMEOUT_MINUTEN * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'] = ['typ' => 'hinweis', 'nachricht' => 'Du wurdest nach längerer Inaktivität abgemeldet.'];
    }
    $_SESSION['letzte_aktivitaet'] = time();
}

// --- Sicherheits-Header ------------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
    . "img-src 'self' data:; frame-ancestors 'none'; form-action 'self'"
);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rollen.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/budget.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validierung.php';

// --- CSRF-Prüfung für jedes abgeschickte Formular ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfGueltig($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    require __DIR__ . '/../templates/419.php';
    exit;
}
