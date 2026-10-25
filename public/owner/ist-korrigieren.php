<?php
/**
 * Owner: eine bestehende Ist-Buchung korrigieren (MB-035).
 *
 * Der neue Betrag überschreibt den alten, aber der ursprüngliche Betrag,
 * das Korrekturdatum und der Grund werden zusätzlich gespeichert
 * (konzept.md Abschnitt 3, Entscheidung E-006). Der ursprüngliche Betrag
 * wird nur beim ERSTEN Korrigieren gesetzt - eine zweite Korrektur
 * überschreibt ihn nicht nochmal, sonst ginge der wirklich ursprüngliche
 * Wert verloren.
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_OWNER);

$benutzerId = eingeloggteBenutzerId();

// Prüft gleichzeitig, dass die Buchung zu einem Budget dieses Owners gehört.
$buchung = ladeEigeneBuchung((int) ($_GET['id'] ?? $_POST['id'] ?? 0), $benutzerId);
$budget = ladeEigenesBudget((int) $buchung['budget_id'], $benutzerId);

$istAbgelaufen = istBudgetAbgelaufen($budget);
$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$istAbgelaufen) {
    $pruefung = pruefeKorrektur($_POST, $buchung);
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        // Ursprünglichen Betrag nur beim ersten Mal festhalten.
        $urspruenglicherBetrag = $buchung['urspruenglicher_betrag'] ?? $buchung['betrag'];

        $stmt = $pdo->prepare(
            'UPDATE ist_buchung
             SET betrag = :neuer_betrag,
                 urspruenglicher_betrag = :urspruenglicher_betrag,
                 korrigiert_am = NOW(),
                 korrektur_grund = :grund
             WHERE id = :id'
        );
        $stmt->execute([
            'neuer_betrag' => $pruefung['werte']['betrag'],
            'urspruenglicher_betrag' => $urspruenglicherBetrag,
            'grund' => $pruefung['werte']['grund'],
            'id' => $buchung['id'],
        ]);

        setzeFlash('erfolg', 'Buchung korrigiert: ' . euro((float) $buchung['betrag']) . ' → ' . euro($pruefung['werte']['betrag']) . '.');
        header('Location: /owner/budget.php?id=' . $budget['id']);
        exit;
    }
}

$k = kennzahlenFuerBudget($budget);
$istBasis = round($k['ist'] - (float) $buchung['betrag'], 2); // Ist ohne diese Buchung
$vorschauTitel = 'So steht das Budget nach der Korrektur';

$pageTitle = 'Buchung korrigieren';
require __DIR__ . '/../../templates/header.php';
?>

<a href="/owner/budget.php?id=<?= (int) $budget['id'] ?>" class="zurueck-link">← <?= e($budget['name']) ?></a>
<div class="seitenkopf">
    <h1>Buchung korrigieren</h1>
    <p class="lead">Der alte Betrag und der Grund bleiben nachvollziehbar gespeichert.</p>
</div>

<?php if ($istAbgelaufen): ?>
    <div class="alert alert-warning">
        <strong>Dieses Budget ist am <?= e(datumDe($budget['gueltig_bis'])) ?> abgelaufen.</strong>
        Buchungen können nach der Verfallszeit nicht mehr korrigiert werden.
    </div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-4">
            <div class="buchung-info mb-4">
                <div class="etikett mb-1">Aktuelle Buchung</div>
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold"><?= e($buchung['beschreibung']) ?></div>
                        <div class="small text-muted"><?= e(datumDe($buchung['datum'])) ?></div>
                    </div>
                    <div class="zahl-gross fs-3 tabular"><?= euro((float) $buchung['betrag']) ?></div>
                </div>
                <?php if ($buchung['urspruenglicher_betrag'] !== null): ?>
                    <div class="korrektur-hinweis mt-2">
                        Bereits korrigiert am <?= e(datumDe($buchung['korrigiert_am'])) ?> –
                        ursprünglich <?= euro((float) $buchung['urspruenglicher_betrag']) ?>, Grund: „<?= e($buchung['korrektur_grund']) ?>“
                    </div>
                <?php endif; ?>
            </div>

            <?= fehlerHinweis($fehler) ?>
            <form method="post" action="/owner/ist-korrigieren.php" novalidate>
                <?= csrfFeld() ?>
                <input type="hidden" name="id" value="<?= (int) $buchung['id'] ?>">
                <div class="mb-3">
                    <label for="betrag" class="form-label">Richtiger Betrag</label>
                    <div class="input-group input-group-lg">
                        <input type="text" inputmode="decimal" class="form-control<?= feldKlasse($fehler, 'betrag') ?>"
                               id="betrag" name="betrag" autocomplete="off"
                               value="<?= alterWert('betrag', number_format((float) $buchung['betrag'], 2, ',', '')) ?>"
                               <?= feldAria($fehler, 'betrag') ?> required autofocus>
                        <span class="input-group-text">€</span>
                        <?= feldFehler($fehler, 'betrag') ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="grund" class="form-label">Grund der Korrektur</label>
                    <input type="text" class="form-control<?= feldKlasse($fehler, 'grund') ?>" id="grund" name="grund"
                           maxlength="255" placeholder="z. B. Gutschrift erhalten, Tippfehler, Rabatt nachträglich"
                           value="<?= alterWert('grund') ?>" <?= feldAria($fehler, 'grund') ?> required>
                    <?= feldFehler($fehler, 'grund') ?>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-maliá">Korrektur speichern</button>
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
