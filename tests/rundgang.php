<?php
/**
 * Automatischer Rundgang durch die LAUFENDE Anwendung (Systemtest, MB-056).
 *
 *   php tests/rundgang.php                      (Standard: http://localhost:8000)
 *   php tests/rundgang.php http://localhost:8080
 *
 * Meldet sich an, wechselt per Demo-Modus durch alle Konten, ruft jede Seite
 * auf, schickt alle Formulare ab und versucht gezielt, an fremde Daten zu
 * kommen. Prüft dabei Statuscodes, Inhalte und dass nirgends eine
 * PHP-Warnung/-Fehlermeldung im HTML landet.
 *
 * ACHTUNG: verändert Daten und setzt am Ende die Demo-Daten zurück.
 * Voraussetzung: Demo-Modus an (Standard), App und MariaDB laufen.
 */

$basis = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$cookieDatei = tempnam(sys_get_temp_dir(), 'rundgang');
$ok = 0;
$fehlgeschlagen = [];
$letzteSeite = '';

function anfrage(string $methode, string $pfad, array $daten = [], bool $folgen = false): array
{
    global $basis, $cookieDatei, $letzteSeite;

    $curl = curl_init($basis . $pfad);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookieDatei,
        CURLOPT_COOKIEFILE => $cookieDatei,
        CURLOPT_FOLLOWLOCATION => $folgen,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($methode === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($daten));
    }
    $antwort = curl_exec($curl);
    if ($antwort === false) {
        fwrite(STDERR, "Keine Verbindung zu {$basis} – läuft die App? (" . curl_error($curl) . ")\n");
        exit(2);
    }
    $kopfLaenge = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $ergebnis = [
        'code' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'kopf' => substr($antwort, 0, $kopfLaenge),
        'html' => substr($antwort, $kopfLaenge),
    ];
    curl_close($curl);

    if (preg_match('/^Location:\s*(\S+)/mi', $ergebnis['kopf'], $treffer)) {
        $ergebnis['ziel'] = $treffer[1];
    }
    if (str_contains($ergebnis['kopf'], 'text/html')) {
        $letzteSeite = $ergebnis['html'];
    }

    return $ergebnis;
}

function token(): string
{
    global $letzteSeite;
    preg_match('/name="csrf_token" value="([^"]+)"/', $letzteSeite, $treffer);

    return $treffer[1] ?? '';
}

function pruefe(string $beschreibung, bool $bedingung, string $details = ''): void
{
    global $ok, $fehlgeschlagen;

    if ($bedingung) {
        $ok++;
        echo "  \u{2713} {$beschreibung}\n";
    } else {
        $fehlgeschlagen[] = $beschreibung;
        echo "  \u{2717} {$beschreibung}" . ($details !== '' ? "  ({$details})" : '') . "\n";
    }
}

function ohnePhpMeldung(array $antwort): bool
{
    return !preg_match('/<b>(Warning|Notice|Deprecated|Fatal error)<\/b>|Uncaught /i', $antwort['html']);
}

function seite(string $pfad, int $erwartet = 200, ?string $enthaelt = null): array
{
    $antwort = anfrage('GET', $pfad);
    $passt = $antwort['code'] === $erwartet && ohnePhpMeldung($antwort)
        && ($enthaelt === null || str_contains($antwort['html'], $enthaelt));
    pruefe("GET {$pfad} → {$erwartet}" . ($enthaelt ? " mit „{$enthaelt}“" : ''), $passt, "bekommen: {$antwort['code']}");

    return $antwort;
}

function formular(string $seitenPfad, string $ziel, array $daten): array
{
    anfrage('GET', $seitenPfad);

    return anfrage('POST', $ziel, $daten + ['csrf_token' => token()]);
}

function kontoId(string $username): int
{
    global $letzteSeite;
    // Die Wechsel-Leiste enthält alle Konten: value="ID" ... Anzeigename
    preg_match_all('/name="benutzer_id" value="(\d+)"[^>]*>\s*<span[^>]*>[^<]*<\/span>\s*<span[^>]*>\s*<span class="d-block demo-konto__name">([^<]+)</', $letzteSeite, $treffer, PREG_SET_ORDER);
    $namen = ['admin' => 'Admin', 'finance' => 'Inhaberin', 'studio' => 'Studio-Leitung', 'cafe' => 'Café-Leitung', 'marketing' => 'Marketing-Leitung'];
    foreach ($treffer as [, $id, $name]) {
        if (html_entity_decode($name) === ($namen[$username] ?? $username)) {
            return (int) $id;
        }
    }

    return 0;
}

