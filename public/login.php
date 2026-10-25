<?php
declare(strict_types=1);

use Malia\Auth;
use Malia\Repositories\UserRepository;

require __DIR__ . '/bootstrap.php';

// Wer schon angemeldet ist, wird direkt zur Startseite seiner Rolle geschickt
$user = $auth->currentUser();
if ($user !== null) {
    Auth::redirect(Auth::homeFor($user->role));
}

$fehler = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $fehler = 'Ungültige Anfrage. Bitte erneut versuchen.';
    } elseif (!empty($config['demo_rollenwechsel']) && isset($_POST['demo_konto'])) {
        // Demo-Schnellanmeldung ohne Passwort: nur im Demo-Modus und nur für Konten aus der Liste
        $konto = (string) $_POST['demo_konto'];
        $ziel  = in_array($konto, $config['demo_konten'] ?? [], true)
            ? (new UserRepository($pdo))->findByUsername($konto)
            : null;
        if ($ziel !== null) {
            $auth->switchTo($ziel);
            Auth::redirect(Auth::homeFor($ziel->role));
        }
        $fehler = 'Anmeldung fehlgeschlagen. Bitte Eingaben prüfen.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($auth->login($username, $password)) {
            Auth::redirect(Auth::homeFor($auth->currentUser()->role));
        }
        // Bewusst nur eine allgemeine Meldung: nicht verraten, was falsch war
        $fehler = 'Anmeldung fehlgeschlagen. Bitte Eingaben prüfen.';
    }
}

// Hier ist niemand angemeldet: der Header zeigt dann keine Navigation
$pageTitle = 'Anmeldung';
require __DIR__ . '/../src/layout/header.php';
?>
<div class="login-wrap">
    <!-- Titelbereich: Marke und Untertitel -->
    <div class="login-brand text-center">
        <div class="login-brand-title">Studio Maliá</div>
        <div class="login-brand-sub">Pilates &amp; Café · Budgetverwaltung</div>
    </div>

    <div class="card login-card shadow">
        <div class="card-body p-4">
            <h1 class="h5 text-center mb-4">Anmelden</h1>

            <?php if ($fehler !== ''): ?>
                <div class="alert alert-danger"><?= e($fehler) ?></div>
            <?php endif; ?>

            <form method="post">
                <?= Auth::csrfField() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus
                           autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Anmelden</button>
            </form>

            <?php if (!empty($config['demo_rollenwechsel'])): ?>
                <!-- Demo-Modus: Schnellanmeldung ohne Passwort (nur Entwicklung/Präsentation) -->
                <hr class="my-4">
                <form method="post">
                    <?= Auth::csrfField() ?>
                    <div class="small text-muted text-center mb-2">Demo-Modus (ohne Passwort)</div>
                    <!-- Ein Klick meldet das erste Demo-Konto an; danach wechselt man in der Seitenleiste zwischen den Konten -->
                    <button type="submit" name="demo_konto" value="<?= e(($config['demo_konten'] ?? [''])[0]) ?>"
                            class="btn btn-outline-secondary w-100">Demo starten</button>
                    <div class="small text-muted text-center mt-2">Danach in der Seitenleiste zwischen den Konten wechseln.</div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../src/layout/footer.php'; ?>
