<?php
/**
 * Owner: Ist-Wert für ein eigenes Budget erfassen (MB-034).
 * Gesperrt nach Ablauf der Verfallszeit (MB-045).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_OWNER);

$benutzerId = eingeloggteBenutzerId();

// ladeEigenesBudget() bricht selbst mit 403 ab, falls das Budget nicht
// existiert oder jemand anderem gehört (Risiko R3).
$budget = ladeEigenesBudget((int) ($_GET['budget_id'] ?? $_POST['budget_id'] ?? 0), $benutzerId);
$istAbgelaufen = istBudgetAbgelaufen($budget);
$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$istAbgelaufen) {
    $pruefung = pruefeAusgabe($_POST, $budget);
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        $werte = $pruefung['werte'];
        $stmt = $pdo->prepare(
            'INSERT INTO ist_buchung (budget_id, betrag, datum, beschreibung, erfasst_von)
             VALUES (:budget_id, :betrag, :datum, :beschreibung, :erfasst_von)'
        );
        $stmt->execute([
            'budget_id' => $budget['id'],
            'betrag' => $werte['betrag'],
            'datum' => $werte['datum'],
            'beschreibung' => $werte['beschreibung'],
            'erfasst_von' => $benutzerId,
        ]);

        $danach = kennzahlenFuerBudget($budget);
        if ($danach['status'] === 'ueberschritten') {
            setzeFlash('hinweis', 'Ausgabe über ' . euro($werte['betrag']) . ' erfasst. Achtung: Das Budget ist jetzt um '
                . euro(-$danach['verfuegbar']) . ' überschritten.');
        } else {
            setzeFlash('erfolg', 'Ausgabe über ' . euro($werte['betrag']) . ' erfasst – noch ' . euro($danach['verfuegbar']) . ' verfügbar.');
        }

        // "Speichern & weitere erfassen" bleibt auf dem Formular
        $ziel = isset($_POST['weitere'])
            ? '/owner/ist-erfassen.php?budget_id=' . $budget['id']
            : '/owner/budget.php?id=' . $budget['id'];
        header('Location: ' . $ziel);
        exit;
    }
}

$k = kennzahlenFuerBudget($budget);
$istBasis = $k['ist'];
$vorschauTitel = 'So steht das Budget danach';

// Vorschläge für die Beschreibung aus früheren Ausgaben dieses Budgets
$vorschlaege = $pdo->prepare('SELECT DISTINCT beschreibung FROM ist_buchung WHERE budget_id = :id ORDER BY beschreibung');
$vorschlaege->execute(['id' => $budget['id']]);
$vorschlaege = $vorschlaege->fetchAll(PDO::FETCH_COLUMN);

$heute = date('Y-m-d');
$maxDatum = min($heute, $budget['gueltig_bis']);

$pageTitle = 'Ausgabe erfassen';
require __DIR__ . '/../../templates/header.php';
?>

<a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="zurueck-link">← <?= e($budget['name']) ?></a>
<div class="seitenkopf">
    <h1>Ausgabe erfassen</h1>
    <p class="lead"><?= e($budget['name']) ?> · noch <?= euro($k['verfuegbar']) ?> verfügbar</p>
</div>

<?php if ($istAbgelaufen): ?>
    <div class="alert alert-warning">
        <strong>Dieses Budget ist am <?= e(datumDe($budget['gueltig_bis'])) ?> abgelaufen.</strong>
        Nach der Verfallszeit können keine Ausgaben mehr erfasst werden.
    </div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-4">
            <?= fehlerHinweis($fehler) ?>
            <form method="post" action="/owner/ist-erfassen.php" novalidate>
                <?= csrfFeld() ?>
                <input type="hidden" name="budget_id" value="<?= (int) $budget['id'] ?>">

                <div class="mb-3">
                    <label for="betrag" class="form-label">Betrag</label>
                    <div class="input-group input-group-lg">
                        <input type="text" inputmode="decimal" class="form-control<?= feldKlasse($fehler, 'betrag') ?>"
                               id="betrag" name="betrag" placeholder="0,00" autocomplete="off"
                               value="<?= alterWert('betrag') ?>" <?= feldAria($fehler, 'betrag') ?> required autofocus>
                        <span class="input-group-text">€</span>
                        <?= feldFehler($fehler, 'betrag') ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="beschreibung" class="form-label">Wofür?</label>
                    <input type="text" class="form-control<?= feldKlasse($fehler, 'beschreibung') ?>" id="beschreibung"
                           name="beschreibung" maxlength="255" list="beschreibung-vorschlaege"
                           placeholder="z. B. Rechnung Lieferant, Material, Wartung …"
                           value="<?= alterWert('beschreibung') ?>" <?= feldAria($fehler, 'beschreibung') ?> required>
                    <datalist id="beschreibung-vorschlaege">
                        <?php foreach ($vorschlaege as $vorschlag): ?>
                            <option value="<?= e($vorschlag) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <?= feldFehler($fehler, 'beschreibung') ?>
                </div>

                <div class="mb-4">
                    <label for="datum" class="form-label">Datum der Ausgabe</label>
                    <input type="date" class="form-control<?= feldKlasse($fehler, 'datum') ?>" id="datum" name="datum"
                           min="<?= e($budget['gueltig_von']) ?>" max="<?= e($maxDatum) ?>"
                           value="<?= alterWert('datum', $maxDatum) ?>" <?= feldAria($fehler, 'datum') ?> required>
                    <?= feldFehler($fehler, 'datum') ?>
                    <div class="form-text">Zwischen <?= e(datumDe($budget['gueltig_von'])) ?> und <?= e(datumDe($maxDatum)) ?>.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-maliá">Ausgabe speichern</button>
                    <button type="submit" name="weitere" value="1" class="btn btn-maliá-outline">Speichern &amp; weitere erfassen</button>
                    <a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="btn btn-link text-muted">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <?php require __DIR__ . '/../../templates/betrag-vorschau.php'; ?>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