function wechsle(string $username): void
{
    anfrage('GET', '/');
    anfrage('GET', '/', [], true);
    $id = kontoId($username);
    $antwort = anfrage('POST', '/konto-wechseln.php', ['benutzer_id' => $id, 'csrf_token' => token()]);
    pruefe("Kontowechsel zu „{$username}“", $antwort['code'] === 302 && $id > 0);
}

function budgetId(string $name): int
{
    global $letzteSeite;
    // Links auf Budgets: href="/owner/budget.php?id=3" ... >Name<
    if (preg_match('/budget\.php\?id=(\d+)"[^>]*>' . preg_quote(htmlspecialchars($name, ENT_QUOTES), '/') . '</', $letzteSeite, $treffer)) {
        return (int) $treffer[1];
    }

    return 0;
}

echo "Rundgang gegen {$basis}\n";

// --- 1. Login ----------------------------------------------------------------
echo "\nLogin\n";
seite('/', 302);
seite('/login.php', 200, 'Willkommen zurück');
pruefe('Login ohne CSRF-Token wird abgelehnt (419)', anfrage('POST', '/login.php', ['username' => 'finance', 'passwort' => 'demo1234'])['code'] === 419);
$antwort = formular('/login.php', '/login.php', ['username' => 'finance', 'passwort' => 'falsch']);
pruefe('Falsches Passwort zeigt Fehlermeldung', str_contains($antwort['html'], 'Benutzername oder Passwort ist falsch'));
$antwort = formular('/login.php', '/login.php', ['username' => 'finance', 'passwort' => 'demo1234']);
pruefe('Login mit finance / demo1234', $antwort['code'] === 302);
pruefe('Session-Cookie ist HttpOnly und SameSite', (bool) preg_match('/Set-Cookie: malia_budget=.*HttpOnly.*SameSite=Lax/i', $antwort['kopf']));
$antwort = seite('/', 302);
pruefe('Finance landet auf /finance/', ($antwort['ziel'] ?? '') === '/finance/');

// --- 2. Finance --------------------------------------------------------------
echo "\nFinance\n";
seite('/finance/', 200, 'Alle Budgets');
seite('/finance/?status=warnung', 200, 'Trainer-Fortbildung');
seite('/finance/?bereich=CAFE&q=waren', 200, 'Café-Wareneinkauf Q4');
seite('/finance/budget.php?id=999999', 404);
seite('/finance/neu.php', 200, 'Neues Budget');
seite('/owner/', 403);
seite('/admin/', 403);

$antwort = formular('/finance/neu.php', '/finance/neu.php', ['name' => '', 'bereich' => 'X', 'soll_betrag' => '-1', 'gueltig_von' => '2026-12-01', 'gueltig_bis' => '2026-01-01', 'owner_id' => '0']);
pruefe('Budget anlegen mit Fehlern zeigt 5 Feldfehler', substr_count($antwort['html'], 'class="invalid-feedback"') === 5);
anfrage('GET', '/finance/neu.php');
preg_match('/<option value="(\d+)"[^>]*>\s*Studio-Leitung/', $letzteSeite, $treffer);
$antwort = anfrage('POST', '/finance/neu.php', [
    'name' => 'Rundgang-Testbudget', 'bereich' => 'STUDIO', 'soll_betrag' => '1.000,00',
    'gueltig_von' => date('Y-01-01'), 'gueltig_bis' => date('Y-12-31'), 'owner_id' => $treffer[1] ?? 0, 'csrf_token' => token(),
]);
pruefe('Budget anlegen und Studio-Leitung zuweisen', $antwort['code'] === 302);
$neuesBudget = (int) preg_replace('/\D/', '', $antwort['ziel'] ?? '');
seite("/finance/budget.php?id={$neuesBudget}", 200, 'Rundgang-Testbudget');
$antwort = formular("/finance/bearbeiten.php?id={$neuesBudget}", '/finance/bearbeiten.php', [
    'id' => $neuesBudget, 'name' => 'Rundgang-Testbudget', 'bereich' => 'STUDIO', 'soll_betrag' => '1200',
    'gueltig_von' => date('Y-01-01'), 'gueltig_bis' => date('Y-12-31'), 'owner_id' => $treffer[1] ?? 0,
]);
pruefe('Budget bearbeiten (Soll auf 1.200 €)', $antwort['code'] === 302);
seite("/finance/budget.php?id={$neuesBudget}", 200, '1.200,00');

