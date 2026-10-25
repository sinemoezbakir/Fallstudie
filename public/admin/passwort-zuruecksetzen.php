<?php
/**
 * Admin: Passwort eines Benutzers zurücksetzen ("Passwort vergessen"-Funktion,
 * bewusst einfach ohne E-Mail-Versand - siehe public/passwort-vergessen.php).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_ADMIN);

$benutzerId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, anzeigename, username, rolle FROM benutzer WHERE id = :id');
$stmt->execute(['id' => $benutzerId]);
$benutzer = $stmt->fetch();

if (!$benutzer) {
    http_response_code(404);
    require __DIR__ . '/../../templates/404.php';
    exit;
}

$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwortFehler = pruefePasswort($_POST['passwort'] ?? null, $_POST['passwort_wiederholung'] ?? '');

    if ($passwortFehler !== null) {
        $fehler['passwort'] = $passwortFehler;
    } else {
        $update = $pdo->prepare('UPDATE benutzer SET passwort_hash = :hash WHERE id = :id');
        $update->execute([
            'hash' => password_hash($_POST['passwort'], PASSWORD_DEFAULT),
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

<a href="/admin/" class="zurueck-link">← Benutzerverwaltung</a>
<div class="seitenkopf">
    <h1>Passwort zurücksetzen</h1>
    <p class="lead">für <strong><?= e($benutzer['anzeigename']) ?></strong> (<?= e($benutzer['username']) ?>, <?= e(rolleLabel($benutzer['rolle'])) ?>)</p>
</div>

<div class="card p-4" style="max-width: 480px;">
    <form method="post" action="/admin/passwort-zuruecksetzen.php" novalidate>
        <?= csrfFeld() ?>
        <input type="hidden" name="id" value="<?= (int) $benutzer['id'] ?>">
        <div class="mb-3">
            <label for="passwort" class="form-label">Neues Passwort</label>
            <input type="password" class="form-control<?= feldKlasse($fehler, 'passwort') ?>" id="passwort" name="passwort"
                   minlength="8" autocomplete="new-password" <?= feldAria($fehler, 'passwort') ?> required autofocus>
            <?= feldFehler($fehler, 'passwort') ?>
        </div>
        <div class="mb-4">
            <label for="passwort_wiederholung" class="form-label">Neues Passwort wiederholen</label>
            <input type="password" class="form-control" id="passwort_wiederholung" name="passwort_wiederholung"
                   minlength="8" autocomplete="new-password" required>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-maliá">Passwort setzen</button>
            <a href="/admin/" class="btn btn-maliá-outline">Abbrechen</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
