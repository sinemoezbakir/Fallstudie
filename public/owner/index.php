<?php
/**
 * Owner-Dashboard: Status aller eigenen Budgets (MB-040).
 *
 * Oben die Summen über alle laufenden Budgets, dann was Aufmerksamkeit
 * braucht (überschritten, Warnung, läuft bald ab), darunter je Budget
 * eine Karte mit Schnellzugriff auf "Ausgabe erfassen".
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_OWNER);

$benutzerId = eingeloggteBenutzerId();
$eintraege = budgetsMitKennzahlen(ladeBudgetsVonOwner($benutzerId));
$summe = zusammenfassung($eintraege);
$aufmerksamkeit = aufmerksamkeitsListe($eintraege);

$stunde = (int) date('G');
$gruss = $stunde < 11 ? 'Guten Morgen' : ($stunde < 18 ? 'Hallo' : 'Guten Abend');

$pageTitle = 'Meine Budgets';
require __DIR__ . '/../../templates/header.php';
?>

<div class="seitenkopf">
    <p class="etikett mb-2"><?= e(datumDe(date('Y-m-d'))) ?> · live aus der Datenbank</p>
    <h1><?= e($gruss) ?>, <?= e($_SESSION['anzeigename']) ?></h1>
    <p class="lead">
        <?php if (!$eintraege): ?>
            Dir sind noch keine Budgets zugewiesen.
        <?php elseif (!$aufmerksamkeit): ?>
            Alle deine Budgets sind im grünen Bereich.
        <?php else: ?>
            <?= count($aufmerksamkeit) === 1 ? 'Ein Budget braucht' : count($aufmerksamkeit) . ' Budgets brauchen' ?> deine Aufmerksamkeit.
        <?php endif; ?>
    </p>
</div>

<?php if ($eintraege): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Budget gesamt</div>
            <div class="kennzahl__wert"><?= euro($summe['soll']) ?></div>
            <div class="kennzahl__info"><?= $summe['laufend'] ?> laufende Budgets</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Ausgegeben</div>
            <div class="kennzahl__wert"><?= euro($summe['ist']) ?></div>
            <div class="kennzahl__info"><?= prozent($summe['verbrauch_prozent']) ?> vom Budget</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kennzahl <?= $summe['verfuegbar'] < 0 ? 'kennzahl--negativ' : 'kennzahl--betont' ?>">
            <div class="etikett">Noch verfügbar</div>
            <div class="kennzahl__wert"><?= euro($summe['verfuegbar']) ?></div>
            <div class="kennzahl__info">über alle laufenden Budgets</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <?php $differenz = $summe['soll'] - $summe['hochrechnung']; ?>
        <div class="kennzahl <?= $differenz < 0 ? 'kennzahl--ueberschritten' : '' ?>">
            <div class="etikett">Hochrechnung</div>
            <div class="kennzahl__wert"><?= euro($summe['hochrechnung']) ?></div>
            <div class="kennzahl__info <?= $differenz < 0 ? 'text-ueberschritten fw-semibold' : '' ?>">
                <?= $differenz < 0 ? euro(-$differenz) . ' über dem Budget' : euro($differenz) . ' Puffer bis Laufzeitende' ?>
            </div>
        </div>
    </div>
</div>

<?php if ($aufmerksamkeit): ?>
<section class="aufmerksamkeit mb-4" aria-labelledby="aufmerksamkeit-titel">
    <h2 id="aufmerksamkeit-titel" class="karte-titel mb-2">Braucht Aufmerksamkeit</h2>
    <ul class="list-unstyled mb-0">
        <?php foreach ($aufmerksamkeit as $punkt): ?>
            <li>
                <a class="aufmerksamkeit__zeile" href="/owner/budget.php?id=<?= (int) $punkt['budget']['id'] ?>">
                    <span class="aufmerksamkeit__punkt aufmerksamkeit__punkt--<?= e($punkt['art']) ?>"></span>
                    <span class="fw-semibold"><?= e($punkt['budget']['name']) ?></span>
                    <span class="text-muted"><?= e($punkt['text']) ?></span>
                    <span class="ms-auto aufmerksamkeit__pfeil" aria-hidden="true">→</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<h2 class="karte-titel mb-3">Meine Budgets</h2>
<div class="row g-3">
    <?php foreach ($eintraege as ['budget' => $budget, 'k' => $k]): ?>
        <?php $abgelaufen = $k['status'] === 'abgelaufen'; ?>
        <div class="col-md-6 col-xl-4">
            <article class="card budget-karte h-100 card--klickbar <?= $abgelaufen ? 'budget-karte--abgelaufen' : '' ?>">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="bereich-chip bereich-chip--<?= e(strtolower($budget['bereich'])) ?>"><?= e(bereichLabel($budget['bereich'])) ?></span>
                    <?= statusBadge($k['status']) ?>
                </div>
                <h3 class="budget-karte__name">
                    <a class="stretched-link" href="/owner/budget.php?id=<?= (int) $budget['id'] ?>"><?= e($budget['name']) ?></a>
                </h3>

                <div class="budget-karte__verfuegbar">
                    <span class="etikett">Verfügbar</span>
                    <span class="zahl-gross <?= $k['verfuegbar'] < 0 ? 'text-ueberschritten' : '' ?>"><?= euro($k['verfuegbar']) ?></span>
                </div>

                <?= verbrauchsBalken($k['verbrauch_prozent']) ?>
                <div class="d-flex justify-content-between small mt-2 tabular">
                    <span><?= euro($k['ist']) ?> von <?= euro((float) $budget['soll_betrag']) ?></span>
                    <span class="fw-semibold"><?= prozent($k['verbrauch_prozent']) ?></span>
                </div>

                <div class="budget-karte__fuss">
                    <span class="small text-muted"><?= e(restlaufzeitText($k['tage_bis_verfall'])) ?></span>
                    <?php if (!$abgelaufen): ?>
                        <a href="/owner/ist-erfassen.php?budget_id=<?= (int) $budget['id'] ?>"
                           class="btn btn-sm btn-maliá position-relative" style="z-index: 2;">+ Ausgabe</a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="leer-zustand">
        <p class="mb-1 fw-semibold">Noch keine Budgets</p>
        <p class="text-muted mb-0">Sobald Finance dir ein Budget zuweist, erscheint es hier.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
