<?php
declare(strict_types=1);

use Malia\Auth;
use Malia\Models\Budget;
use Malia\Models\User;
use Malia\Repositories\BudgetRepository;
use Malia\Repositories\UserRepository;

require __DIR__ . '/../bootstrap.php';

// Nur FINANCE darf Budgets anlegen und zuweisen
$user    = $auth->requireRole(User::ROLE_FINANCE);   // $user wird vom Layout gebraucht
$budgets = new BudgetRepository($pdo);
$owners  = (new UserRepository($pdo))->findByRole(User::ROLE_OWNER);

// Erlaubte Owner-IDs: nur Benutzer mit Rolle OWNER (serverseitig prüfen!)
$ownerNames = [];
foreach ($owners as $o) {
    $ownerNames[$o->id] = $o->username;
}

/** Prüft ein Datum im Format YYYY-MM-DD, gibt es zurück oder null. */
function parseDate(string $value): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Ungültige Anfrage. Bitte erneut versuchen.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        // ----- Budget anlegen -----
        $name    = trim((string) ($_POST['name'] ?? ''));
        $bereich = trim((string) ($_POST['bereich'] ?? ''));
        // Komma als Dezimaltrenner erlauben (1500,50); parseBetrag() prüft streng (siehe helpers.php)
        $soll    = parseBetrag((string) ($_POST['soll_betrag'] ?? ''));
        $von     = parseDate((string) ($_POST['gueltig_von'] ?? ''));
        $bis     = parseDate((string) ($_POST['gueltig_bis'] ?? ''));
        $ownerId = (int) ($_POST['owner_id'] ?? 0);

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Name: Pflichtfeld, höchstens 100 Zeichen.';
        }
        if ($bereich === '' || mb_strlen($bereich) > 50) {
            $errors[] = 'Bereich: Pflichtfeld, höchstens 50 Zeichen.';
        }
        if ($soll === null) {
            $errors[] = 'Soll-Betrag muss eine Zahl größer als 0 sein, ohne Tausenderpunkt (z. B. 1500 oder 1500,50).';
        }
        if ($von === null || $bis === null) {
            $errors[] = 'Bitte gültiges Datum für "gültig von" und "gültig bis" angeben.';
        } elseif ($bis <= $von) {   // Datumstexte im Format YYYY-MM-DD lassen sich direkt vergleichen
            $errors[] = 'Das Ende muss nach dem Beginn liegen.';
        }
        if (!isset($ownerNames[$ownerId])) {
            $errors[] = 'Bitte einen gültigen Owner auswählen.';
        }

        if (!$errors) {
            $budgets->create(new Budget(0, $name, $bereich, $soll, $von, $bis, $ownerId, $user->id));
            $_SESSION['flash_success'] = 'Budget "' . $name . '" wurde angelegt.';
            Auth::redirect('budgets.php');   // Post/Redirect/Get
        }
    } elseif (($_POST['action'] ?? '') === 'assign') {
        // ----- Budget einem anderen Owner zuweisen -----
        $budget  = $budgets->findById((int) ($_POST['budget_id'] ?? 0));
        $ownerId = (int) ($_POST['owner_id'] ?? 0);

        if ($budget === null) {
            $errors[] = 'Budget nicht gefunden.';
        } elseif (!isset($ownerNames[$ownerId])) {
            $errors[] = 'Bitte einen gültigen Owner auswählen.';
        } else {
            $budgets->assignOwner($budget->id, $ownerId);
            $_SESSION['flash_success'] = 'Budget "' . $budget->name . '" wurde ' . $ownerNames[$ownerId] . ' zugewiesen.';
            Auth::redirect('budgets.php');
        }
    }
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$rows      = $budgets->findAllWithIst();
$pageTitle = 'Budgets verwalten';
require __DIR__ . '/../../src/layout/header.php';
?>
<h1 class="h3 mb-4">Budgets verwalten</h1>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <!-- Formular: Budget anlegen -->
    <div class="col-lg-4">
        <form method="post" class="card card-body shadow-sm">
            <h2 class="h5">Budget anlegen</h2>
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required maxlength="100"
                       value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="bereich" class="form-label">Bereich</label>
                <input type="text" class="form-control" id="bereich" name="bereich" required maxlength="50"
                       placeholder="z. B. Studio, Café, Marketing" value="<?= e($_POST['bereich'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="soll_betrag" class="form-label">Soll-Betrag (€)</label>
                <input type="text" inputmode="decimal" class="form-control" id="soll_betrag" name="soll_betrag" required
                       value="<?= e($_POST['soll_betrag'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="gueltig_von" class="form-label">Gültig von</label>
                <input type="date" class="form-control" id="gueltig_von" name="gueltig_von" required
                       value="<?= e($_POST['gueltig_von'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="gueltig_bis" class="form-label">Gültig bis (Verfallszeit)</label>
                <input type="date" class="form-control" id="gueltig_bis" name="gueltig_bis" required
                       value="<?= e($_POST['gueltig_bis'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="owner_id" class="form-label">Owner</label>
                <select class="form-select" id="owner_id" name="owner_id" required>
                    <option value="">– bitte wählen –</option>
                    <?php foreach ($ownerNames as $id => $name): ?>
                        <option value="<?= e($id) ?>" <?= ((int) ($_POST['owner_id'] ?? 0) === $id) ? 'selected' : '' ?>>
                            <?= e($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Anlegen</button>
        </form>
    </div>

    <!-- Tabelle aller Budgets mit Zuweisung -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <h2 class="h5">Alle Budgets</h2>
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Name</th><th>Bereich</th><th class="text-end">Soll</th><th>Gültigkeit</th><th>Owner zuweisen</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): $b = $row['budget']; ?>
                        <tr>
                            <td><?= e($b->name) ?></td>
                            <td><?= e($b->bereich) ?></td>
                            <td class="text-end"><?= e(number_format($b->sollBetrag, 2, ',', '.')) ?> €</td>
                            <td><?= e($b->gueltigVon) ?> bis <?= e($b->gueltigBis) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="budget_id" value="<?= e($b->id) ?>">
                                    <select name="owner_id" class="form-select form-select-sm"
                                            aria-label="Owner für <?= e($b->name) ?>">
                                        <?php foreach ($ownerNames as $id => $name): ?>
                                            <option value="<?= e($id) ?>" <?= $b->ownerId === $id ? 'selected' : '' ?>>
                                                <?= e($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Zuweisen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5">Noch keine Budgets vorhanden.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
