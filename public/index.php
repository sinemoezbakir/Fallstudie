<?php
declare(strict_types=1);

// Einstiegspunkt: leitet zur Startseite der Rolle bzw. zum Login.
// (Der frühere Verbindungstest war ohne Login öffentlich sichtbar und ist entfernt.)

use Malia\Auth;

require __DIR__ . '/bootstrap.php';

$user = $auth->currentUser();
Auth::redirect($user !== null ? Auth::homeFor($user->role) : 'login.php');

