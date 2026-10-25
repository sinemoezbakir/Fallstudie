<?php
declare(strict_types=1);

use Malia\Auth;

require __DIR__ . '/bootstrap.php';

// Abmelden nur per POST mit CSRF-Token, damit keine fremde Seite uns ausloggen kann
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $auth->logout();
}

Auth::redirect('login.php');
