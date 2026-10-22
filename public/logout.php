<?php
/**
 * Abmelden: Session komplett beenden.
 */

session_start();
$_SESSION = [];
session_destroy();

header('Location: /login.php');
exit;
