/*
 * Live-Vorschau für Ausgabe erfassen / korrigieren (templates/betrag-vorschau.php).
 * Statusregeln wie in src/budget.php berechneStatus() (konzept.md Abschnitt 4).
 */
(() => {
    const box = document.getElementById('vorschau');
    const feld = document.getElementById('betrag');
    if (!box || !feld) {
        return;
    }

    const soll = parseFloat(box.dataset.soll);
    const istBasis = parseFloat(box.dataset.istBasis);
    const forecast = parseFloat(box.dataset.forecast);
    const euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
    const teil = (name) => box.querySelector(`[data-vorschau="${name}"]`);
    const statusText = { im_rahmen: 'Im Rahmen', warnung: 'Warnung', ueberschritten: 'Überschritten' };

    // "1.234,56" oder "1234.56" -> 1234.56 (wie leseBetrag() in PHP)
    const leseBetrag = (text) => {
        let wert = text.replace(/[\s€]/g, '');
        if (wert.includes(',')) {
            wert = wert.replace(/\./g, '').replace(',', '.');
        }
        return /^\d+(\.\d{1,2})?$/.test(wert) ? parseFloat(wert) : null;
    };

    const aktualisiere = () => {
        const betrag = leseBetrag(feld.value) ?? 0;
        const ist = istBasis + betrag;
        const verfuegbar = soll - ist;
        const prozent = soll > 0 ? (ist / soll) * 100 : 0;
        const hochrechnung = ist + forecast;

        let status = 'im_rahmen';
        if (ist > soll) {
            status = 'ueberschritten';
        } else if (prozent >= 80 || hochrechnung > soll) {
            status = 'warnung';
        }

        teil('betrag').textContent = betrag > 0 ? `− ${euro.format(betrag)}` : '–';
        teil('verfuegbar').textContent = euro.format(verfuegbar);
        teil('verfuegbar').classList.toggle('text-ueberschritten', verfuegbar < 0);
        teil('prozent').textContent = `${prozent.toLocaleString('de-DE', { maximumFractionDigits: 1 })} % verbraucht`;

        const balken = teil('balken');
        balken.style.width = `${Math.min(prozent, 100)}%`;
        balken.className = `verbrauch-balken__fuellung ${status === 'im_rahmen' ? '' : status}`;

        teil('status').innerHTML = `<span class="status-badge ${status.replace('_', '-')}">${statusText[status]}</span>`;

        const hinweis = teil('hinweis');
        hinweis.hidden = status === 'im_rahmen';
        hinweis.className = `vorschau__hinweis small mb-0 mt-3 vorschau__hinweis--${status}`;
        if (status === 'ueberschritten') {
            hinweis.textContent = `Achtung: Damit wird das Budget um ${euro.format(-verfuegbar)} überschritten. Speichern ist möglich – bitte mit Finance abstimmen.`;
        } else if (status === 'warnung') {
            hinweis.textContent = prozent >= 80
                ? 'Danach sind mehr als 80 % des Budgets verbraucht.'
                : `Mit dem geplanten Forecast (${euro.format(forecast)}) würde das Budget überschritten.`;
        }
    };

    feld.addEventListener('input', aktualisiere);
    aktualisiere();
})();
