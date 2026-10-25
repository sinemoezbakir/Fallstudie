<?php
/**
 * UI-Hilfsfunktionen: Formatierung, Status-Badge, Fortschrittsbalken,
 * Flash-Meldungen.
 *
 * Die Status-Logik selbst (welcher Status gilt) steht in src/budget.php -
 * diese Funktionen hier rendern nur das fertige Ergebnis.
 */

const BEREICHE = [
    'STUDIO' => 'Studio',
    'CAFE' => 'Café',
    'MARKETING' => 'Marketing',
];

const STATUS_LABEL = [
    'im_rahmen' => 'Im Rahmen',
    'warnung' => 'Warnung',
    'ueberschritten' => 'Überschritten',
    'abgelaufen' => 'Abgelaufen',
];

const ROLLEN = [
    'ADMIN' => ['label' => 'Admin', 'beschreibung' => 'Benutzer und Rollen verwalten'],
    'FINANCE' => ['label' => 'Finance', 'beschreibung' => 'alle Budgets anlegen und überwachen'],
    'OWNER' => ['label' => 'Owner', 'beschreibung' => 'nur die eigenen Budgets'],
];

const MONATE = [
    1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
];

/** Kurzform für htmlspecialchars() in Templates. */
function e(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES);
}

/** 1234.5 -> "1.234,50 €" (geschütztes Leerzeichen, damit € nicht umbricht). */
function euro(float $betrag): string
{
    return number_format($betrag, 2, ',', '.') . "\u{00A0}€";
}

/** "2026-12-31" -> "31.12.2026" */
function datumDe(?string $datum): string
{
    if (!$datum) {
        return '';
    }

    return date('d.m.Y', strtotime($datum));
}

/** "2026-11-01" -> "November 2026" bzw. kurz "Nov 26" */
function monatDe(string $datum, bool $kurz = false): string
{
    $zeitpunkt = strtotime($datum);
    $name = MONATE[(int) date('n', $zeitpunkt)];

    return $kurz
        ? mb_substr($name, 0, 3) . ' ' . date('y', $zeitpunkt)
        : $name . ' ' . date('Y', $zeitpunkt);
}

function bereichLabel(string $bereich): string
{
    return BEREICHE[$bereich] ?? $bereich;
}

function rolleLabel(string $rolle): string
{
    return ROLLEN[$rolle]['label'] ?? $rolle;
}

/** Erster Buchstabe des Namens für den Avatar-Kreis. */
function initiale(string $name): string
{
    return mb_strtoupper(mb_substr($name, 0, 1));
}

function statusLabel(string $status): string
{
    return STATUS_LABEL[$status] ?? ucfirst($status);
}

/** "noch 12 Tage" / "läuft heute ab" / "seit 3 Tagen abgelaufen" */
function restlaufzeitText(int $tage): string
{
    if ($tage > 1) {
        return "noch {$tage} Tage";
    }
    if ($tage === 1) {
        return 'noch 1 Tag';
    }
    if ($tage === 0) {
        return 'läuft heute ab';
    }

    return abs($tage) === 1 ? 'seit 1 Tag abgelaufen' : 'seit ' . abs($tage) . ' Tagen abgelaufen';
}

/**
 * Rendert einen Status-Badge.
 *
 * @param string $status Einer von: 'im_rahmen', 'warnung', 'ueberschritten', 'abgelaufen'
 */
function statusBadge(string $status): string
{
    return sprintf(
        '<span class="status-badge %s">%s</span>',
        e(str_replace('_', '-', $status)),
        e(statusLabel($status))
    );
}

/**
 * Rendert einen Fortschrittsbalken für den Verbrauch in Prozent.
 */
function verbrauchsBalken(float $verbrauchProzent): string
{
    $prozentGedeckelt = max(0, min(100, $verbrauchProzent));

    $klasse = 'im-rahmen';
    if ($verbrauchProzent > 100) {
        $klasse = 'ueberschritten';
    } elseif ($verbrauchProzent >= 80) {
        $klasse = 'warnung';
    }

    return sprintf(
        '<div class="verbrauch-balken" role="progressbar" aria-label="Verbrauch" aria-valuenow="%.1f" aria-valuemin="0" aria-valuemax="100">'
        . '<div class="verbrauch-balken__fuellung %s" style="width: %.1f%%"></div></div>',
        $verbrauchProzent,
        e($klasse),
        $prozentGedeckelt
    );
}

// --- Formular-Helfer (MB-051): Fehlermeldung direkt am Feld ----------------

/** " is-invalid", wenn das Feld einen Fehler hat (rahmt es rot ein). */
function feldKlasse(array $fehler, string $feld): string
{
    return isset($fehler[$feld]) ? ' is-invalid' : '';
}

/** Fehlermeldung unter dem Feld, verknüpft per aria-describedby. */
function feldFehler(array $fehler, string $feld): string
{
    if (!isset($fehler[$feld])) {
        return '';
    }

    return sprintf('<div class="invalid-feedback" id="%s-fehler">%s</div>', e($feld), e($fehler[$feld]));
}

/** Attribute für ein Feld mit Fehler, damit Screenreader die Meldung vorlesen. */
function feldAria(array $fehler, string $feld): string
{
    return isset($fehler[$feld]) ? sprintf('aria-invalid="true" aria-describedby="%s-fehler"', e($feld)) : '';
}

/** Zuletzt eingegebener Wert (nach einem Fehler bleibt die Eingabe stehen). */
function alterWert(string $feld, string $standard = ''): string
{
    $wert = $_POST[$feld] ?? $standard;

    return e(is_string($wert) ? $wert : $standard);
}

/** Zusammenfassung oberhalb des Formulars, wenn etwas nicht passt. */
function fehlerHinweis(array $fehler): string
{
    if (!$fehler) {
        return '';
    }
    $anzahl = count($fehler);

    return '<div class="alert alert-danger" role="alert">'
        . ($anzahl === 1 ? 'Bitte prüfe das markierte Feld.' : "Bitte prüfe die {$anzahl} markierten Felder.")
        . '</div>';
}

/** "active" für den Menüpunkt der aktuellen Seite. */
function aktivWenn(string $pfadAnfang): string
{
    $pfad = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return str_starts_with($pfad, $pfadAnfang) ? 'active' : '';
}

/**
 * Setzt eine Flash-Message für die nächste Seite (z. B. nach einem Formular-POST).
 * $typ: 'erfolg', 'fehler' oder 'hinweis'.
 */
function setzeFlash(string $typ, string $nachricht): void
{
    $_SESSION['flash'] = ['typ' => $typ, 'nachricht' => $nachricht];
}

/**
 * Gibt die aktuelle Flash-Message als Toast (oben rechts, verschwindet
 * nach ein paar Sekunden) aus und löscht sie danach.
 */
function zeigeFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $icons = ['erfolg' => '✓', 'fehler' => '!', 'hinweis' => 'i'];
    $typ = isset($icons[$flash['typ']]) ? $flash['typ'] : 'hinweis';

    return sprintf(
        '<div class="toast-container position-fixed top-0 end-0 p-3">'
        . '<div class="toast maliá-toast maliá-toast--%s show" role="status" aria-live="polite" data-bs-delay="5000">'
        . '<div class="d-flex align-items-center gap-2 p-3"><span class="maliá-toast__icon">%s</span>'
        . '<div class="me-auto">%s</div>'
        . '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Schließen"></button>'
        . '</div></div></div>',
        e($typ),
        $icons[$typ],
        e($flash['nachricht'])
    );
}
