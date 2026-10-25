<?php
/**
 * 419 – Formular abgelaufen: CSRF-Token fehlt oder passt nicht zur Session
 * (siehe src/csrf.php). Passiert in der Praxis, wenn man ein Formular sehr
 * lange offen lässt und die Session inzwischen abgelaufen ist.
 */

$fehlerCode = 419;
$fehlerTitel = 'Formular abgelaufen';
$fehlerText = 'Aus Sicherheitsgründen wurde das Formular nicht gespeichert. '
    . 'Bitte lade die Seite neu und schicke es noch einmal ab.';
require __DIR__ . '/fehlerseite.php';
