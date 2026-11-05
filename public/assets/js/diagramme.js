/*
 * Diagramme der Budget-Detailseite (MB-043, MB-044) mit Chart.js.
 * Die Daten kommen als JSON aus templates/budget-detail.php.
 */
(() => {
    const quelle = document.getElementById('diagramm-daten');
    if (!quelle || typeof Chart === 'undefined') {
        return;
    }

    const daten = JSON.parse(quelle.textContent);
    const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const farbe = {
        sage: css('--sage'),
        sageDeep: css('--sage-deep'),
        rose: css('--rose'),
        ocker: css('--ocker'),
        muted: css('--muted'),
        line: css('--line'),
        cream: css('--cream'),
        ink: css('--ink'),
    };
    const euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
    const euroKurz = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });

    Chart.defaults.font.family = css('--sans') || 'system-ui';
    Chart.defaults.color = farbe.muted;

    // --- MB-044: Verlauf Ist / Forecast / Soll ---------------------------------
    const verlauf = daten.verlauf;
    const prognoseWerte = verlauf.prognose.filter((wert) => wert !== null);
    const prognoseEnde = prognoseWerte.length ? prognoseWerte[prognoseWerte.length - 1] : null;
    const prognoseFarbe = prognoseEnde !== null && prognoseEnde > verlauf.soll ? farbe.rose : farbe.ocker;

    const leinwand = document.getElementById('diagrammVerlauf');
    if (leinwand) {
        const verlaufFuellung = leinwand.getContext('2d').createLinearGradient(0, 0, 0, 320);
        verlaufFuellung.addColorStop(0, 'rgba(141, 155, 127, 0.35)');
        verlaufFuellung.addColorStop(1, 'rgba(141, 155, 127, 0.02)');

        new Chart(leinwand, {
            type: 'line',
            data: {
                labels: verlauf.beschriftungen,
                datasets: [
                    {
                        label: 'Ist (kumuliert)',
                        data: verlauf.ist,
                        borderColor: farbe.sageDeep,
                        backgroundColor: verlaufFuellung,
                        fill: true,
                        cubicInterpolationMode: 'monotone',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: farbe.sageDeep,
                    },
                    {
                        label: 'Hochrechnung mit Forecast',
                        data: verlauf.prognose,
                        borderColor: prognoseFarbe,
                        borderDash: [6, 5],
                        borderWidth: 2.5,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 3,
                        pointBackgroundColor: prognoseFarbe,
                        fill: false,
                    },
                    {
                        // Betrag direkt in der Legende, damit die Linie ohne Nachschlagen lesbar ist
                        label: `Budget (Soll): ${euroKurz.format(verlauf.soll)}`,
                        data: verlauf.monate.map(() => verlauf.soll),
                        borderColor: farbe.rose,
                        borderWidth: 2,
                        borderDash: [4, 4],
                        pointRadius: 0,
                        pointStyle: 'line',
                        fill: false,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                    tooltip: {
                        backgroundColor: farbe.ink,
                        padding: 10,
                        callbacks: {
                            label: (kontext) => kontext.raw === null ? null : ` ${kontext.dataset.label}: ${euro.format(kontext.raw)}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 14 } },
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.max(verlauf.soll, prognoseEnde ?? 0, daten.ist) * 1.1,
                        grid: { color: farbe.line },
                        ticks: { callback: (wert) => euroKurz.format(wert) },
                    },
                },
            },
        });
    }
})();
