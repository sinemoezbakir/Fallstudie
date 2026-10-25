<?php
/**
 * Formular "Budget anlegen" (MB-031) und "Budget bearbeiten" - gleiche
 * Felder, gleiche Prüfung (pruefeBudget()).
 *
 * Erwartet: $owners, $fehler, $werte (vorbelegte Werte), $aktion (Formular-URL),
 * $knopfText, optional $budgetId und $istBisher (beim Bearbeiten)
 */

$wert = fn (string $feld): string => alterWert($feld, (string) ($werte[$feld] ?? ''));
$gewaehlt = fn (string $feld, string $option): string => (($_POST[$feld] ?? (string) ($werte[$feld] ?? '')) === $option) ? 'selected' : '';
?>
<div class="card p-4" style="max-width: 720px;">
    <?= fehlerHinweis($fehler) ?>
    <form method="post" action="<?= e($aktion) ?>" novalidate>
        <?= csrfFeld() ?>
        <?php if (!empty($budgetId)): ?>
            <input type="hidden" name="id" value="<?= (int) $budgetId ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label for="name" class="form-label">Name des Budgets</label>
            <input type="text" class="form-control<?= feldKlasse($fehler, 'name') ?>" id="name" name="name" maxlength="150"
                   placeholder="z. B. Reformer-Wartung 2027" value="<?= $wert('name') ?>" <?= feldAria($fehler, 'name') ?> required autofocus>
            <?= feldFehler($fehler, 'name') ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="bereich" class="form-label">Bereich</label>
                <select class="form-select<?= feldKlasse($fehler, 'bereich') ?>" id="bereich" name="bereich" <?= feldAria($fehler, 'bereich') ?> required>
                    <option value="">Bitte wählen …</option>
                    <?php foreach (BEREICHE as $schluessel => $label): ?>
                        <option value="<?= e($schluessel) ?>" <?= $gewaehlt('bereich', $schluessel) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= feldFehler($fehler, 'bereich') ?>
            </div>
            <div class="col-md-6">
                <label for="owner_id" class="form-label">Verantwortlich (Owner)</label>
                <select class="form-select<?= feldKlasse($fehler, 'owner_id') ?>" id="owner_id" name="owner_id" <?= feldAria($fehler, 'owner_id') ?> required>
                    <option value="">Bitte wählen …</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= (int) $owner['id'] ?>" <?= $gewaehlt('owner_id', (string) $owner['id']) ?>><?= e($owner['anzeigename']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= feldFehler($fehler, 'owner_id') ?>
            </div>
        </div>

        <div class="mb-3">
            <label for="soll_betrag" class="form-label">Soll-Betrag</label>
            <div class="input-group">
                <input type="text" inputmode="decimal" class="form-control<?= feldKlasse($fehler, 'soll_betrag') ?>" id="soll_betrag"
                       name="soll_betrag" placeholder="0,00" value="<?= $wert('soll_betrag') ?>" <?= feldAria($fehler, 'soll_betrag') ?> required>
                <span class="input-group-text">€</span>
                <?= feldFehler($fehler, 'soll_betrag') ?>
            </div>
            <?php if (isset($istBisher)): ?>
                <div class="form-text">Bisher ausgegeben: <?= euro($istBisher) ?>. Liegt das neue Soll darunter, gilt das Budget sofort als überschritten.</div>
            <?php endif; ?>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label for="gueltig_von" class="form-label">Gültig von</label>
                <input type="date" class="form-control<?= feldKlasse($fehler, 'gueltig_von') ?>" id="gueltig_von" name="gueltig_von"
                       value="<?= $wert('gueltig_von') ?>" <?= feldAria($fehler, 'gueltig_von') ?> required>
                <?= feldFehler($fehler, 'gueltig_von') ?>
            </div>
            <div class="col-md-6">
                <label for="gueltig_bis" class="form-label">Gültig bis (Verfallszeit)</label>
                <input type="date" class="form-control<?= feldKlasse($fehler, 'gueltig_bis') ?>" id="gueltig_bis" name="gueltig_bis"
                       value="<?= $wert('gueltig_bis') ?>" <?= feldAria($fehler, 'gueltig_bis') ?> required>
                <?= feldFehler($fehler, 'gueltig_bis') ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Zeitraum schnell wählen">
            <span class="small text-muted align-self-center me-1">Schnellwahl:</span>
            <?php $jahr = (int) date('Y'); ?>
            <button type="button" class="btn btn-sm btn-maliá-outline" data-zeitraum="<?= $jahr ?>-01-01|<?= $jahr ?>-12-31">Jahr <?= $jahr ?></button>
            <button type="button" class="btn btn-sm btn-maliá-outline" data-zeitraum="<?= $jahr + 1 ?>-01-01|<?= $jahr + 1 ?>-12-31">Jahr <?= $jahr + 1 ?></button>
            <?php $quartal = intdiv((int) date('n') - 1, 3); $qStart = sprintf('%d-%02d-01', $jahr, $quartal * 3 + 1); ?>
            <button type="button" class="btn btn-sm btn-maliá-outline" data-zeitraum="<?= $qStart ?>|<?= date('Y-m-t', strtotime($qStart . ' +2 months')) ?>">Q<?= $quartal + 1 ?> <?= $jahr ?></button>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-maliá"><?= e($knopfText) ?></button>
            <a href="<?= e($abbrechenLink ?? '/finance/') ?>" class="btn btn-link text-muted">Abbrechen</a>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('[data-zeitraum]').forEach((knopf) => knopf.addEventListener('click', () => {
    const [von, bis] = knopf.dataset.zeitraum.split('|');
    document.getElementById('gueltig_von').value = von;
    document.getElementById('gueltig_bis').value = bis;
}));
</script>
