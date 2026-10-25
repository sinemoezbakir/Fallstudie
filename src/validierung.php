<?php
/**
 * Eingabeprüfung für alle Formulare an einer Stelle (MB-051).
 *
 * Jede pruefe...()-Funktion bekommt die rohen Formulardaten und liefert
 *   ['werte' => [...bereinigte Werte...], 'fehler' => ['feld' => 'Meldung']]
 * Ist 'fehler' leer, darf gespeichert werden. Die Seiten zeigen die
 * Meldungen direkt unter dem jeweiligen Feld an (feldFehler() in ui.php).
 *
 * Bewusst ohne Datenbank- und Session-Zugriff, damit alles mit PHPUnit
 * testbar ist (tests/ValidierungTest.php).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ui.php';

/**
 * Liest einen Geldbetrag ein. Akzeptiert "12.50" (aus <input type="number">)
 * genauso wie die deutsche Schreibweise "12,50" oder "1.234,56".
 * Gibt null zurück, wenn es keine gültige Zahl mit max. 2 Nachkommastellen ist.
 */
function leseBetrag(mixed $eingabe): ?float
{
    if (!is_string($eingabe) && !is_numeric($eingabe)) {
        return null;
    }

    $text = str_replace([' ', "\u{00A0}", '€'], '', trim((string) $eingabe));

    if (str_contains($text, ',')) {
        // Deutsche Schreibweise: Punkt = Tausender, Komma = Dezimaltrenner
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
    }

    if (!preg_match('/^-?\d+(\.\d{1,2})?$/', $text)) {
        return null;
    }

    return round((float) $text, 2);
}

/** Prüft ein Datum im Format JJJJ-MM-TT streng (2026-02-31 ist ungültig). */
function istGueltigesDatum(mixed $datum): bool
{
    if (!is_string($datum)) {
        return false;
    }
    $geparst = DateTime::createFromFormat('!Y-m-d', $datum);

    return $geparst !== false && $geparst->format('Y-m-d') === $datum;
}

/** Gemeinsame Betragsprüfung: Pflicht, Zahl, Untergrenze, Obergrenze. */
function pruefeBetragsfeld(mixed $eingabe, array &$fehler, string $feld, bool $nullErlaubt = false): ?float
{
    $betrag = leseBetrag($eingabe);

    if ($betrag === null) {
        $fehler[$feld] = 'Bitte einen Betrag eingeben, z. B. 249,90.';
    } elseif ($nullErlaubt ? $betrag < 0 : $betrag <= 0) {
        $fehler[$feld] = $nullErlaubt ? 'Der Betrag darf nicht negativ sein.' : 'Der Betrag muss größer als 0 sein.';
    } elseif ($betrag > MAX_BETRAG) {
        $fehler[$feld] = 'Der Betrag ist zu groß (höchstens ' . euro(MAX_BETRAG) . ').';
    }

    return $betrag;
}

function pruefeTextfeld(mixed $eingabe, array &$fehler, string $feld, string $bezeichnung, int $maxLaenge): string
{
    $text = is_string($eingabe) ? trim($eingabe) : '';

    if ($text === '') {
        $fehler[$feld] = "Bitte {$bezeichnung} angeben.";
    } elseif (mb_strlen($text) > $maxLaenge) {
        $fehler[$feld] = "Höchstens {$maxLaenge} Zeichen erlaubt.";
    }

    return $text;
}

/**
 * Neue Ist-Buchung (MB-034). Das Datum muss im Gültigkeitszeitraum des
 * Budgets liegen und darf nicht in der Zukunft liegen.
 */
function pruefeAusgabe(array $eingabe, array $budget, ?string $heute = null): array
{
    $heute ??= date('Y-m-d');
    $fehler = [];

    $betrag = pruefeBetragsfeld($eingabe['betrag'] ?? null, $fehler, 'betrag');
    $beschreibung = pruefeTextfeld($eingabe['beschreibung'] ?? null, $fehler, 'beschreibung', 'eine Beschreibung', 255);

    $datum = $eingabe['datum'] ?? '';
    if (!istGueltigesDatum($datum)) {
        $fehler['datum'] = 'Bitte ein gültiges Datum wählen.';
    } elseif ($datum > $heute) {
        $fehler['datum'] = 'Ausgaben in der Zukunft können noch nicht erfasst werden.';
    } elseif ($datum < $budget['gueltig_von']) {
        $fehler['datum'] = 'Das Datum liegt vor dem Budgetbeginn (' . datumDe($budget['gueltig_von']) . ').';
    } elseif ($datum > $budget['gueltig_bis']) {
        $fehler['datum'] = 'Das Datum liegt nach dem Budgetende (' . datumDe($budget['gueltig_bis']) . ').';
    }

    return [
        'werte' => ['betrag' => $betrag, 'datum' => $datum, 'beschreibung' => $beschreibung],
        'fehler' => $fehler,
    ];
}

