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
require_once __DIR__ . '/ui.php'; // euro(), datumDe() für die Texte in statusGrund()

/**
 * Reine Datentrennungs-Abfrage ohne Seiteneffekt (kein exit) - das macht
 * sie mit PHPUnit testbar (MB-050). Gibt null zurück, wenn das Budget
 * nicht existiert oder jemand anderem gehört.
 */
function findeBudgetFuerOwner(int $budgetId, int $ownerId): ?array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM budget WHERE id = :id AND owner_id = :owner_id');
    $stmt->execute(['id' => $budgetId, 'owner_id' => $ownerId]);
    $budget = $stmt->fetch();

    return $budget ?: null;
}

/**
 * Lädt ein Budget, aber NUR wenn es dem angegebenen Owner gehört.
 * Gehört es jemand anderem (oder existiert es nicht), bricht die Anfrage
 * mit 403 ab - unabhängig davon, ob die ID in der URL manipuliert wurde.
 * Dünner Wrapper um findeBudgetFuerOwner() für den Web-Kontext.
 */
function ladeEigenesBudget(int $budgetId, int $ownerId): array
{
    $budget = findeBudgetFuerOwner($budgetId, $ownerId);

    if ($budget === null) {
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

/**
 * Reine Datentrennungs-Abfrage für Ist-Buchungen, ohne Seiteneffekt
 * (analog zu findeBudgetFuerOwner(), MB-050 testbar).
 */
function findeBuchungFuerOwner(int $buchungId, int $ownerId): ?array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT ib.* FROM ist_buchung ib
         JOIN budget b ON b.id = ib.budget_id
         WHERE ib.id = :id AND b.owner_id = :owner_id'
    );
    $stmt->execute(['id' => $buchungId, 'owner_id' => $ownerId]);
    $buchung = $stmt->fetch();

    return $buchung ?: null;
}

/**
 * Lädt eine einzelne Ist-Buchung, aber NUR wenn das zugehörige Budget dem
 * angegebenen Owner gehört (dieselbe Logik wie ladeEigenesBudget(), nur für
 * Buchungen statt Budgets - wichtig für MB-035, sonst liesse sich über die
 * Buchungs-ID eine fremde Buchung korrigieren). Dünner Wrapper für den
 * Web-Kontext um findeBuchungFuerOwner().
 */
function ladeEigeneBuchung(int $buchungId, int $ownerId): array
{
    $buchung = findeBuchungFuerOwner($buchungId, $ownerId);

    if ($buchung === null) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }

    return $buchung;
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

// --- Auswertungen für Dashboard, Detailseite und Finance-Übersicht ----------

/**
 * Hängt an jedes Budget seine Kennzahlen: [['budget' => ..., 'k' => ...], ...]
 * Abgelaufene Budgets wandern ans Ende, sonst bleibt die Reihenfolge.
 */
function budgetsMitKennzahlen(array $budgets): array
{
    $eintraege = array_map(fn (array $b) => ['budget' => $b, 'k' => kennzahlenFuerBudget($b)], $budgets);

    usort($eintraege, fn ($a, $b) => ($a['k']['status'] === 'abgelaufen') <=> ($b['k']['status'] === 'abgelaufen'));

    return $eintraege;
}

/**
 * Summen über mehrere Budgets. Geldsummen nur über laufende Budgets
 * (abgelaufene sind abgeschlossen und würden "Verfügbar" verfälschen),
 * die Status-Zählung über alle.
 */
function zusammenfassung(array $eintraege): array
{
    $summe = ['soll' => 0.0, 'ist' => 0.0, 'verfuegbar' => 0.0, 'hochrechnung' => 0.0, 'laufend' => 0];
    $anzahlJeStatus = array_fill_keys(['im_rahmen', 'warnung', 'ueberschritten', 'abgelaufen'], 0);

    foreach ($eintraege as $eintrag) {
        $k = $eintrag['k'];
        $anzahlJeStatus[$k['status']]++;

        if ($k['status'] === 'abgelaufen') {
            continue;
        }
        $summe['laufend']++;
        $summe['soll'] += (float) $eintrag['budget']['soll_betrag'];
        $summe['ist'] += $k['ist'];
        $summe['verfuegbar'] += $k['verfuegbar'];
        $summe['hochrechnung'] += $k['hochrechnung'];
    }

    $summe['verbrauch_prozent'] = $summe['soll'] > 0 ? round($summe['ist'] / $summe['soll'] * 100, 1) : 0.0;
    $summe['anzahl'] = $anzahlJeStatus;

    return array_map(fn ($wert) => is_float($wert) ? round($wert, 2) : $wert, $summe);
}

/**
 * Warum ein Budget Aufmerksamkeit braucht - in Worten, für Dashboard und
 * Übersicht. Leerer String, wenn alles in Ordnung ist.
 */
function statusGrund(array $budget, array $k): string
{
    $soll = (float) $budget['soll_betrag'];

    return match ($k['status']) {
        'ueberschritten' => euro($k['ist'] - $soll) . ' über dem Soll',
        'warnung' => $k['verbrauch_prozent'] >= 80
            ? prozent($k['verbrauch_prozent']) . ' verbraucht'
            : 'Hochrechnung ' . euro($k['hochrechnung']) . ' liegt über dem Soll',
        'abgelaufen' => 'abgelaufen am ' . datumDe($budget['gueltig_bis']),
        default => '',
    };
}

