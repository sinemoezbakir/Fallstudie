<?php
/**
 * Admin: Benutzer anlegen und Rolle zuweisen (MB-030).
 *
 * Bewusst einfach gehalten (E-009): Anlegen, Übersicht, Passwort
 * zurücksetzen - kein Löschen, weil Ausgaben auf Benutzer verweisen
 * (konzept.md Abschnitt 3).
 */

require_once __DIR__ . '/../../src/app.php';
erfordereRolle(ROLLE_ADMIN);

$fehler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pruefung = pruefeNeuenBenutzer($_POST, function (string $username) use ($pdo): bool {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM benutzer WHERE username = :username');
        $stmt->execute(['username' => $username]);

        return (int) $stmt->fetchColumn() > 0;
    });
    $fehler = $pruefung['fehler'];

    if (!$fehler) {
        $werte = $pruefung['werte'];
        $stmt = $pdo->prepare(
            'INSERT INTO benutzer (username, passwort_hash, anzeigename, rolle)
             VALUES (:username, :hash, :anzeigename, :rolle)'
        );
        $stmt->execute([
            'username' => $werte['username'],
            'hash' => password_hash($_POST['passwort'], PASSWORD_DEFAULT),
            'anzeigename' => $werte['anzeigename'],
            'rolle' => $werte['rolle'],
        ]);

        setzeFlash('erfolg', "Zugang für „{$werte['anzeigename']}“ ({$werte['username']}) wurde angelegt.");
        header('Location: /admin/');
        exit;
    }
}

$benutzerListe = $pdo->query(
    "SELECT u.id, u.username, u.anzeigename, u.rolle, COUNT(b.id) AS anzahl_budgets
     FROM benutzer u LEFT JOIN budget b ON b.owner_id = u.id
     GROUP BY u.id
     ORDER BY FIELD(u.rolle, 'ADMIN', 'FINANCE', 'OWNER'), u.anzeigename"
)->fetchAll();

$anzahlJeRolle = array_count_values(array_column($benutzerListe, 'rolle'));

$pageTitle = 'Benutzerverwaltung';
require __DIR__ . '/../../templates/header.php';
?>

<div class="seitenkopf">
    <h1>Benutzerverwaltung</h1>
    <p class="lead">Zugänge für Finance, Owner und Verwaltung anlegen und Passwörter zurücksetzen.</p>
</div>

<div class="row g-4">
    <div class="col-lg-4 order-lg-2">
        <div class="card p-4">
            <h2 class="karte-titel mb-3">Neuen Zugang anlegen</h2>
            <?= fehlerHinweis($fehler) ?>
            <form method="post" action="/admin/" novalidate>
                <?= csrfFeld() ?>
                <div class="mb-3">
                    <label for="anzeigename" class="form-label">Anzeigename</label>
                    <input type="text" class="form-control<?= feldKlasse($fehler, 'anzeigename') ?>" id="anzeigename"
                           name="anzeigename" maxlength="100" placeholder="z. B. Yoga-Leitung"
                           value="<?= alterWert('anzeigename') ?>" <?= feldAria($fehler, 'anzeigename') ?> required>
                    <?= feldFehler($fehler, 'anzeigename') ?>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control<?= feldKlasse($fehler, 'username') ?>" id="username"
                           name="username" maxlength="50" autocomplete="off" placeholder="z. B. yoga"
                           value="<?= alterWert('username') ?>" <?= feldAria($fehler, 'username') ?> required>
                    <?= feldFehler($fehler, 'username') ?>
                </div>
                <div class="mb-3">
                    <label for="rolle" class="form-label">Rolle</label>
                    <select class="form-select<?= feldKlasse($fehler, 'rolle') ?>" id="rolle" name="rolle"
                            <?= feldAria($fehler, 'rolle') ?> required>
                        <option value="">Bitte wählen …</option>
                        <?php foreach (ROLLEN as $schluessel => $info): ?>
                            <option value="<?= e($schluessel) ?>" <?= ($_POST['rolle'] ?? '') === $schluessel ? 'selected' : '' ?>>
                                <?= e($info['label']) ?> – <?= e($info['beschreibung']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= feldFehler($fehler, 'rolle') ?>
                </div>
                <div class="mb-4">
                    <label for="passwort" class="form-label">Startpasswort</label>
                    <input type="password" class="form-control<?= feldKlasse($fehler, 'passwort') ?>" id="passwort"
                           name="passwort" minlength="8" autocomplete="new-password"
                           <?= feldAria($fehler, 'passwort') ?> required>
                    <?= feldFehler($fehler, 'passwort') ?>
                    <div class="form-text">Mindestens 8 Zeichen. Die Person kann es danach selbst ändern.</div>
                </div>
                <button type="submit" class="btn btn-maliá w-100">Zugang anlegen</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8 order-lg-1">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach (ROLLEN as $schluessel => $info): ?>
                <span class="zaehler-chip">
                    <span class="avatar avatar--klein avatar--<?= e(strtolower($schluessel)) ?>"><?= (int) ($anzahlJeRolle[$schluessel] ?? 0) ?></span>
                    <?= e($info['label']) ?>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="tabelle-karte">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Rolle</th>
                            <th class="text-end">Budgets</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($benutzerListe as $benutzer): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar--<?= e(strtolower($benutzer['rolle'])) ?>"><?= e(initiale($benutzer['anzeigename'])) ?></span>
                                        <div class="lh-sm">
                                            <div class="fw-semibold"><?= e($benutzer['anzeigename']) ?></div>
                                            <div class="small text-muted"><?= e($benutzer['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="rollen-chip rollen-chip--<?= e(strtolower($benutzer['rolle'])) ?>"><?= e(rolleLabel($benutzer['rolle'])) ?></span></td>
                                <td class="text-end tabular">
                                    <?= $benutzer['rolle'] === ROLLE_OWNER ? (int) $benutzer['anzahl_budgets'] : '<span class="text-muted">–</span>' ?>
                                </td>
                                <td class="text-end">
                                    <a href="/admin/passwort-zuruecksetzen.php?id=<?= (int) $benutzer['id'] ?>"
                                       class="btn btn-sm btn-maliá-outline">Passwort zurücksetzen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
