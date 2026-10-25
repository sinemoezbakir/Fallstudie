<?php
/**
 * CSV-Export (MB-060) im Format, das ein deutsches Excel direkt öffnet:
 * Semikolon als Trenner, Komma als Dezimalzeichen, UTF-8 mit BOM.
 */

/**
 * Freitext für CSV absichern (CSV-/Formel-Injection): Beginnt ein Text mit
 * = + - @ oder einem Steuerzeichen, würde Excel ihn als Formel ausführen,
 * z. B. eine Beschreibung "=HYPERLINK(...)". Ein vorangestelltes ' macht
 * daraus wieder reinen Text.
 */
function csvText(?string $text): string
{
    $text = (string) $text;

    return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
}

/** Betrag als Zahl, die Excel (deutsch) als Zahl erkennt: 1234,50 */
function csvZahl(float $wert): string
{
    return number_format($wert, 2, ',', '');
}

/**
 * Schickt die Zeilen als CSV-Download und beendet die Anfrage.
 *
 * @param string[]   $kopf   Spaltenüberschriften
 * @param string[][] $zeilen bereits mit csvText()/csvZahl() aufbereitet
 */
function sendeCsv(string $dateiname, array $kopf, array $zeilen): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $dateiname) . '"');
    header('Cache-Control: no-store');

    $ausgabe = fopen('php://output', 'w');
    fwrite($ausgabe, "\u{FEFF}"); // BOM, damit Excel UTF-8 erkennt (Umlaute)
    fputcsv($ausgabe, $kopf, ';');
    foreach ($zeilen as $zeile) {
        fputcsv($ausgabe, $zeile, ';');
    }
    fclose($ausgabe);
    exit;
}
