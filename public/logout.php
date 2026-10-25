<?php
/**
 * Abmelden: Session komplett beenden. Nur per POST (mit CSRF-Token aus
 * src/app.php), damit ein fremder Link niemanden abmelden kann.
 */

require_once __DIR__ . '/../src/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$_SESSION = [];
session_destroy();

session_start();
session_regenerate_id(true);
setzeFlash('hinweis', 'Du wurdest abgemeldet. Bis bald!');

header('Location: /login.php');
exit;
