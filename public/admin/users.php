<?php
declare(strict_types=1);

use Malia\Auth;
use Malia\Models\User;
use Malia\Repositories\UserRepository;

require __DIR__ . '/../bootstrap.php';

// Nur ADMIN. Diese Seite arbeitet ausschließlich mit Benutzerdaten,
// es werden keine Budgets, Ist-Werte oder Forecasts geladen.
$user  = $auth->requireRole(User::ROLE_ADMIN);   // $user wird vom Layout (Navigation) gebraucht
$admin = $user;
$users = new UserRepository($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Ungültige Anfrage. Bitte erneut versuchen.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        // ----- Benutzer anlegen -----
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role     = (string) ($_POST['role'] ?? '');

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $errors[] = 'Benutzername: 3–50 Zeichen, erlaubt sind Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich.';
        } elseif ($users->findByUsername($username) !== null) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            // 72 Bytes: länger wird ein bcrypt-Passwort ohnehin nicht ausgewertet
            $errors[] = 'Das Passwort muss 8 bis 72 Zeichen lang sein.';
        }
        // Rolle nur aus der festen Liste im Code, nie dem Formular vertrauen
        if (!in_array($role, User::ROLES, true)) {
            $errors[] = 'Ungültige Rolle.';
        }

        if (!$errors) {
            try {
                // Das Passwort wird nur als Hash gespeichert
                $users->create($username, password_hash($password, PASSWORD_DEFAULT), $role);
                $_SESSION['flash_success'] = 'Benutzer "' . $username . '" wurde angelegt.';
                Auth::redirect('users.php');   // Post/Redirect/Get: verhindert doppeltes Absenden beim Neuladen
            } catch (PDOException $ex) {
                // Doppelter Eintrag (Unique-Index), falls zwei Admins gleichzeitig anlegen
                $errors[] = 'Der Benutzer konnte nicht angelegt werden (Benutzername evtl. schon vergeben).';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'change_role') {
        // ----- Rolle ändern -----
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $role     = (string) ($_POST['role'] ?? '');
        $target   = $users->findById($targetId);

        if ($target === null) {
            $errors[] = 'Benutzer nicht gefunden.';
        } elseif (!in_array($role, User::ROLES, true)) {
            $errors[] = 'Ungültige Rolle.';
        } elseif ($target->id === $admin->id) {
            // Schutz vor Aussperren: der Admin darf sich nicht selbst die Rolle entziehen
            $errors[] = 'Die eigene Rolle kann nicht geändert werden.';
        } else {
            $users->updateRole($target->id, $role);
            $_SESSION['flash_success'] = 'Rolle von "' . $target->username . '" wurde geändert.';
            Auth::redirect('users.php');
        }
    }
}

// Erfolgsmeldung nach dem Redirect einmalig anzeigen
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$allUsers  = $users->findAll();
$pageTitle = 'Benutzerverwaltung';
require __DIR__ . '/../../src/layout/header.php';
?>
<h1 class="h3 mb-4">Benutzerverwaltung</h1>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <!-- Formular: Benutzer anlegen -->
    <div class="col-lg-4">
        <form method="post" class="card card-body shadow-sm">
            <h2 class="h5">Benutzer anlegen</h2>
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" class="form-control" id="username" name="username" required
                       minlength="3" maxlength="50" autocomplete="off"
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passwort (mind. 8 Zeichen)</label>
                <input type="password" class="form-control" id="password" name="password" required
                       minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rolle</label>
                <select class="form-select" id="role" name="role" required>
                    <?php foreach (User::ROLES as $r): ?>
                        <option value="<?= e($r) ?>" <?= (($_POST['role'] ?? '') === $r) ? 'selected' : '' ?>>
                            <?= e($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Anlegen</button>
        </form>
    </div>

    <!-- Benutzerliste mit Rollenänderung -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Alle Benutzer</h2>
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>ID</th><th>Benutzername</th><th>Rolle</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td><?= e($u->id) ?></td>
                            <td><?= e($u->username) ?></td>
                            <td>
                                <?php if ($u->id === $admin->id): ?>
                                    <?= e($u->role) ?> <span class="text-muted small">(du)</span>
                                <?php else: ?>
                                    <form method="post" class="d-flex gap-2">
                                        <?= Auth::csrfField() ?>
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?= e($u->id) ?>">
                                        <select name="role" class="form-select form-select-sm"
                                                aria-label="Rolle von <?= e($u->username) ?>">
                                            <?php foreach (User::ROLES as $r): ?>
                                                <option value="<?= e($r) ?>" <?= $u->role === $r ? 'selected' : '' ?>>
                                                    <?= e($r) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Ändern</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
