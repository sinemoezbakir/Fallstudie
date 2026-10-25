/*
 * Kleine Helfer für alle Seiten. Bewusst ohne Framework (E-009).
 */
document.addEventListener('DOMContentLoaded', () => {
    // Toast-Meldungen nach ein paar Sekunden automatisch ausblenden.
    document.querySelectorAll('.toast').forEach((element) => {
        bootstrap.Toast.getOrCreateInstance(element).show();
    });

    // Suchfeld filtert Tabellenzeilen: <input data-filter-tabelle="#tabellen-id">
    document.querySelectorAll('[data-filter-tabelle]').forEach((feld) => {
        const zeilen = document.querySelectorAll(`${feld.dataset.filterTabelle} tbody tr`);
        feld.addEventListener('input', () => {
            const suche = feld.value.trim().toLowerCase();
            zeilen.forEach((zeile) => {
                zeile.hidden = suche !== '' && !zeile.textContent.toLowerCase().includes(suche);
            });
        });
    });

    // Ganze Tabellenzeile klickbar: <tr data-link="/pfad"> (Links/Buttons darin gehen vor)
    document.querySelectorAll('tr[data-link]').forEach((zeile) => {
        zeile.addEventListener('click', (event) => {
            if (!event.target.closest('a, button, input')) {
                window.location.href = zeile.dataset.link;
            }
        });
    });

    // Sortierbare Tabellen: <th data-sortieren="text|zahl">, Wert aus <td data-wert>
    document.querySelectorAll('table.sortierbar').forEach((tabelle) => {
        const koepfe = [...tabelle.querySelectorAll('th[data-sortieren]')];
        koepfe.forEach((kopf) => {
            kopf.tabIndex = 0;
            kopf.setAttribute('aria-sort', 'none');
            const sortiere = () => {
                const spalte = [...kopf.parentNode.children].indexOf(kopf);
                const aufsteigend = kopf.getAttribute('aria-sort') !== 'ascending';
                const istZahl = kopf.dataset.sortieren === 'zahl';
                const tbody = tabelle.tBodies[0];
                const zeilen = [...tbody.rows].filter((zeile) => zeile.cells.length > spalte);

                zeilen.sort((a, b) => {
                    const wertA = a.cells[spalte].dataset.wert ?? a.cells[spalte].textContent;
                    const wertB = b.cells[spalte].dataset.wert ?? b.cells[spalte].textContent;
                    const vergleich = istZahl
                        ? parseFloat(wertA) - parseFloat(wertB)
                        : wertA.localeCompare(wertB, 'de', { sensitivity: 'base' });
                    return aufsteigend ? vergleich : -vergleich;
                });
                zeilen.forEach((zeile) => tbody.appendChild(zeile));
                koepfe.forEach((k) => k.setAttribute('aria-sort', 'none'));
                kopf.setAttribute('aria-sort', aufsteigend ? 'ascending' : 'descending');
            };
            kopf.addEventListener('click', sortiere);
            kopf.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    sortiere();
                }
            });
        });
    });

    // Sicherheitsabfrage für Buttons mit data-bestaetigen="Text".
    document.querySelectorAll('[data-bestaetigen]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.bestaetigen)) {
                event.preventDefault();
            }
        });
    });
});