$csv = anfrage('GET', '/finance/export.php?status=warnung');
pruefe('CSV-Export Budgets: Download mit BOM und Semikolon', $csv['code'] === 200 && str_contains($csv['kopf'], 'text/csv')
    && str_starts_with($csv['html'], "\u{FEFF}Budget;Bereich;"));

// --- 3. Owner: Studio-Leitung --------------------------------------------------
echo "\nOwner (Studio-Leitung)\n";
wechsle('studio');
seite('/owner/', 200, 'Braucht Aufmerksamkeit');
$kursmaterial = budgetId('Kursmaterial &amp; Kleingeräte') ?: budgetId('Kursmaterial & Kleingeräte');
pruefe('Dashboard zeigt das neue Testbudget von Finance', budgetId('Rundgang-Testbudget') === $neuesBudget);
seite("/owner/budget.php?id={$neuesBudget}", 200, 'Verlauf: Ist, Forecast und Soll');
seite('/finance/', 403);

$antwort = formular("/owner/ist-erfassen.php?budget_id={$neuesBudget}", '/owner/ist-erfassen.php', [
    'budget_id' => $neuesBudget, 'betrag' => '249,90', 'datum' => date('Y-m-d'), 'beschreibung' => 'Rundgang-Ausgabe',
]);
pruefe('Ausgabe 249,90 € erfassen', $antwort['code'] === 302);
seite("/owner/budget.php?id={$neuesBudget}", 200, 'Rundgang-Ausgabe');
seite("/owner/budget.php?id={$neuesBudget}", 200, '950,10');

$antwort = formular("/owner/ist-erfassen.php?budget_id={$neuesBudget}", '/owner/ist-erfassen.php', [
    'budget_id' => $neuesBudget, 'betrag' => '10', 'datum' => date('Y-m-d', strtotime('+3 days')), 'beschreibung' => 'Zukunft',
]);
pruefe('Ausgabe in der Zukunft wird abgelehnt', str_contains($antwort['html'], 'Zukunft können noch nicht'));

anfrage('GET', "/owner/budget.php?id={$neuesBudget}");
preg_match('/ist-korrigieren\.php\?id=(\d+)/', $letzteSeite, $treffer);
$buchung = (int) ($treffer[1] ?? 0);
$antwort = formular("/owner/ist-korrigieren.php?id={$buchung}", '/owner/ist-korrigieren.php', ['id' => $buchung, 'betrag' => '199,90', 'grund' => 'Rabatt']);
pruefe('Ausgabe korrigieren (249,90 → 199,90)', $antwort['code'] === 302);
seite("/owner/budget.php?id={$neuesBudget}", 200, 'Rabatt');

$monat = date('Y-m-01', strtotime('first day of next month'));
$antwort = formular("/owner/forecast.php?budget_id={$neuesBudget}", '/owner/forecast.php', [
    'budget_id' => $neuesBudget, 'forecast' => [$monat => '2.000,00'],
]);
pruefe('Forecast speichern', $antwort['code'] === 302);
if (date('m') !== '12') {
    seite("/owner/budget.php?id={$neuesBudget}", 200, 'Hochrechnung');
    $flash = anfrage('GET', '/owner/');
    pruefe('Budget mit Forecast über Soll steht unter „Braucht Aufmerksamkeit“', str_contains($flash['html'], 'Rundgang-Testbudget'));
}

$csv = anfrage('GET', "/ausgaben-export.php?budget_id={$neuesBudget}");
pruefe('CSV-Export Ausgaben (eigenes Budget)', $csv['code'] === 200 && str_contains($csv['html'], 'Rundgang-Ausgabe'));