/** Korrektur einer Ist-Buchung (MB-035): neuer Betrag + Pflicht-Grund. */
function pruefeKorrektur(array $eingabe, array $buchung): array
{
    $fehler = [];

    $betrag = pruefeBetragsfeld($eingabe['betrag'] ?? null, $fehler, 'betrag');
    $grund = pruefeTextfeld($eingabe['grund'] ?? null, $fehler, 'grund', 'einen Grund für die Korrektur', 255);

    if (!isset($fehler['betrag']) && $betrag === round((float) $buchung['betrag'], 2)) {
        $fehler['betrag'] = 'Der Betrag ist unverändert – bitte den korrigierten Betrag eintragen.';
    }

    return ['werte' => ['betrag' => $betrag, 'grund' => $grund], 'fehler' => $fehler];
}

/**
 * Budget anlegen/bearbeiten (MB-031).
 *
 * @param int[] $ownerIds IDs aller Konten mit Rolle OWNER
 */
function pruefeBudget(array $eingabe, array $ownerIds): array
{
    $fehler = [];

    $name = pruefeTextfeld($eingabe['name'] ?? null, $fehler, 'name', 'einen Namen', 150);
    $soll = pruefeBetragsfeld($eingabe['soll_betrag'] ?? null, $fehler, 'soll_betrag');

    $bereich = $eingabe['bereich'] ?? '';
    if (!isset(BEREICHE[$bereich])) {
        $fehler['bereich'] = 'Bitte einen Bereich wählen.';
    }

    $ownerId = (int) ($eingabe['owner_id'] ?? 0);
    if (!in_array($ownerId, array_map('intval', $ownerIds), true)) {
        $fehler['owner_id'] = 'Bitte eine verantwortliche Person (Owner) wählen.';
    }

    $von = $eingabe['gueltig_von'] ?? '';
    $bis = $eingabe['gueltig_bis'] ?? '';
    if (!istGueltigesDatum($von)) {
        $fehler['gueltig_von'] = 'Bitte ein gültiges Startdatum wählen.';
    }
    if (!istGueltigesDatum($bis)) {
        $fehler['gueltig_bis'] = 'Bitte ein gültiges Enddatum wählen.';
    } elseif (!isset($fehler['gueltig_von']) && $von > $bis) {
        $fehler['gueltig_bis'] = 'Das Ende darf nicht vor dem Beginn liegen.';
    }

    return [
        'werte' => [
            'name' => $name, 'bereich' => $bereich, 'soll_betrag' => $soll,
            'gueltig_von' => $von, 'gueltig_bis' => $bis, 'owner_id' => $ownerId,
        ],
        'fehler' => $fehler,
    ];
}

/**
 * Passwortregeln: mindestens 8 Zeichen, höchstens 72 Byte (bcrypt schneidet
 * alles danach still ab), Wiederholung muss passen (falls abgefragt).
 * Gibt die Fehlermeldung zurück oder null.
 */
function pruefePasswort(mixed $passwort, mixed $wiederholung = null): ?string
{
    if (!is_string($passwort) || mb_strlen($passwort) < 8) {
        return 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    }
    if (strlen($passwort) > 72) {
        return 'Das Passwort darf höchstens 72 Zeichen lang sein.';
    }
    if ($wiederholung !== null && $passwort !== $wiederholung) {
        return 'Die beiden Passwörter stimmen nicht überein.';
    }

    return null;
}

/**
 * Neues Konto (MB-030).
 *
 * @param callable(string): bool $usernameVergeben prüft, ob es den Namen schon gibt
 */
function pruefeNeuenBenutzer(array $eingabe, callable $usernameVergeben): array
{
    $fehler = [];

    $anzeigename = pruefeTextfeld($eingabe['anzeigename'] ?? null, $fehler, 'anzeigename', 'einen Anzeigenamen', 100);

    $username = strtolower(trim((string) ($eingabe['username'] ?? '')));
    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
        $fehler['username'] = '3–50 Zeichen: Kleinbuchstaben, Ziffern, Punkt, Binde- oder Unterstrich.';
    } elseif ($usernameVergeben($username)) {
        $fehler['username'] = 'Dieser Benutzername ist bereits vergeben.';
    }

    $rolle = $eingabe['rolle'] ?? '';
    if (!isset(ROLLEN[$rolle])) {
        $fehler['rolle'] = 'Bitte eine Rolle wählen.';
    }

    $passwortFehler = pruefePasswort($eingabe['passwort'] ?? null);
    if ($passwortFehler !== null) {
        $fehler['passwort'] = $passwortFehler;
    }

    return [
        'werte' => ['anzeigename' => $anzeigename, 'username' => $username, 'rolle' => $rolle],
        'fehler' => $fehler,
    ];
}
