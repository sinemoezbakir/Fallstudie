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

// Status-Banner: dasselbe Prinzip wie bei Finance - eine Aussage statt Zahlen.
// Nutzt dieselbe, bereits bestehende aufmerksamkeitsListe() wie der Bereich
// weiter unten (ueberschritten/warnung/laeuft-bald-ab), damit beide
// Anzeigen immer konsistent sind.
$istRisiko = (bool) $aufmerksamkeit;
$anzahlAbgelaufen = $summe['anzahl']['abgelaufen'] ?? 0;
$dringendsterPunkt = null;
foreach (['ueberschritten', 'warnung', 'laeuft_ab'] as $art) {
    foreach ($aufmerksamkeit as $punkt) {
        if ($punkt['art'] === $art) {
            $dringendsterPunkt = $punkt;
            break 2;
        }
    }
}

$stunde = (int) date('G');
$gruss = $stunde < 11 ? 'Guten Morgen' : ($stunde < 18 ? 'Hallo' : 'Guten Abend');

$pageTitle = 'Meine Budgets';
require __DIR__ . '/../../templates/header.php';
?>

<div class="seitenkopf">
    <p class="etikett mb-2">Stand: <?= date('d.m.Y, H:i') ?> Uhr</p>
    <h1><?= e($gruss) ?>, <?= e($_SESSION['anzeigename']) ?></h1>
</div>

<?php if (!$eintraege): ?>
    <p class="lead mb-4">Dir sind noch keine Budgets zugewiesen.</p>
<?php else: ?>
    <div class="status-banner <?= $istRisiko ? 'status-banner--risiko' : 'status-banner--gut' ?> mb-4">
        <span class="status-banner__icon" aria-hidden="true"><?= $istRisiko ? '!' : '✓' ?></span>
        <div>
            <p class="status-banner__titel"><?= $istRisiko ? 'Risiko erkannt' : 'Keine aktiven Risiken' ?></p>
            <p class="status-banner__text">
                <?php if ($istRisiko): ?>
                    <strong><?= e($dringendsterPunkt['budget']['name']) ?></strong>:
                    <?= e($dringendsterPunkt['text']) ?>.
                    <?php if (count($aufmerksamkeit) > 1): ?>
                        Insgesamt <?= count($aufmerksamkeit) ?> Budgets brauchen deine Aufmerksamkeit.
                    <?php endif; ?>
                <?php else: ?>
                    Alle laufenden Budgets sind im grünen Bereich.
                <?php endif; ?>
                <?php if ($anzahlAbgelaufen > 0): ?>
                    <?= $anzahlAbgelaufen === 1 ? '1 Budget ist' : "{$anzahlAbgelaufen} Budgets sind" ?> bereits abgeschlossen (abgelaufen).
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<?php if ($eintraege): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kennzahl">
            <div class="etikett">Gesamtbudget</div>
            <div class="kennzahl__wert"><?= euro($summe['soll']) ?></div>
            <div class="kennzahl__info"><?= $summe['laufend'] === 1 ? '1 laufendes Budget' : $summe['laufend'] . ' laufende Budgets' ?></div>
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
        <?php // Nicht gruen, wenn die Hochrechnung schon ueber dem Budget liegt - sonst Widerspruch zur Karte daneben ?>
        <div class="kennzahl <?= $summe['verfuegbar'] < 0 ? 'kennzahl--negativ' : ($summe['hochrechnung'] > $summe['soll'] ? '' : 'kennzahl--betont') ?>">
            <div class="etikett">Verfügbares Budget</div>
            <div class="kennzahl__wert"><?= euro($summe['verfuegbar']) ?></div>
            <div class="kennzahl__info">über alle laufenden Budgets</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <?php $differenz = $summe['soll'] - $summe['hochrechnung']; ?>
        <div class="kennzahl <?= $differenz < 0 ? 'kennzahl--negativ' : '' ?>">
            <div class="etikett">
                Hochrechnung
                <button type="button" class="info-knopf" data-bs-toggle="popover" data-bs-trigger="focus"
                        data-bs-title="Was ist die Hochrechnung?"
                        data-bs-content="Ist-Ausgaben + bereits eingetragener Forecast für die restliche Laufzeit. Zeigt, wo du am Ende der Laufzeit stehen wirst, wenn alles wie geplant eintrifft."
                        aria-label="Was bedeutet Hochrechnung?">i</button>
            </div>
            <div class="kennzahl__wert"><?= euro($summe['hochrechnung']) ?></div>
            <div class="kennzahl__info <?= $differenz < 0 ? 'fw-semibold' : '' ?>">
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
                    <span class="fw-semibold <?= match ($punkt['art']) {
                        'ueberschritten' => 'text-ueberschritten',
                        default => 'text-warnung',
                    } ?>"><?= e($punkt['text']) ?></span>
                    <span class="ms-auto aufmerksamkeit__pfeil" aria-hidden="true">→</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<h2 class="karte-titel mb-3">Meine Budgets</h2>
