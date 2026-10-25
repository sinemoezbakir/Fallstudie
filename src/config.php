<?php
/**
 * Zentrale Einstellungen der Anwendung.
 *
 * Die Datenbank-Zugangsdaten stehen weiterhin in src/db.php (E-003).
 */

/**
 * Demo-Modus (E-021): Nach dem Login lässt sich oben im Kopfbereich in jedes
 * Konto wechseln (Admin, Finance, jede Owner-Person), ohne sich ab- und
 * wieder anzumelden. Rollenprüfung und Datentrennung gelten dabei
 * unverändert für das jeweils aktive Konto.
 *
 * Für einen echten Betrieb abschalten: Umgebungsvariable MALIA_DEMO=0.
 */
define('DEMO_MODUS', getenv('MALIA_DEMO') !== '0');

/** Passwort aller Demo-Konten (siehe database/demodaten.sql). */
const DEMO_PASSWORT = 'demo1234';

/** Nach so vielen Minuten ohne Aktivität wird man automatisch abgemeldet. */
const SESSION_TIMEOUT_MINUTEN = 120;

/** Größter Betrag, den DECIMAL(10,2) speichern kann (E-015). */
const MAX_BETRAG = 99999999.99;
