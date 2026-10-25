<?php
/**
 * PHPUnit-Bootstrap: lädt die gemeinsame App-Logik, die getestet wird.
 *
 * Nutzt dieselbe Datenbank wie die Anwendung (malia_budget). Jeder Test
 * öffnet/schliesst seine eigene Transaktion (Rollback in tearDown()), damit
 * die Demodaten durch Testläufe nicht verändert werden.
 */

require_once __DIR__ . '/../src/budget.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/validierung.php';
