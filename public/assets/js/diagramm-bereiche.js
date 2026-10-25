/*
 * Finance-Übersicht: Soll, Ist und Hochrechnung je Bereich (Chart.js).
 * Daten als JSON aus public/finance/index.php.
 */
(() => {
    const quelle = document.getElementById('bereich-daten');
    const leinwand = document.getElementById('diagrammBereiche');
    if (!quelle || !leinwand || typeof Chart === 'undefined') {
        return;
    }

    const bereiche = JSON.parse(quelle.textContent);
    const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
    const euroKurz = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

    Chart.defaults.font.family = css('--sans') || 'system-ui';
    Chart.defaults.color = css('--muted');

    new Chart(leinwand, {
        type: 'bar',
        data: {
            labels: bereiche.map((b) => b.label),
            datasets: [
                { label: 'Soll', data: bereiche.map((b) => b.soll), backgroundColor: css('--line'), borderRadius: 6 },
                {
                    label: 'Ist',
                    data: bereiche.map((b) => b.ist),
                    // Ist rosé, wenn der Bereich insgesamt über dem Soll liegt
                    backgroundColor: bereiche.map((b) => (b.ist > b.soll ? css('--rose') : css('--sage-deep'))),
                    borderRadius: 6,
                },
                {
                    label: 'Hochrechnung',
                    data: bereiche.map((b) => b.hochrechnung),
                    backgroundColor: 'rgba(217, 164, 65, 0.25)',
                    borderColor: css('--ocker'),
                    borderWidth: 2,
                    borderDash: [5, 4],
                    borderRadius: 6,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                tooltip: {
                    backgroundColor: css('--ink'),
                    callbacks: { label: (kontext) => ` ${kontext.dataset.label}: ${euro.format(kontext.raw)}` },
                },
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: css('--line') }, ticks: { callback: (wert) => euroKurz.format(wert) } },
            },
        },
    });
})();