<?php
    // Dringendste Budgets zuerst, nicht in Lade-Reihenfolge (Priorisierungsregel:
    // Handlungsbedarf vor Detaildaten) - "schlechtester Status" zuerst.
    $sortierteEintraege = $eintraege;
    usort($sortierteEintraege, fn ($a, $b) => STATUS_RANG[$a['k']['status']] <=> STATUS_RANG[$b['k']['status']]);
?>
<div class="row g-3">
    <?php foreach ($sortierteEintraege as ['budget' => $budget, 'k' => $k]): ?>
        <?php
            $abgelaufen = $k['status'] === 'abgelaufen';
            // Eine Aussage pro Karte: was ist los und was ist zu tun - aus
            // vorhandenem Status, statusGrund() und Restlaufzeit, ohne neue Berechnung.
            [$aussage, $aussageKlasse] = match (true) {
                $k['status'] === 'ueberschritten' => [statusGrund($budget, $k) . ' – mit Finance abstimmen', 'text-ueberschritten'],
                $k['status'] === 'warnung' => [statusGrund($budget, $k) . ' – Ausgaben prüfen', 'text-warnung'],
                $abgelaufen => ['Abgeschlossen – keine Buchungen mehr möglich', 'text-muted'],
                laeuftBaldAb($k['tage_bis_verfall']) => [ucfirst(restlaufzeitText($k['tage_bis_verfall'])) . ' – Restbudget einplanen', 'text-warnung'],
                default => ['Im Rahmen – kein Handlungsbedarf', 'text-im-rahmen'],
            };
        ?>
        <div class="col-md-6 col-xl-4">
            <article class="card budget-karte budget-karte--<?= e(str_replace('_', '-', $k['status'])) ?> h-100 card--klickbar <?= $abgelaufen ? 'budget-karte--abgelaufen' : '' ?>">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="bereich-chip bereich-chip--<?= e(strtolower($budget['bereich'])) ?>"><?= e(bereichLabel($budget['bereich'])) ?></span>
                    <?= statusBadge($k['status']) ?>
                </div>
                <h3 class="budget-karte__name">
                    <a class="stretched-link" href="/owner/budget.php?id=<?= (int) $budget['id'] ?>"><?= e($budget['name']) ?></a>
                </h3>
                <p class="small fw-semibold mb-3 <?= $aussageKlasse ?>"><?= e($aussage) ?></p>

                <div class="budget-karte__verfuegbar">
                    <span class="etikett"><?= $k['verfuegbar'] < 0 ? 'Überzogen' : ($abgelaufen ? 'Nicht ausgegeben' : 'Verfügbar') ?></span>
                    <span class="zahl-gross <?= $k['verfuegbar'] < 0 ? 'text-ueberschritten' : '' ?>"><?= euro(abs($k['verfuegbar'])) ?></span>
                </div>

                <?= verbrauchsBalken($k['verbrauch_prozent']) ?>
                <div class="d-flex justify-content-between small mt-2 tabular">
                    <span><?= euro($k['ist']) ?> von <?= euro((float) $budget['soll_betrag']) ?></span>
                    <span class="fw-semibold"><?= prozent($k['verbrauch_prozent']) ?></span>
                </div>

                <div class="budget-karte__fuss">
                    <span class="small <?= laeuftBaldAb($k['tage_bis_verfall']) ? 'text-warnung fw-semibold' : 'text-muted' ?>"><?= e(restlaufzeitText($k['tage_bis_verfall'])) ?></span>
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
