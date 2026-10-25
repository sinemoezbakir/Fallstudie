<?php
/**
 * Owner: Forecast pro Monat eintragen und anzeigen (MB-042).
 *
 * Alle offenen Monate (aktueller Monat bis Budgetende) als Raster in einem
 * Formular. Pro (budget_id, monat) existiert genau ein Eintrag
 * (UNIQUE-Constraint) - leeres Feld oder 0 löscht den Eintrag.
 * Nach Ablauf der Verfallszeit gesperrt (MB-045).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_OWNER);

$budget = ladeEigenesBudget((int) ($_GET['budget_id'] ?? $_POST['budget_id'] ?? 0), eingeloggteBenutzerId());
$istAbgelaufen = istBudgetAbgelaufen($budget);

// Offene Monate: ab dem aktuellen Monat (bzw. Budgetbeginn) bis Budgetende
$monate = [];
$start = max(date('Y-m-01'), substr($budget['gueltig_von'], 0, 7) . '-01');
for ($monat = new DateTimeImmutable($start); $monat->format('Y-m-d') <= $budget['gueltig_bis']; $monat = $monat->modify('+1 month')) {
    $monate[] = $monat->format('Y-m-d');
}

$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$istAbgelaufen) {
    $pruefung = pruefeForecast($_POST['forecast'] ?? [], $monate);
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        $speichern = $pdo->prepare(
            'INSERT INTO forecast (budget_id, monat, betrag) VALUES (:budget_id, :monat, :betrag)
             ON DUPLICATE KEY UPDATE betrag = VALUES(betrag)'
        );
        $loeschen = $pdo->prepare('DELETE FROM forecast WHERE budget_id = :budget_id AND monat = :monat');

        $pdo->beginTransaction();
        foreach ($pruefung['werte'] as $monat => $betrag) {
            if ($betrag === null) {
                $loeschen->execute(['budget_id' => $budget['id'], 'monat' => $monat]);
            } else {
                $speichern->execute(['budget_id' => $budget['id'], 'monat' => $monat, 'betrag' => $betrag]);
            }
        }
        $pdo->commit();

        $k = kennzahlenFuerBudget($budget);
        $puffer = (float) $budget['soll_betrag'] - $k['hochrechnung'];
        setzeFlash(
            $puffer < 0 ? 'hinweis' : 'erfolg',
            'Forecast gespeichert. Hochrechnung: ' . euro($k['hochrechnung'])
                . ($puffer < 0 ? ' – ' . euro(-$puffer) . ' über dem Soll.' : ' – ' . euro($puffer) . ' Puffer.')
        );
        header('Location: /owner/budget.php?id=' . $budget['id']);
        exit;
    }
}

$k = kennzahlenFuerBudget($budget);
$forecasts = ladeForecasts((int) $budget['id']);

// Ist je Monat, damit man beim aktuellen Monat sieht, was schon gebucht ist
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(datum, '%Y-%m-01'), SUM(betrag) FROM ist_buchung WHERE budget_id = :id GROUP BY 1"
);
$stmt->execute(['id' => $budget['id']]);
$istJeMonat = array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));

$pageTitle = 'Forecast planen';
require __DIR__ . '/../../templates/header.php';
?>

<a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="zurueck-link">← <?= e($budget['name']) ?></a>
<div class="seitenkopf">
    <h1>Forecast planen</h1>
    <p class="lead">Was wird in den kommenden Monaten voraussichtlich noch ausgegeben? Daraus entsteht die Hochrechnung.</p>
</div>

<?php if ($istAbgelaufen || !$monate): ?>
    <div class="alert alert-warning">
        <strong>Dieses Budget ist am <?= e(datumDe($budget['gueltig_bis'])) ?> abgelaufen.</strong>
        Für abgelaufene Budgets kann kein Forecast mehr geplant werden.
    </div>
<?php else: ?>
<form method="post" action="/owner/forecast.php" novalidate>
    <?= csrfFeld() ?>
    <input type="hidden" name="budget_id" value="<?= (int) $budget['id'] ?>">

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4">
                <?= fehlerHinweis($fehler) ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="karte-titel mb-0">Offene Monate</h2>
                    <button type="button" class="btn btn-sm btn-maliá-outline" id="rest-verteilen"
                            title="Verteilt das noch verfügbare Budget gleichmäßig auf alle offenen Monate">
                        Rest gleichmäßig verteilen
                    </button>
                </div>

                <div class="forecast-raster">
                    <?php foreach ($monate as $monat): ?>
                        <?php
                        $feld = "forecast-{$monat}";
                        $wert = $_POST['forecast'][$monat] ?? (isset($forecasts[$monat]) ? number_format($forecasts[$monat], 2, ',', '') : '');
                        ?>
                        <div class="forecast-raster__zeile">
                            <label for="<?= e($feld) ?>" class="forecast-raster__monat">
                                <?= e(monatDe($monat)) ?>
                                <?php if ($monat === date('Y-m-01')): ?>
                                    <span class="d-block small text-muted">aktueller Monat · bisher <?= euro($istJeMonat[$monat] ?? 0) ?> gebucht</span>
                                <?php endif; ?>
                            </label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" class="form-control text-end forecast-feld<?= feldKlasse($fehler, $feld) ?>"
                                       id="<?= e($feld) ?>" name="forecast[<?= e($monat) ?>]" placeholder="0,00"
                                       value="<?= e(is_string($wert) ? $wert : '') ?>" <?= feldAria($fehler, $feld) ?>>
                                <span class="input-group-text">€</span>
                                <?= feldFehler($fehler, $feld) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="form-text mb-0 mt-3">Leeres Feld = nichts geplant. Bereits gebuchte Ausgaben zählen automatisch zum Ist.</p>
            </div>
        </div>

        <div class="col-lg-5">
            <aside class="card p-4 vorschau" id="forecast-vorschau"
                   data-soll="<?= e((string) (float) $budget['soll_betrag']) ?>" data-ist="<?= e((string) $k['ist']) ?>" aria-live="polite">
                <h2 class="karte-titel mb-3">Hochrechnung</h2>
                <dl class="vorschau__liste tabular">
                    <div><dt>Budget (Soll)</dt><dd><?= euro((float) $budget['soll_betrag']) ?></dd></div>
                    <div><dt>Bereits ausgegeben (Ist)</dt><dd><?= euro($k['ist']) ?></dd></div>
                    <div><dt>Geplanter Forecast</dt><dd data-fc="summe">–</dd></div>
                    <div class="vorschau__summe"><dt>Hochrechnung</dt><dd data-fc="hochrechnung">–</dd></div>
                </dl>
                <div class="verbrauch-balken mt-3"><div class="verbrauch-balken__fuellung" data-fc="balken" style="width: 0%"></div></div>
                <p class="small mt-2 mb-0" data-fc="fazit"></p>
                <button type="submit" class="btn btn-maliá w-100 mt-4">Forecast speichern</button>
            </aside>
        </div>
    </div>
</form>
<script>
(() => {
    const box = document.getElementById('forecast-vorschau');
    const felder = [...document.querySelectorAll('.forecast-feld')];
    const soll = parseFloat(box.dataset.soll);
    const ist = parseFloat(box.dataset.ist);
    const euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
    const lese = (text) => {
        let wert = text.replace(/[\s€]/g, '');
        if (wert.includes(',')) wert = wert.replace(/\./g, '').replace(',', '.');
        return /^\d+(\.\d{1,2})?$/.test(wert) ? parseFloat(wert) : 0;
    };
    const teil = (name) => box.querySelector(`[data-fc="${name}"]`);

    const aktualisiere = () => {
        const summe = felder.reduce((gesamt, feld) => gesamt + lese(feld.value), 0);
        const hochrechnung = ist + summe;
        const puffer = soll - hochrechnung;
        const prozent = soll > 0 ? hochrechnung / soll * 100 : 0;

        teil('summe').textContent = euro.format(summe);
        teil('hochrechnung').textContent = euro.format(hochrechnung);
        teil('hochrechnung').classList.toggle('text-ueberschritten', puffer < 0);
        teil('balken').style.width = `${Math.min(prozent, 100)}%`;
        teil('balken').className = 'verbrauch-balken__fuellung ' + (puffer < 0 ? 'ueberschritten' : (prozent >= 80 ? 'warnung' : ''));
        teil('fazit').innerHTML = puffer < 0
            ? `<span class="text-ueberschritten fw-semibold">${euro.format(-puffer)} über dem Soll</span> – das Budget wird auf <strong>Warnung</strong> gesetzt.`
            : `<span class="text-im-rahmen fw-semibold">${euro.format(puffer)} Puffer</span> bis zum Budgetende.`;
    };

    document.getElementById('rest-verteilen').addEventListener('click', () => {
        const rest = Math.max(soll - ist, 0);
        const jeMonat = Math.floor(rest / felder.length * 100) / 100;
        felder.forEach((feld, index) => {
            // Rundungsrest landet im letzten Monat, damit die Summe genau stimmt
            const wert = index === felder.length - 1 ? rest - jeMonat * (felder.length - 1) : jeMonat;
            feld.value = wert.toFixed(2).replace('.', ',');
        });
        aktualisiere();
    });

    felder.forEach((feld) => feld.addEventListener('input', aktualisiere));
    aktualisiere();
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
