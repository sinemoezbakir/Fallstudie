<?php
/**
 * Abmelden: Session komplett beenden.
 */

require_once __DIR__ . '/../src/app.php';
$_SESSION = [];
session_destroy();

header('Location: /login.php');
exit;
