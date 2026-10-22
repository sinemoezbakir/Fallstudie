<?php
/**
 * Datentrennung und Budget-Zugriff (Risiko R3: "Owner sieht fremde Daten").
 *
 * ladeEigenesBudget() ist die EINZIGE erlaubte Art, wie eine Owner-Seite ein
 * Budget anhand einer ID aus der URL lädt. Niemals direktes
 * "SELECT ... WHERE id = :id" ohne Owner-Prüfung - sonst reicht es, die ID
 * in der URL zu ändern, um fremde Budgets zu sehen.
 *
 * Die Kennzahlen-Berechnung (Ist, Verfügbar, Status, ...) kommt mit MB-033
 * dazu; diese Datei kümmert sich nur um den sicheren Zugriff.
 */

require_once __DIR__ . '/db.php';

/**
 * Lädt ein Budget, aber NUR wenn es dem angegebenen Owner gehört.
 * Gehört es jemand anderem (oder existiert es nicht), bricht die Anfrage
 * mit 403 ab - unabhängig davon, ob die ID in der URL manipuliert wurde.
 */
function ladeEigenesBudget(int $budgetId, int $ownerId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM budget WHERE id = :id AND owner_id = :owner_id');
    $stmt->execute(['id' => $budgetId, 'owner_id' => $ownerId]);
    $budget = $stmt->fetch();

    if (!$budget) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }

    return $budget;
}

/**
 * Lädt alle Budgets eines Owners (für das Dashboard, MB-040) - hier ist
 * keine zusätzliche Prüfung nötig, weil der Filter "WHERE owner_id = ..."
 * selbst schon die Trennung herstellt.
 */
function ladeBudgetsVonOwner(int $ownerId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM budget WHERE owner_id = :owner_id ORDER BY gueltig_bis');
    $stmt->execute(['owner_id' => $ownerId]);

    return $stmt->fetchAll();
}

// --- Kennzahlen und Status (MB-033, Formeln aus konzept.md Abschnitt 4) ---
//
// Summen werden per SQL SUM() gebildet, nicht in PHP aufaddiert (E-015).
// In PHP wird nur gerundet.

/**
 * Summe aller Ist-Buchungen eines Budgets.
 */
function berechneIst(int $budgetId): float
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(betrag), 0) FROM ist_buchung WHERE budget_id = :id');
    $stmt->execute(['id' => $budgetId]);

    return (float) $stmt->fetchColumn();
}

/**
 * Summe des Forecasts ab dem aktuellen Monat bis Budgetende ("offener Forecast").
 */
function berechneOffenenForecast(int $budgetId): float
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(betrag), 0) FROM forecast WHERE budget_id = :id AND monat >= :aktueller_monat'
    );
    $stmt->execute(['id' => $budgetId, 'aktueller_monat' => date('Y-m-01')]);

    return (float) $stmt->fetchColumn();
}

/**
 * Status eines Budgets nach der Reihenfolge aus konzept.md Abschnitt 4
 * (von oben nach unten prüfen, erste zutreffende Regel gewinnt):
 *   1. Abgelaufen       - heute > gueltig_bis
 *   2. Überschritten    - Ist > Soll
 *   3. Warnung          - Verbrauch% >= 80 ODER Hochrechnung > Soll
 *   4. Im Rahmen        - sonst
 *
 * Rückgabewerte passen zu den CSS-Klassen in style.css / statusBadge().
 */
function berechneStatus(array $budget): string
{
    $heute = date('Y-m-d');
    $sollBetrag = (float) $budget['soll_betrag'];
    $budgetId = (int) $budget['id'];

    $ist = berechneIst($budgetId);
    $hochrechnung = $ist + berechneOffenenForecast($budgetId);
    $verbrauchProzent = $sollBetrag > 0 ? ($ist / $sollBetrag * 100) : 0.0;

    if ($heute > $budget['gueltig_bis']) {
        return 'abgelaufen';
    }
    if ($ist > $sollBetrag) {
        return 'ueberschritten';
    }
    if ($verbrauchProzent >= 80 || $hochrechnung > $sollBetrag) {
        return 'warnung';
    }

    return 'im_rahmen';
}

/**
 * Prüft, ob ein Budget wegen Zeitablauf gesperrt ist (MB-045). Wird von den
 * Formularen zum Erfassen/Korrigieren von Ist-Werten (MB-034/MB-035) vor
 * dem Speichern aufgerufen - danach darf keine neue Buchung mehr rein.
 *
 * Reiner Datumsvergleich, unabhängig von berechneStatus() (das würde
 * zusätzliche, hier unnötige DB-Abfragen auslösen), aber dieselbe Regel
 * wie Status-Stufe 1 ("Abgelaufen").
 */
function istBudgetAbgelaufen(array $budget): bool
{
    return date('Y-m-d') > $budget['gueltig_bis'];
}

/**
 * Bündelt alle Kennzahlen eines Budgets für die Anzeige (Dashboard,
 * Detailseite, Übersichten). Rechnet bei jedem Aufruf live aus der
 * Datenbank ("Echtzeit", siehe konzept.md Abschnitt 4).
 */
function kennzahlenFuerBudget(array $budget): array
{
    $budgetId = (int) $budget['id'];
    $sollBetrag = round((float) $budget['soll_betrag'], 2);
    $ist = round(berechneIst($budgetId), 2);
    $offenerForecast = round(berechneOffenenForecast($budgetId), 2);
    $hochrechnung = round($ist + $offenerForecast, 2);
    $verfuegbar = round($sollBetrag - $ist, 2);
    $verbrauchProzent = $sollBetrag > 0 ? round($ist / $sollBetrag * 100, 1) : 0.0;

    $tageBisVerfall = (int) floor(
        (strtotime($budget['gueltig_bis']) - strtotime(date('Y-m-d'))) / 86400
    );

    return [
        'ist' => $ist,
        'verfuegbar' => $verfuegbar,
        'verbrauch_prozent' => $verbrauchProzent,
        'offener_forecast' => $offenerForecast,
        'hochrechnung' => $hochrechnung,
        'status' => berechneStatus($budget),
        'tage_bis_verfall' => $tageBisVerfall,
    ];
}
