<?php
// Vorlage für die Zugangsdaten.
// Kopieren nach config/config.php und Werte anpassen.
// config.php wird NICHT ins Git eingecheckt (siehe .gitignore).

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,          // XAMPP-Standard (bei uns evtl. 3307)
        'name'     => 'budget_malia',
        'user'     => 'root',
        'password' => '',            // XAMPP-Standard: leer
    ],
    // Demo-Modus: Wechsel zwischen Konten ohne Passwort. Im Echtbetrieb false lassen!
    'demo_rollenwechsel' => false,
    'demo_konten'        => ['admin', 'finance', 'cafe', 'marketing', 'studio'],

    // KI-Prognose (Google Gemini, kostenloser Key: https://aistudio.google.com/apikey).
    // Key hier eintragen ODER als Umgebungsvariable GEMINI_API_KEY setzen. Nie ins Git!
    'ki' => [
        'aktiv'    => false,
        'api_key'  => '',
        'modell'   => 'gemini-3.5-flash',
    ],
];