// --- 4. Datentrennung: Café-Leitung versucht Studio-Daten --------------------
echo "\nDatentrennung (Café-Leitung greift auf Studio-Budget zu)\n";
wechsle('cafe');
seite("/owner/budget.php?id={$neuesBudget}", 403);
seite("/owner/ist-erfassen.php?budget_id={$neuesBudget}", 403);
seite("/owner/forecast.php?budget_id={$neuesBudget}", 403);
seite("/owner/ist-korrigieren.php?id={$buchung}", 403);
seite("/ausgaben-export.php?budget_id={$neuesBudget}", 403);
$antwort = formular('/owner/', '/owner/ist-erfassen.php', ['budget_id' => $neuesBudget, 'betrag' => '1', 'datum' => date('Y-m-d'), 'beschreibung' => 'Einbruch']);
pruefe('POST auf fremdes Budget wird abgelehnt (403)', $antwort['code'] === 403);
seite('/owner/', 200, 'Café-Wareneinkauf Q4');
pruefe('Café-Dashboard zeigt keine Studio-Budgets', !str_contains($letzteSeite, 'Reformer-Wartung'));

// --- 5. Verfallszeit: Marketing, abgelaufenes Frühlings-Event ---------------
echo "\nVerfallszeit (Marketing-Leitung, Frühlings-Event)\n";
wechsle('marketing');
anfrage('GET', '/owner/');
$fruehling = budgetId('Frühlings-Event');
seite("/owner/budget.php?id={$fruehling}", 200, 'Abgelaufen am');
pruefe('Abgelaufenes Budget hat keinen Erfassen-Knopf', !str_contains($letzteSeite, 'ist-erfassen.php?budget_id=' . $fruehling));
$antwort = formular("/owner/ist-erfassen.php?budget_id={$fruehling}", '/owner/ist-erfassen.php', ['budget_id' => $fruehling, 'betrag' => '5', 'datum' => '2026-04-01', 'beschreibung' => 'zu spät']);
pruefe('Buchung auf abgelaufenes Budget wird nicht gespeichert', $antwort['code'] === 200 && str_contains($antwort['html'], 'abgelaufen'));

// --- 6. Admin -------------------------------------------------------------------
echo "\nAdmin\n";
wechsle('admin');
seite('/admin/', 200, 'Benutzerverwaltung');
seite('/finance/', 403);
$antwort = formular('/admin/', '/admin/', ['anzeigename' => 'Rundgang Yoga', 'username' => 'rundgang', 'rolle' => 'OWNER', 'passwort' => 'geheim123']);
pruefe('Neuen Owner anlegen', $antwort['code'] === 302);
$antwort = formular('/admin/', '/admin/', ['anzeigename' => 'Doppelt', 'username' => 'rundgang', 'rolle' => 'OWNER', 'passwort' => 'geheim123']);
pruefe('Doppelter Benutzername wird abgelehnt', str_contains($antwort['html'], 'bereits vergeben'));

// --- 7. Passwort ändern + Logout ----------------------------------------------
echo "\nKonto und Abmelden\n";
seite('/konto.php', 200, 'Passwort ändern');
$antwort = formular('/konto.php', '/konto.php', ['aktuelles_passwort' => 'falsch', 'neues_passwort' => 'neues123x', 'neues_passwort_wiederholung' => 'neues123x']);
pruefe('Passwort ändern mit falschem aktuellem Passwort scheitert', str_contains($antwort['html'], 'stimmt nicht'));
seite('/logout.php', 302);
seite('/admin/', 200, 'Benutzerverwaltung');
pruefe('Abmelden per GET-Link wirkt nicht (nur POST)', true);
$antwort = anfrage('POST', '/logout.php', ['csrf_token' => token()]);
pruefe('Abmelden per POST', $antwort['code'] === 302 && ($antwort['ziel'] ?? '') === '/login.php');
seite('/admin/', 403);

// --- 8. Aufräumen: Demo-Daten zurücksetzen --------------------------------------
echo "\nAufräumen\n";
formular('/login.php', '/login.php', ['username' => 'finance', 'passwort' => 'demo1234']);
$antwort = formular('/finance/', '/demo-zuruecksetzen.php', []);
pruefe('Demo-Daten zurückgesetzt', $antwort['code'] === 302);
seite('/finance/', 200);
pruefe('Testbudget ist wieder weg', !str_contains($letzteSeite, 'Rundgang-Testbudget'));

@unlink($cookieDatei);

$gesamt = $ok + count($fehlgeschlagen);
echo "\n" . str_repeat('─', 50) . "\n";
echo count($fehlgeschlagen) === 0
    ? "Alle {$gesamt} Prüfungen bestanden.\n"
    : count($fehlgeschlagen) . " von {$gesamt} Prüfungen fehlgeschlagen:\n  - " . implode("\n  - ", $fehlgeschlagen) . "\n";

exit(count($fehlgeschlagen) === 0 ? 0 : 1);
