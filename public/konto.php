<?php
/**
 * Eigenes Passwort ändern (MB-062) - für alle Rollen.
 * Verlangt das aktuelle Passwort, damit niemand an einem kurz
 * unbeaufsichtigten Rechner das Passwort umstellen kann.
 */

require_once __DIR__ . '/../src/app.php';

$benutzerId = eingeloggteBenutzerId();
$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT passwort_hash FROM benutzer WHERE id = :id');
    $stmt->execute(['id' => $benutzerId]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify((string) ($_POST['aktuelles_passwort'] ?? ''), $hash)) {
        $fehler['aktuelles_passwort'] = 'Das aktuelle Passwort stimmt nicht.';
    }

    $passwortFehler = pruefePasswort($_POST['neues_passwort'] ?? null, $_POST['neues_passwort_wiederholung'] ?? '');
    if ($passwortFehler !== null) {
        $fehler['neues_passwort'] = $passwortFehler;
    } elseif (!isset($fehler['aktuelles_passwort']) && $_POST['neues_passwort'] === $_POST['aktuelles_passwort']) {
        $fehler['neues_passwort'] = 'Das neue Passwort muss sich vom aktuellen unterscheiden.';
    }

    if (!$fehler) {
        $pdo->prepare('UPDATE benutzer SET passwort_hash = :hash WHERE id = :id')
            ->execute(['hash' => password_hash($_POST['neues_passwort'], PASSWORD_DEFAULT), 'id' => $benutzerId]);

        session_regenerate_id(true);
        setzeFlash('erfolg', 'Dein Passwort wurde geändert.');
        header('Location: /');
        exit;
    }
}

$pageTitle = 'Passwort ändern';
require __DIR__ . '/../templates/header.php';
?>

<div class="seitenkopf">
    <h1>Passwort ändern</h1>
    <p class="lead">für <?= e($_SESSION['anzeigename']) ?> (<?= e($_SESSION['username'] ?? '') ?>)</p>
</div>

<div class="card p-4" style="max-width: 480px;">
    <?= fehlerHinweis($fehler) ?>
    <form method="post" action="/konto.php" novalidate>
        <?= csrfFeld() ?>
        <div class="mb-3">
            <label for="aktuelles_passwort" class="form-label">Aktuelles Passwort</label>
            <input type="password" class="form-control<?= feldKlasse($fehler, 'aktuelles_passwort') ?>" id="aktuelles_passwort"
                   name="aktuelles_passwort" autocomplete="current-password" <?= feldAria($fehler, 'aktuelles_passwort') ?> required autofocus>
            <?= feldFehler($fehler, 'aktuelles_passwort') ?>
        </div>
        <div class="mb-3">
            <label for="neues_passwort" class="form-label">Neues Passwort</label>
            <input type="password" class="form-control<?= feldKlasse($fehler, 'neues_passwort') ?>" id="neues_passwort"
                   name="neues_passwort" minlength="8" autocomplete="new-password" <?= feldAria($fehler, 'neues_passwort') ?> required>
            <?= feldFehler($fehler, 'neues_passwort') ?>
            <div class="form-text">Mindestens 8 Zeichen.</div>
        </div>
        <div class="mb-4">
            <label for="neues_passwort_wiederholung" class="form-label">Neues Passwort wiederholen</label>
            <input type="password" class="form-control" id="neues_passwort_wiederholung" name="neues_passwort_wiederholung"
                   minlength="8" autocomplete="new-password" required>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-maliá">Passwort ändern</button>
            <a href="/" class="btn btn-link text-muted">Abbrechen</a>
        </div>
    </form>
    <?php if (DEMO_MODUS): ?>
        <p class="small text-muted mt-4 mb-0">Demo-Modus: „Demo-Daten zurücksetzen“ im Kontomenü stellt wieder <code><?= e(DEMO_PASSWORT) ?></code> her.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
