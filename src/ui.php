<?php
/**
 * UI-Hilfsfunktionen: Status-Badge, Fortschrittsbalken, Flash-Messages.
 *
 * Die Status-Logik selbst (welcher Status gilt) kommt mit MB-033 in
 * src/budget.php - diese Funktionen hier rendern nur das fertige Ergebnis.
 */

/**
 * Rendert einen Status-Badge.
 *
 * @param string $status Einer von: 'im_rahmen', 'warnung', 'ueberschritten', 'abgelaufen'
 */
function statusBadge(string $status): string
{
    $beschriftungen = [
        'im_rahmen' => 'Im Rahmen',
        'warnung' => 'Warnung',
        'ueberschritten' => 'Überschritten',
        'abgelaufen' => 'Abgelaufen',
    ];

    $cssKlasse = str_replace('_', '-', $status);
    $text = $beschriftungen[$status] ?? ucfirst($status);

    return sprintf(
        '<span class="status-badge %s">%s</span>',
        htmlspecialchars($cssKlasse),
        htmlspecialchars($text)
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
        '<div class="verbrauch-balken"><div class="verbrauch-balken__fuellung %s" style="width: %.1f%%"></div></div>',
        htmlspecialchars($klasse),
        $prozentGedeckelt
    );
}

/**
 * Setzt eine Flash-Message für die nächste Seite (z. B. nach einem Formular-POST).
 */
function setzeFlash(string $typ, string $nachricht): void
{
    $_SESSION['flash'] = ['typ' => $typ, 'nachricht' => $nachricht];
}

/**
 * Gibt die aktuelle Flash-Message als HTML aus und löscht sie danach.
 * $typ ist 'erfolg' oder 'fehler' und wird 1:1 als Bootstrap-Alert-Klasse genutzt.
 */
function zeigeFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $bootstrapKlasse = $flash['typ'] === 'erfolg' ? 'alert-success' : 'alert-danger';

    return sprintf(
        '<div class="alert %s" role="alert">%s</div>',
        htmlspecialchars($bootstrapKlasse),
        htmlspecialchars($flash['nachricht'])
    );
}