/**
 * Liste für "Braucht Aufmerksamkeit": überschrittene und gewarnte Budgets
 * sowie laufende Budgets, die in den nächsten $tageGrenze Tagen ablaufen.
 */
function aufmerksamkeitsListe(array $eintraege, int $tageGrenze = 30): array
{
    $liste = [];

    foreach ($eintraege as $eintrag) {
        $budget = $eintrag['budget'];
        $k = $eintrag['k'];

        if (in_array($k['status'], ['ueberschritten', 'warnung'], true)) {
            $liste[] = ['budget' => $budget, 'k' => $k, 'art' => $k['status'], 'text' => statusGrund($budget, $k)];
        } elseif ($k['status'] === 'im_rahmen' && $k['tage_bis_verfall'] <= $tageGrenze) {
            $liste[] = [
                'budget' => $budget, 'k' => $k, 'art' => 'laeuft_ab',
                'text' => ucfirst(restlaufzeitText($k['tage_bis_verfall'])) . ' – noch ' . euro($k['verfuegbar']) . ' verfügbar',
            ];
        }
    }

    // Überschritten zuerst, dann Warnung, dann "läuft ab"
    $reihenfolge = ['ueberschritten' => 0, 'warnung' => 1, 'laeuft_ab' => 2];
    usort($liste, fn ($a, $b) => $reihenfolge[$a['art']] <=> $reihenfolge[$b['art']]);

    return $liste;
}

/**
 * Monatlicher Verlauf für das Forecast-Ist-Soll-Diagramm (MB-044):
 *  - ist:      kumulierte Ausgaben bis einschließlich des aktuellen Monats
 *  - prognose: ab dem aktuellen Monat Ist + offener Forecast, kumuliert
 *              (startet beim Vormonat, damit die Linie an das Ist anschließt)
 *  - soll:     Budgetgrenze als waagrechte Linie
 * Monate ohne Wert sind null (Chart.js zeichnet dort nichts).
 */
function verlaufFuerBudget(array $budget, ?string $heute = null): array
{
    global $pdo;

    $heute ??= date('Y-m-d');
    $aktuellerMonat = substr($heute, 0, 7) . '-01';
    $budgetId = (int) $budget['id'];

    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(datum, '%Y-%m-01') AS monat, SUM(betrag) AS summe
         FROM ist_buchung WHERE budget_id = :id GROUP BY monat"
    );
    $stmt->execute(['id' => $budgetId]);
    $istJeMonat = array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));

    $stmt = $pdo->prepare('SELECT monat, betrag FROM forecast WHERE budget_id = :id');
    $stmt->execute(['id' => $budgetId]);
    $forecastJeMonat = array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));

    $start = new DateTimeImmutable(substr($budget['gueltig_von'], 0, 7) . '-01');
    $ende = new DateTimeImmutable(substr($budget['gueltig_bis'], 0, 7) . '-01');

    // Buchungen vor Budgetbeginn (sollte es nicht geben) zählen zum ersten Monat
    $kumuliert = array_sum(array_filter($istJeMonat, fn ($m) => $m < $start->format('Y-m-d'), ARRAY_FILTER_USE_KEY));
    $istGesamt = array_sum($istJeMonat);
    $prognoseLaufend = $istGesamt;

    $verlauf = ['monate' => [], 'beschriftungen' => [], 'ist' => [], 'prognose' => [], 'soll' => (float) $budget['soll_betrag']];

    for ($monat = $start; $monat <= $ende; $monat = $monat->modify('+1 month')) {
        $schluessel = $monat->format('Y-m-d');
        $kumuliert += $istJeMonat[$schluessel] ?? 0.0;

        $verlauf['monate'][] = $schluessel;
        $verlauf['beschriftungen'][] = monatDe($schluessel, true);
        $verlauf['ist'][] = $schluessel <= $aktuellerMonat ? round($kumuliert, 2) : null;

        if ($schluessel >= $aktuellerMonat) {
            $prognoseLaufend += $forecastJeMonat[$schluessel] ?? 0.0;
            $verlauf['prognose'][] = round($prognoseLaufend, 2);
        } else {
            $verlauf['prognose'][] = null;
        }
    }

    // Anschluss: Prognose-Linie beginnt am letzten Ist-Punkt vor dem aktuellen Monat
    $index = array_search($aktuellerMonat, $verlauf['monate'], true);
    if ($index !== false && $index > 0) {
        $verlauf['prognose'][$index - 1] = $verlauf['ist'][$index - 1];
    }

    return $verlauf;
}

/** Alle Ist-Buchungen eines Budgets, neueste zuerst, mit Name der erfassenden Person. */
function ladeBuchungen(int $budgetId): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT ib.*, u.anzeigename AS erfasst_von_name
         FROM ist_buchung ib JOIN benutzer u ON u.id = ib.erfasst_von
         WHERE ib.budget_id = :id
         ORDER BY ib.datum DESC, ib.id DESC'
    );
    $stmt->execute(['id' => $budgetId]);

    return $stmt->fetchAll();
}

/** Forecast-Werte eines Budgets als ['2026-11-01' => 400.0, ...], aufsteigend. */
function ladeForecasts(int $budgetId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT monat, betrag FROM forecast WHERE budget_id = :id ORDER BY monat');
    $stmt->execute(['id' => $budgetId]);

    return array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
}
