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

    // Sicherheitsabfrage für Buttons mit data-bestaetigen="Text".
    document.querySelectorAll('[data-bestaetigen]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.bestaetigen)) {
                event.preventDefault();
            }
        });
    });
});
