<?php
/**
 * Finance: Neues Budget anlegen und einem Owner zuweisen (MB-031).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_FINANCE);

$fehler = null;

$owners = $pdo->query("SELECT id, anzeigename FROM benutzer WHERE rolle = 'OWNER' ORDER BY anzeigename")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bereich = $_POST['bereich'] ?? '';
    $sollBetrag = $_POST['soll_betrag'] ?? '';
    $gueltigVon = $_POST['gueltig_von'] ?? '';
    $gueltigBis = $_POST['gueltig_bis'] ?? '';
    $ownerId = $_POST['owner_id'] ?? '';

    $gueltigeBereiche = ['STUDIO', 'CAFE', 'MARKETING'];

    if ($name === '' || $gueltigVon === '' || $gueltigBis === '' || $ownerId === '') {
        $fehler = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (mb_strlen($name) > 150) {
        $fehler = 'Name darf höchstens 150 Zeichen lang sein.';
    } elseif (!in_array($bereich, $gueltigeBereiche, true)) {
        $fehler = 'Bitte einen gültigen Bereich auswählen.';
    } elseif (!is_numeric($sollBetrag) || (float) $sollBetrag <= 0) {
        $fehler = 'Soll-Betrag muss eine positive Zahl sein.';
    } elseif ($gueltigVon > $gueltigBis) {
        $fehler = '"Gültig von" darf nicht nach "Gültig bis" liegen.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO budget (name, bereich, soll_betrag, gueltig_von, gueltig_bis, owner_id)
             VALUES (:name, :bereich, :soll_betrag, :gueltig_von, :gueltig_bis, :owner_id)'
        );
        $stmt->execute([
            'name' => $name,
            'bereich' => $bereich,
            'soll_betrag' => round((float) $sollBetrag, 2),
            'gueltig_von' => $gueltigVon,
            'gueltig_bis' => $gueltigBis,
            'owner_id' => (int) $ownerId,
        ]);

        setzeFlash('erfolg', "Budget „{$name}“ wurde angelegt.");
        header('Location: /finance/');
        exit;
    }
}

$pageTitle = 'Neues Budget';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-4">Neues Budget anlegen</h1>

<?php if ($fehler): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<div class="card p-4" style="max-width: 600px;">
    <form method="post" action="/finance/neu.php">
        <?= csrfFeld() ?>
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" maxlength="150"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="bereich" class="form-label">Bereich</label>
            <select class="form-select" id="bereich" name="bereich" required>
                <option value="">-- wählen --</option>
                <option value="STUDIO">Studio</option>
                <option value="CAFE">Café</option>
                <option value="MARKETING">Marketing</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="soll_betrag" class="form-label">Soll-Betrag (€)</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="soll_betrag" name="soll_betrag" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="gueltig_von" class="form-label">Gültig von</label>
                <input type="date" class="form-control" id="gueltig_von" name="gueltig_von" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="gueltig_bis" class="form-label">Gültig bis</label>
                <input type="date" class="form-control" id="gueltig_bis" name="gueltig_bis" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="owner_id" class="form-label">Owner</label>
            <select class="form-select" id="owner_id" name="owner_id" required>
                <option value="">-- wählen --</option>
                <?php foreach ($owners as $owner): ?>
                    <option value="<?= (int) $owner['id'] ?>"><?= htmlspecialchars($owner['anzeigename']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-maliá">
            Budget anlegen
        </button>
        <a href="/finance/" class="btn btn-maliá-outline">Abbrechen</a>
    </form>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
