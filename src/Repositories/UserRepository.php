<?php
declare(strict_types=1);

namespace Malia\Repositories;

use Malia\Models\User;
use PDO;

/** Datenbankzugriff für die Tabelle users (nur Prepared Statements). */
class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Legt einen Benutzer an und gibt die neue ID zurück. Erwartet einen fertigen Hash. */
    public function create(string $username, string $passwordHash, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, :role)'
        );
        $stmt->execute(['username' => $username, 'hash' => $passwordHash, 'role' => $role]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    /** Wird für den Login gebraucht. */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ? User::fromRow($row) : null;
    }

    /** Alle Benutzer (nur für Admin gedacht, Rollenprüfung folgt im Service). */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM users ORDER BY username')->fetchAll();
        return array_map([User::class, 'fromRow'], $rows);
    }

    /** Alle Benutzer einer Rolle, z. B. alle Owner (für die Budget-Zuweisung). */
    public function findByRole(string $role): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE role = :role ORDER BY username');
        $stmt->execute(['role' => $role]);
        return array_map([User::class, 'fromRow'], $stmt->fetchAll());
    }

    public function updateRole(int $id, string $role): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }
}
