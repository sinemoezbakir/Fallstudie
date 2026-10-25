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
                        label: 'Soll',
                        data: verlauf.monate.map(() => verlauf.soll),
                        borderColor: farbe.rose,
                        borderWidth: 1.5,
                        borderDash: [2, 4],
                        pointRadius: 0,
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

    // --- MB-043: Soll-Ist als Ring mit Prozentangabe in der Mitte ----------------
    const ring = document.getElementById('diagrammSollIst');
    if (ring) {
        const ueberschritten = daten.ist > daten.soll;
        const statusFarbe = {
            im_rahmen: farbe.sageDeep,
            warnung: farbe.ocker,
            ueberschritten: farbe.rose,
            abgelaufen: farbe.muted,
        }[daten.status] || farbe.sageDeep;

        const mitteText = {
            id: 'mitteText',
            afterDraw(chart) {
                const { ctx, chartArea } = chart;
                const x = (chartArea.left + chartArea.right) / 2;
                const y = (chartArea.top + chartArea.bottom) / 2;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.fillStyle = farbe.ink;
                ctx.font = `2rem ${css('--serif')}`;
                ctx.fillText(`${daten.verbrauch.toLocaleString('de-DE')} %`, x, y + 4);
                ctx.fillStyle = farbe.muted;
                ctx.font = `600 0.72rem ${css('--sans')}`;
                ctx.fillText('VERBRAUCHT', x, y + 24);
                ctx.restore();
            },
        };

        new Chart(ring, {
            type: 'doughnut',
            data: {
                labels: ueberschritten ? ['Soll', 'Überschreitung'] : ['Ist', 'Verfügbar'],
                datasets: [{
                    data: ueberschritten
                        ? [daten.soll, daten.ist - daten.soll]
                        : [daten.ist, Math.max(daten.soll - daten.ist, 0)],
                    backgroundColor: ueberschritten ? [farbe.ocker, farbe.rose] : [statusFarbe, farbe.cream],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                maintainAspectRatio: false,
                cutout: '74%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } },
                    tooltip: {
                        backgroundColor: farbe.ink,
                        callbacks: { label: (kontext) => ` ${kontext.label}: ${euro.format(kontext.raw)}` },
                    },
                },
            },
            plugins: [mitteText],
        });
    }
})();
