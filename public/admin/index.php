<?php
/**
 * Admin: Benutzer anlegen und Rolle zuweisen (MB-030).
 *
 * Bewusst einfach gehalten (E-009): nur Anlegen + Übersicht, kein
 * Bearbeiten/Löschen - das ist laut Projektplan 2.2 (MoSCoW) nicht
 * gefordert (Admin-Zusatzfunktionen wie Audit-Log sind "Won't").
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_ADMIN);

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anzeigename = trim($_POST['anzeigename'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $rolle = $_POST['rolle'] ?? '';
    $passwort = $_POST['passwort'] ?? '';

    $gueltigeRollen = [ROLLE_ADMIN, ROLLE_FINANCE, ROLLE_OWNER];

    if ($anzeigename === '' || $username === '' || $passwort === '') {
        $fehler = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (mb_strlen($anzeigename) > 100) {
        $fehler = 'Anzeigename darf höchstens 100 Zeichen lang sein.';
    } elseif (mb_strlen($username) > 50) {
        $fehler = 'Benutzername darf höchstens 50 Zeichen lang sein.';
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

<h1 class="h3 mb-1">Benutzerverwaltung</h1>
<p class="text-muted mb-4">Lege neue Zugänge für Finance, Owner oder Verwaltung an, oder setze ein Passwort zurück.</p>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <h2 class="h5 mb-3">Neuen Benutzer anlegen</h2>
    <form method="post" action="/admin/" class="row g-3">
        <?= csrfFeld() ?>
        <div class="col-md-3">
            <label for="anzeigename" class="form-label">Anzeigename</label>
            <input type="text" class="form-control" id="anzeigename" name="anzeigename" maxlength="100" required>
        </div>
        <div class="col-md-3">
            <label for="username" class="form-label">Benutzername</label>
            <input type="text" class="form-control" id="username" name="username" maxlength="50" required>
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
        <div class="col-12">
            <button type="submit" class="btn btn-maliá">
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
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($benutzerListe as $benutzer): ?>
            <tr>
                <td><?= htmlspecialchars($benutzer['anzeigename']) ?></td>
                <td><?= htmlspecialchars($benutzer['username']) ?></td>
                <td><?= htmlspecialchars($benutzer['rolle']) ?></td>
                <td>
                    <a href="/admin/passwort-zuruecksetzen.php?id=<?= (int) $benutzer['id'] ?>" class="small">
                        Passwort zurücksetzen
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
