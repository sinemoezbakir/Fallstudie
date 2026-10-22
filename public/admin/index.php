<?php
/**
 * Admin: Benutzer anlegen und Rolle zuweisen (MB-030).
 *
 * Bewusst einfach gehalten (E-009): nur Anlegen + Übersicht, kein
 * Bearbeiten/Löschen - das ist laut Projektplan 2.2 (MoSCoW) nicht
 * gefordert (Admin-Zusatzfunktionen wie Audit-Log sind "Won't").
 */

session_start();
require_once __DIR__ . '/../../src/rollen.php';
erfordereRolle(ROLLE_ADMIN);

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/ui.php';

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anzeigename = trim($_POST['anzeigename'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $rolle = $_POST['rolle'] ?? '';
    $passwort = $_POST['passwort'] ?? '';

    $gueltigeRollen = [ROLLE_ADMIN, ROLLE_FINANCE, ROLLE_OWNER];

    if ($anzeigename === '' || $username === '' || $passwort === '') {
        $fehler = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (!in_array($rolle, $gueltigeRollen, true)) {
        $fehler = 'Bitte eine gültige Rolle auswählen.';
    } elseif (strlen($passwort) < 8) {
        $fehler = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } else {
        $pruefung = $pdo->prepare('SELECT COUNT(*) FROM benutzer WHERE username = :username');
        $pruefung->execute(['username' => $username]);

        if ((int) $pruefung->fetchColumn() > 0) {
            $fehler = 'Dieser Benutzername ist bereits vergeben.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle)
                 VALUES (:username, :hash, :anzeigename, :rolle)'
            );
            $stmt->execute([
                'username' => $username,
                'hash' => password_hash($passwort, PASSWORD_DEFAULT),
                'anzeigename' => $anzeigename,
                'rolle' => $rolle,
            ]);

            setzeFlash('erfolg', "Benutzer „{$anzeigename}“ wurde angelegt.");
            header('Location: /admin/');
            exit;
        }
    }
}

$benutzerListe = $pdo->query('SELECT id, username, anzeigename, rolle FROM benutzer ORDER BY anzeigename')->fetchAll();

$pageTitle = 'Benutzerverwaltung';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Benutzerverwaltung</h1>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <h2 class="h5 mb-3">Neuen Benutzer anlegen</h2>
    <form method="post" action="/admin/" class="row g-3">
        <div class="col-md-3">
            <label for="anzeigename" class="form-label">Anzeigename</label>
            <input type="text" class="form-control" id="anzeigename" name="anzeigename" required>
        </div>
        <div class="col-md-3">
            <label for="username" class="form-label">Benutzername</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="col-md-2">
            <label for="rolle" class="form-label">Rolle</label>
            <select class="form-select" id="rolle" name="rolle" required>
                <option value="">-- wählen --</option>
                <option value="ADMIN">Admin</option>
                <option value="FINANCE">Finance</option>
                <option value="OWNER">Owner</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="passwort" class="form-label">Passwort</label>
            <input type="password" class="form-control" id="passwort" name="passwort" minlength="8" required>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn w-100" style="background-color: var(--sage); color: #fff;">
                Anlegen
            </button>
        </div>
    </form>
</div>

<h2 class="h5 mb-3">Alle Benutzer</h2>
<table class="table">
    <thead>
        <tr>
            <th>Anzeigename</th>
            <th>Benutzername</th>
            <th>Rolle</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($benutzerListe as $benutzer): ?>
            <tr>
                <td><?= htmlspecialchars($benutzer['anzeigename']) ?></td>
                <td><?= htmlspecialchars($benutzer['username']) ?></td>
                <td><?= htmlspecialchars($benutzer['rolle']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
