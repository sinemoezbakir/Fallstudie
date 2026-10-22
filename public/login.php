<?php
/**
 * Login mit Username und Passwort (Projektplan 3: Zugang per Username &
 * Passwort, PHP-Session, password_hash()/password_verify()).
 */

session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/ui.php';

// Schon eingeloggt? Dann direkt weiterleiten statt nochmal Login zu zeigen.
if (!empty($_SESSION['rolle'])) {
    header('Location: /');
    exit;
}

$fehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $passwort = $_POST['passwort'] ?? '';

    if ($username === '' || $passwort === '') {
        $fehler = 'Bitte Benutzername und Passwort eingeben.';
    } else {
        $stmt = $pdo->prepare('SELECT id, passwort_hash, anzeigename, rolle FROM benutzer WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $benutzer = $stmt->fetch();

        if ($benutzer && password_verify($passwort, $benutzer['passwort_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $benutzer['id'];
            $_SESSION['anzeigename'] = $benutzer['anzeigename'];
            $_SESSION['rolle'] = $benutzer['rolle'];

            header('Location: /');
            exit;
        }

        // Bewusst dieselbe Fehlermeldung für "Username unbekannt" und
        // "Passwort falsch" - sonst lässt sich erraten, welche Usernamen
        // existieren.
        $fehler = 'Benutzername oder Passwort ist falsch.';
    }
}

$pageTitle = 'Anmelden';
require __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4">
            <h1 class="h3 mb-4">Willkommen bei Maliá Budget</h1>

            <?php if ($fehler): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
            <?php endif; ?>

            <form method="post" action="/login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="passwort" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="passwort" name="passwort" required>
                </div>
                <button type="submit" class="btn w-100" style="background-color: var(--sage); color: #fff;">
                    Anmelden
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
