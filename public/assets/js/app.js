/*
 * Kleine Helfer für alle Seiten. Bewusst ohne Framework (E-009).
 */
document.addEventListener('DOMContentLoaded', () => {
    // Toast-Meldungen nach ein paar Sekunden automatisch ausblenden.
    document.querySelectorAll('.toast').forEach((element) => {
        bootstrap.Toast.getOrCreateInstance(element).show();
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
