<?php
/**
 * Login mit Username und Passwort (Projektplan 3: Zugang per Username &
 * Passwort, PHP-Session, password_hash()/password_verify()).
 */

require_once __DIR__ . '/../src/app.php';

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
        $benutzer = pruefeLogin($username, $passwort);

        if ($benutzer !== null) {
            setzeAngemeldet($benutzer);

            header('Location: /');
            exit;
        }

        $fehler = 'Benutzername oder Passwort ist falsch.';
    }
}

$pageTitle = 'Anmelden';
require __DIR__ . '/../templates/header.php';
?>

<div class="login-buehne card overflow-hidden mx-auto">
    <div class="row g-0">
        <div class="col-lg-6 login-buehne__marke d-none d-lg-flex">
            <div>
                <p class="etikett text-white-50 mb-3">Maliá – Pilates &amp; Café</p>
                <h1 class="login-buehne__titel">Jeder Euro<br><em>an seinem Platz.</em></h1>
                <ul class="login-buehne__punkte">
                    <li>Soll, Ist und Forecast je Budget – live berechnet</li>
                    <li>Warnung, bevor ein Budget überschritten wird</li>
                    <li>Jede Leitung sieht nur ihre eigenen Budgets</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6 p-4 p-md-5">
            <h2 class="h2 mb-1">Willkommen zurück</h2>
            <p class="text-muted mb-4">Melde dich an, um deine Budgets zu sehen.</p>

            <?php if ($fehler): ?>
                <div class="alert alert-danger"><?= e($fehler) ?></div>
            <?php endif; ?>

            <form method="post" action="/login.php">
                <?= csrfFeld() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="passwort" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="passwort" name="passwort"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-maliá w-100">Anmelden</button>
            </form>
            <p class="text-center mt-3 mb-0 small">
                <a href="/passwort-vergessen.php">Passwort vergessen?</a>
            </p>

            <?php if (DEMO_MODUS): ?>
                <div class="demo-hinweis mt-4">
                    <strong>Demo-Zugang:</strong> z. B. <code>finance</code> / <code><?= e(DEMO_PASSWORT) ?></code>.
                    Nach dem Login kannst du oben jederzeit die Ansicht wechseln –
                    Admin, Finance oder eine der drei Leitungen.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
