<?php
/**
 * Rollen-Konstanten und Zugriffsschutz.
 *
 * Rollen stehen fest im Code (Projektplan 3, Annahme "Rollen dürfen fix im
 * Code stehen"). Jede Seite in public/admin/, public/finance/, public/owner/
 * beginnt mit:
 *
 *   session_start();
 *   require_once __DIR__ . '/../../src/rollen.php';
 *   erfordereRolle(ROLLE_...);
 *
 * Das ist die "Ordner-Regel" aus kanban/README.md - die zweite
 * Schutz-Ebene (ladeEigenesBudget() für einzelne Budgets) kommt mit MB-022.
 */

const ROLLE_ADMIN = 'ADMIN';
const ROLLE_FINANCE = 'FINANCE';
const ROLLE_OWNER = 'OWNER';

/**
 * Bricht mit 403 ab, wenn die eingeloggte Person nicht die geforderte
 * Rolle hat (oder gar nicht eingeloggt ist).
 */
function erfordereRolle(string $geforderteRolle): void
{
    $aktuelleRolle = $_SESSION['rolle'] ?? null;

    if ($aktuelleRolle !== $geforderteRolle) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }
}

/**
 * Gibt die eingeloggte Benutzer-ID zurück oder bricht mit 403 ab, falls
 * niemand eingeloggt ist. Nützlich für Seiten, die irgendeine gültige
 * Rolle akzeptieren, aber den Nutzer kennen müssen (z. B. "erfasst_von").
 */
function eingeloggteBenutzerId(): int
{
    if (empty($_SESSION['user_id'])) {
        http_response_code(403);
        require __DIR__ . '/../templates/403.php';
        exit;
    }

    return (int) $_SESSION['user_id'];
}
