<?php
declare(strict_types=1);

namespace Malia;

use Malia\Models\User;
use Malia\Repositories\UserRepository;

/**
 * Anmeldung, Sessions, Rollenprüfung und CSRF-Schutz.
 * Alle Prüfungen laufen auf dem Server; dem Browser wird nichts geglaubt.
 */
class Auth
{
    private ?User $cachedUser = null;

    // Präfix für Weiterleitungen aus Unterordnern ("" oder "../")
    private static string $basePath = '';

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = $basePath;
    }

    public function __construct(private UserRepository $users)
    {
    }

    /** Startet die Session mit sicheren Cookie-Einstellungen (nur einmal pro Request). */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');       // keine fremden Session-IDs akzeptieren
        session_set_cookie_params([
            'httponly' => true,                         // kein Zugriff per JavaScript
            'samesite' => 'Lax',                        // Cookie nicht bei fremden Seiten mitsenden
        ]);
        session_start();
    }

    /**
     * Prüft Benutzername und Passwort. Bei Erfolg wird der Benutzer in der Session
     * gespeichert. Gibt true/false zurück, aber nie den Grund eines Fehlers.
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);

        // Auch bei unbekanntem Benutzer wird ein Hash geprüft, damit die Antwortzeit
        // nicht verrät, ob es den Benutzernamen gibt.
        $hash = $user ? $user->passwordHash : password_hash('dummy', PASSWORD_DEFAULT);
        $passwordOk = password_verify($password, $hash);

        if ($user === null || !$passwordOk) {
            return false;
        }

        // Neue Session-ID nach dem Login (verhindert Session Fixation)
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $this->cachedUser = $user;
        return true;
    }

    /**
     * Demo-Modus: wechselt ohne Passwort zu einem anderen Benutzer.
     * Der Aufrufer (wechsel.php) muss vorher den Demo-Modus und das CSRF-Token prüfen.
     */
    public function switchTo(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $this->cachedUser = $user;
    }

    /** Beendet die Session komplett. */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->cachedUser = null;
    }

    /**
     * Der angemeldete Benutzer oder null. Er wird bei jedem Request frisch aus der
     * Datenbank geladen, damit eine geänderte Rolle sofort gilt.
     */
    public function currentUser(): ?User
    {
        if ($this->cachedUser === null && isset($_SESSION['user_id'])) {
            $this->cachedUser = $this->users->findById((int) $_SESSION['user_id']);
        }
        return $this->cachedUser;
    }

    /** Leitet nicht angemeldete Besucher zum Login um. */
    public function requireLogin(): User
    {
        $user = $this->currentUser();
        if ($user === null) {
            self::redirect(self::$basePath . 'login.php');
        }
        return $user;
    }

    /** Erlaubt nur die angegebenen Rollen, sonst Fehler 403 (Zugriff verweigert). */
    public function requireRole(string ...$roles): User
    {
        $user = $this->requireLogin();
        if (!in_array($user->role, $roles, true)) {
            fehlerseite(403, 'Auf diese Seite haben Sie keinen Zugriff.');
        }
        return $user;
    }

    /** Startseite je Rolle (Ziel nach dem Login). */
    public static function homeFor(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN   => 'admin/users.php',
            User::ROLE_FINANCE => 'finance/overview.php',
            default            => 'owner/index.php',
        };
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    // ---------- CSRF-Schutz ----------
    // Jedes Formular enthält ein geheimes Token aus der Session. Eine fremde Seite
    // kennt es nicht und kann deshalb keine Formulare in unserem Namen absenden.

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Fertiges verstecktes Formularfeld. */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    /** Prüft das gesendete Token (zeitsicher mit hash_equals). */
    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
