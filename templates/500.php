<?php
/**
 * 500 – unerwarteter Fehler. Wird vom Exception-Handler in src/app.php
 * eingebunden; $datenbankFehler ist true, wenn die DB nicht erreichbar ist.
 */

$fehlerCode = 500;
$fehlerTitel = 'Da ist etwas schiefgelaufen';
$fehlerText = !empty($datenbankFehler)
    ? 'Die Datenbank ist nicht erreichbar. Läuft <strong>MySQL im XAMPP Control Panel</strong> '
        . 'und ist die Datenbank <code>malia_budget</code> importiert?'
    : 'Bitte versuche es noch einmal. Wenn der Fehler bleibt, steht die Ursache im PHP-Fehlerprotokoll.';
require __DIR__ . '/fehlerseite.php';
