<?php
/**
 * Admin: Passwort eines Benutzers zurücksetzen ("Passwort vergessen"-Funktion,
 * bewusst einfach ohne E-Mail-Versand - siehe public/passwort-vergessen.php).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_ADMIN);

$benutzerId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, anzeigename, username FROM benutzer WHERE id = :id');
$stmt->execute(['id' => $benutzerId]);
$benutzer = $stmt->fetch();

if (!$benutzer) {
    http_response_code(404);
    require __DIR__ . '/../../templates/404.php';
    exit;
}

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $neuesPasswort = $_POST['passwort'] ?? '';

    if (strlen($neuesPasswort) < 8) {
        $fehler = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
    } else {
        $update = $pdo->prepare('UPDATE benutzer SET passwort_hash = :hash WHERE id = :id');
        $update->execute([
            'hash' => password_hash($neuesPasswort, PASSWORD_DEFAULT),
            'id' => $benutzer['id'],
        ]);

        setzeFlash('erfolg', "Passwort für „{$benutzer['anzeigename']}“ wurde zurückgesetzt.");
        header('Location: /admin/');
        exit;
    }
}

$pageTitle = 'Passwort zurücksetzen';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-1">Passwort zurücksetzen</h1>
<p class="text-muted mb-4">
    für <strong><?= htmlspecialchars($benutzer['anzeigename']) ?></strong>
    (<?= htmlspecialchars($benutzer['username']) ?>)
</p>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<div class="card p-4" style="max-width: 500px;">
    <form method="post" action="/admin/passwort-zuruecksetzen.php">
        <?= csrfFeld() ?>
        <input type="hidden" name="id" value="<?= (int) $benutzer['id'] ?>">
        <div class="mb-3">
            <label for="passwort" class="form-label">Neues Passwort</label>
            <input type="password" class="form-control" id="passwort" name="passwort" minlength="8" required autofocus>
        </div>
        <button type="submit" class="btn btn-maliá">Passwort setzen</button>
        <a href="/admin/" class="btn btn-maliá-outline">Abbrechen</a>
    </form>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
