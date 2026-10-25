<?php
declare(strict_types=1);

namespace Malia\Models;

/**
 * Ein Benutzer des Systems.
 * Die drei Rollen sind fest als Konstanten im Code hinterlegt.
 */
class User
{
    public const ROLE_FINANCE = 'FINANCE';   // Inhaberin
    public const ROLE_OWNER   = 'OWNER';     // Bereichsleitung
    public const ROLE_ADMIN   = 'ADMIN';     // Verwaltung

    public const ROLES = [self::ROLE_FINANCE, self::ROLE_OWNER, self::ROLE_ADMIN];

    public function __construct(
        public int $id,
        public string $username,
        public string $passwordHash,
        public string $role
    ) {
    }

    /** Baut ein User-Objekt aus einer Datenbankzeile. */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['username'],
            $row['password_hash'],
            $row['role']
        );
    }
}
