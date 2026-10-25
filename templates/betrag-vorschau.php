<?php
/**
 * Live-Vorschau neben dem Betragsfeld (Ausgabe erfassen / korrigieren):
 * zeigt sofort beim Tippen, was nach dem Speichern verfügbar ist und
 * welcher Status sich ergibt. Rechnet im Browser (assets/js/vorschau.js),
 * gespeichert und endgültig berechnet wird wie immer auf dem Server.
 *
 * Erwartet: $budget, $k (Kennzahlen), $istBasis (Ist ohne die Buchung,
 * die gerade erfasst/korrigiert wird), $vorschauTitel
 */
?>
<aside class="card p-4 vorschau" id="vorschau"
       data-soll="<?= e((string) (float) $budget['soll_betrag']) ?>"
       data-ist-basis="<?= e((string) $istBasis) ?>"
       data-forecast="<?= e((string) $k['offener_forecast']) ?>"
       aria-live="polite">
    <h2 class="karte-titel mb-3"><?= e($vorschauTitel) ?></h2>
    <dl class="vorschau__liste tabular">
        <div><dt>Budget (Soll)</dt><dd><?= euro((float) $budget['soll_betrag']) ?></dd></div>
        <div><dt>Bisher ausgegeben</dt><dd><?= euro($istBasis) ?></dd></div>
        <div><dt>Diese Ausgabe</dt><dd data-vorschau="betrag">–</dd></div>
        <div class="vorschau__summe"><dt>Danach verfügbar</dt><dd data-vorschau="verfuegbar"><?= euro((float) $budget['soll_betrag'] - $istBasis) ?></dd></div>
    </dl>
    <div class="verbrauch-balken mt-3"><div class="verbrauch-balken__fuellung" data-vorschau="balken" style="width: 0%"></div></div>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <span class="small text-muted" data-vorschau="prozent"></span>
        <span data-vorschau="status"></span>
    </div>
    <p class="vorschau__hinweis small mb-0 mt-3" data-vorschau="hinweis" hidden></p>
</aside>
<script src="/assets/js/vorschau.js" defer></script>
