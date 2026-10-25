<?php
declare(strict_types=1);

/**
 * Kurzform für htmlspecialchars: wandelt Sonderzeichen wie < > & " in HTML-Entities um.
 * Jede Ausgabe in HTML läuft darüber (Schutz vor XSS).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Zeigt eine Fehlerseite im normalen Layout und beendet das Skript.
 * Wird für 403 (kein Zugriff), 404 und 500 (unerwarteter Fehler) genutzt.
 * Die Texte sind bewusst allgemein: keine Details, die einem Angreifer helfen.
 */
function fehlerseite(int $code, string $text): never
{
    $titel = [403 => 'Kein Zugriff', 404 => 'Seite nicht gefunden', 500 => 'Fehler'][$code] ?? 'Fehler';
    http_response_code($code);

    // Layout-Variablen; bei einem frühen Fehler (z. B. DB nicht erreichbar) gibt es noch keinen Benutzer
    $pageTitle = $titel;
    $basePath  = $GLOBALS['basePath'] ?? '';
    $config    = $GLOBALS['config'] ?? [];
    $user      = null;
    try {
        $auth = $GLOBALS['auth'] ?? null;
        $user = $auth ? $auth->currentUser() : null;
    } catch (Throwable $ex) {
        $user = null;
    }

    require __DIR__ . '/layout/header.php';
    echo '<h1 class="h3">' . $code . ' – ' . e($titel) . '</h1>';
    echo '<div class="alert alert-danger">' . e($text) . '</div>';
    if ($user !== null) {
        echo '<a class="btn btn-outline-secondary" href="' . e($basePath . \Malia\Auth::homeFor($user->role)) . '">Zur Startseite</a>';
    } else {
        echo '<a class="btn btn-outline-secondary" href="' . e($basePath) . 'login.php">Zur Anmeldung</a>';
    }
    require __DIR__ . '/layout/footer.php';
    exit;
}

/**
 * Liest einen Betrag aus einem Formular: höchstens 8 Vorkommastellen, höchstens
 * 2 Nachkommastellen, Komma oder Punkt als Trenner, kein Tausenderpunkt.
 * Strenger als is_numeric(), das auch "1e3" oder " 5" akzeptieren würde.
 * Gibt null zurück, wenn ungültig oder nicht größer als 0.
 */
function parseBetrag(string $raw): ?float
{
    $raw = str_replace(',', '.', trim($raw));
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0) {
        return null;
    }
    return round((float) $raw, 2);
}

/** Euro-Betrag im deutschen Format, z. B. 1.234,50 €. */
function euro(float $betrag): string
{
    return number_format($betrag, 2, ',', '.') . ' €';
}

/**
 * Name der CSS-Variable (siehe style.css) mit der Farbe eines Budget-Status.
 * Die Diagramme lesen die Farbe daraus, so bleibt style.css die einzige Stelle für Farben.
 */
function statusFarbVariable(string $status): string
{
    return [
        'IM_RAHMEN'      => '--malia-status-ok',
        'WARNUNG'        => '--malia-status-warning',
        'UEBERSCHRITTEN' => '--malia-status-over',
        'ABGELAUFEN'     => '--malia-status-expired',
    ][$status];
}

/**
 * Anzeigetext, CSS-Klasse (siehe style.css) und Symbol für einen Budget-Status.
 * Barrierefreiheit: Der Status steht immer als Text UND Symbol da, nie nur als Farbe.
 * Das Symbol hat jeweils eine andere Form (Haken, Dreieck, Kreuz, Uhr). \u{FE0E} erzwingt
 * die einfarbige Textdarstellung statt eines bunten Emojis.
 * @return array{0: string, 1: string, 2: string} [Text, CSS-Klasse, Symbol]
 */
function statusAnzeige(string $status): array
{
    return [
        'IM_RAHMEN'      => ['Im Rahmen', 'status-ok', "\u{2714}\u{FE0E}"],
        'WARNUNG'        => ['Warnung', 'status-warning', "\u{26A0}\u{FE0E}"],
        'UEBERSCHRITTEN' => ['Überschritten', 'status-over', "\u{2716}\u{FE0E}"],
        'ABGELAUFEN'     => ['Abgelaufen', 'status-expired', "\u{23F1}\u{FE0E}"],
    ][$status];
}

/** Status als reiner Text mit Symbol, z. B. "✖ Überschritten" (für Diagramme). */
function statusLabel(string $status): string
{
    [$text, , $symbol] = statusAnzeige($status);
    return $symbol . ' ' . $text;
}

/** Fertiges Status-Badge (HTML): Symbol + Text + Farbe. Das Symbol ist für Screenreader ausgeblendet. */
function statusBadge(string $status): string
{
    [$text, $klasse, $symbol] = statusAnzeige($status);
    return '<span class="badge ' . e($klasse) . '"><span aria-hidden="true">' . $symbol . '</span> '
        . e($text) . '</span>';
}
